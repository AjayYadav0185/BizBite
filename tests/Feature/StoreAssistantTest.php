<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Store Assistant (owner-only Groq chatbot) API contract tests.
 *
 * Groq is faked at the HTTP layer — these tests pin down the authorization
 * (owner only), the request validation, the grounding prompt (the store's
 * menu/revenue snapshot must reach the model) and the audit trail.
 */
class StoreAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function groqResponse(string $answer = 'Your best seller today is Margherita.'): string
    {
        return json_encode([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => $answer]],
            ],
        ]);
    }

    private function ownerOf(Store $store): User
    {
        return User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Admin,
        ]);
    }

    private function seedMenu(Store $store): void
    {
        $category = Category::factory()->create(['store_id' => $store->id, 'name' => 'Pizza']);
        FoodItem::factory()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Margherita',
            'price' => 199.00,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
        ]);
    }

    private function seedSettledOrder(Store $store, User $user): void
    {
        Order::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'total_amount' => 449.00,
        ]);
    }

    public function test_guest_gets_401(): void
    {
        $this->postJson('/api/assistant/ask', ['question' => 'How many bills today?'])
            ->assertUnauthorized();
    }

    public function test_cashier_is_forbidden(): void
    {
        $store = Store::factory()->create();
        $cashier = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);

        Http::fake();

        $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'How many bills today?'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_owner_gets_a_store_grounded_answer(): void
    {
        $store = Store::factory()->create(['name' => 'Test Diner']);
        $owner = $this->ownerOf($store);
        $this->seedMenu($store);
        $this->seedSettledOrder($store, $owner);

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqResponse(), 200),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'How many bills did we close today?'])
            ->assertOk()
            ->assertJsonStructure(['reply', 'model', 'store_id', 'asked_at']);

        // The model MUST have received the store name + menu + revenue data.
        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $system = $messages[0]['content'] ?? '';
            $last = $messages[count($messages) - 1]['content'] ?? '';

            return str_contains($system, 'Test Diner')
                && str_contains($system, 'Margherita')
                && str_contains($system, '449.00')
                && $last === 'How many bills did we close today?';
        });

        // Every question is owner-visible audited.
        $this->assertDatabaseHas('tbl_pos_audit_logs', [
            'user_id' => $owner->id,
            'action' => AuditLog::ACTION_ASSISTANT_ASK,
        ]);
    }

    public function test_owner_cannot_ask_about_another_store(): void
    {
        $ownerStore = Store::factory()->create(['name' => 'My Store']);
        $otherStore = Store::factory()->create(['name' => 'Other Store']);
        $owner = $this->ownerOf($ownerStore);
        $this->seedMenu($otherStore);

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqResponse(), 200),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'What is on the menu?'])
            ->assertOk();

        Http::assertSent(function ($request) {
            $system = $request->data()['messages'][0]['content'] ?? '';

            return str_contains($system, 'My Store')
                && ! str_contains($system, 'Other Store')
                && ! str_contains($system, 'Margherita');
        });
    }

    public function test_history_turns_are_replayed_to_the_model(): void
    {
        $store = Store::factory()->create();
        $owner = $this->ownerOf($store);

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqResponse(), 200),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', [
                'question' => 'And what about best sellers?',
                'history' => [
                    ['role' => 'user', 'content' => 'How many bills today?'],
                    ['role' => 'assistant', 'content' => '3 bills.'],
                ],
            ])
            ->assertOk();

        Http::assertSent(function ($request) {
            $roles = array_map(
                fn (array $message) => $message['role'],
                $request->data()['messages'] ?? [],
            );

            return ['system', 'user', 'assistant', 'user'] === array_values($roles);
        });
    }

    public function test_question_is_validated(): void
    {
        $store = Store::factory()->create();
        $owner = $this->ownerOf($store);

        Http::fake();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');

        Http::assertNothingSent();
    }

    public function test_missing_groq_key_maps_to_503(): void
    {
        config(['services.groq.key' => null]);

        $store = Store::factory()->create();
        $owner = $this->ownerOf($store);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'How many bills today?'])
            ->assertStatus(503);
    }

    public function test_groq_failure_maps_to_a_friendly_502(): void
    {
        $store = Store::factory()->create();
        $owner = $this->ownerOf($store);

        Http::fake([
            'api.groq.com/*' => Http::response('boom', 500),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'How many bills today?'])
            ->assertStatus(502);
    }

    public function test_groq_bad_key_maps_to_502_with_a_hint(): void
    {
        $store = Store::factory()->create();
        $owner = $this->ownerOf($store);

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => ['message' => 'Invalid API Key']], 401),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/assistant/ask', ['question' => 'How many bills today?'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'The AI service rejected the server API key. Check GROQ_API_KEY.');
    }
}
