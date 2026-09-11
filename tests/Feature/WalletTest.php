<?php

namespace Tests\Feature;

use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer Wallet System — full API regression suite.
 *
 * Covers the three business rules from the spec:
 *   1. Sign-up bonus: every fresh registration starts at 200 points.
 *   2. Bill deduction: 1% of the settled bill total is debited (1 pt = ₹1),
 *      capped at the available balance so the wallet never goes negative.
 *   3. Razorpay recharge: initiate → pay → verify credits ₹N as N points,
 *      with server-side signature verification and replay idempotency.
 */
class WalletTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // 1. Sign-up bonus + balance endpoint
    // -----------------------------------------------------------------

    public function test_registration_grants_a_200_point_signup_bonus(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'customer@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.wallet_balance', '200.00');

        $user = User::query()->where('email', 'customer@example.test')->first();

        $this->assertSame('200.00', number_format((float) $user->wallet_balance, 2, '.', ''));

        $this->assertDatabaseHas('tbl_pos_wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'description' => 'Sign-up bonus',
            'amount' => '200.00',
        ]);
    }

    public function test_wallet_balance_endpoint_returns_balance_and_history(): void
    {
        $user = $this->cashier();
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => '200.00',
            'type' => 'credit',
            'description' => 'Sign-up bonus',
            'reference_id' => 'signup:'.$user->id,
            'balance_after' => '200.00',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/wallet/balance');

        $response->assertOk()
            ->assertJsonPath('wallet_balance', '200.00');

        $transactions = $response->json('transactions');
        $this->assertCount(1, $transactions);
        $this->assertSame('Sign-up bonus', $transactions[0]['description']);
        $this->assertSame('credit', $transactions[0]['type']);
    }

    public function test_wallet_balance_requires_authentication(): void
    {
        $this->getJson('/api/wallet/balance')->assertUnauthorized();
    }

    // -----------------------------------------------------------------
    // 2. Bill generation → 1% wallet deduction
    // -----------------------------------------------------------------

    public function test_bill_generation_debits_one_percent_of_the_total(): void
    {
        $store = Store::factory()->create();
        $user = $this->cashier(['store_id' => $store->id]);
        // Signup bonus so the wallet has a real balance to debit from.
        $user->forceFill(['wallet_balance' => '200.00'])->save();

        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 450.00,
            'is_available' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/bill/generate', [
                'items' => [['id' => $item->id, 'quantity' => 2]], // 900 total
                'payment_mode' => 'cash',
                'order_type' => 'takeaway',
            ]);

        $response->assertCreated();

        // Example B from the spec: bill 900 → 1% = 9 points debited.
        $this->assertSame('900.00', $response->json('total_amount'));
        $this->assertSame('9.00', $response->json('wallet_deduction'));
        $this->assertSame('191.00', $response->json('wallet_balance_after'));

        $user->refresh();
        $this->assertSame('191.00', number_format((float) $user->wallet_balance, 2, '.', ''));

        $this->assertDatabaseHas('tbl_pos_wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'debit',
            'description' => 'Bill deduction',
            'amount' => '-9.00',
            'reference_id' => (string) $response->json('order_id'),
        ]);
    }

    public function test_wallet_deduction_is_capped_at_the_available_balance(): void
    {
        $store = Store::factory()->create();
        $user = $this->cashier(['store_id' => $store->id]);
        $user->forceFill(['wallet_balance' => '5.00'])->save();

        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 900.00,
            'is_available' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/bill/generate', [
                'items' => [['id' => $item->id, 'quantity' => 1]], // 1% = 9 > 5
                'payment_mode' => 'cash',
            ]);

        $response->assertCreated();

        // Debit clamped to 5.00 — the wallet can never go negative.
        $this->assertSame('5.00', $response->json('wallet_deduction'));
        $this->assertSame('0.00', $response->json('wallet_balance_after'));
    }

    // -----------------------------------------------------------------
    // 3. Razorpay recharge flow
    // -----------------------------------------------------------------

    public function test_recharge_initiate_returns_a_razorpay_order_id(): void
    {
        $user = $this->cashier();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 100]);

        $response->assertCreated();
        $this->assertNotEmpty($response->json('razorpay_order_id'));
        $this->assertSame('100.00', $response->json('amount'));
        $this->assertSame('INR', $response->json('currency'));

        $this->assertDatabaseHas('tbl_pos_wallet_recharges', [
            'user_id' => $user->id,
            'amount' => '100.00',
            'razorpay_order_id' => $response->json('razorpay_order_id'),
            'status' => 'created',
        ]);
    }

    public function test_recharge_initiate_rejects_non_positive_amounts(): void
    {
        $user = $this->cashier();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 0])
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', [])
            ->assertStatus(422);
    }

    public function test_recharge_verify_credits_the_wallet_on_a_valid_signature(): void
    {
        // Force the real HMAC verification path (SDK is not installed in CI).
        config(['services.razorpay.key_id' => 'rzp_test_wallet']);
        config(['services.razorpay.key_secret' => 'test_secret']);

        $user = $this->cashier();
        $user->forceFill(['wallet_balance' => '200.00'])->save();

        $orderId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 100])
            ->json('razorpay_order_id');

        $paymentId = 'pay_TESTWALLET001';
        $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'test_secret');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/verify', [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature,
            ]);

        $response->assertOk()
            ->assertJsonPath('credited_points', '100.00')
            ->assertJsonPath('wallet_balance', '300.00');

        $user->refresh();
        $this->assertSame('300.00', number_format((float) $user->wallet_balance, 2, '.', ''));

        $this->assertDatabaseHas('tbl_pos_wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'description' => 'Razorpay recharge',
            'amount' => '100.00',
            'reference_id' => $paymentId,
        ]);

        $this->assertSame(
            'verified',
            WalletRecharge::query()->where('razorpay_order_id', $orderId)->value('status')
        );
    }

    public function test_recharge_verify_rejects_a_tampered_signature(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_wallet']);
        config(['services.razorpay.key_secret' => 'test_secret']);

        $user = $this->cashier();
        $user->forceFill(['wallet_balance' => '200.00'])->save();

        $orderId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 500])
            ->json('razorpay_order_id');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/verify', [
                'razorpay_payment_id' => 'pay_FAKE',
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => str_repeat('a', 64),
            ])
            ->assertStatus(422);

        $user->refresh();
        $this->assertSame('200.00', number_format((float) $user->wallet_balance, 2, '.', ''));
        $this->assertSame(
            'failed',
            WalletRecharge::query()->where('razorpay_order_id', $orderId)->value('status')
        );
    }

    public function test_recharge_verify_is_idempotent_on_replay(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_wallet']);
        config(['services.razorpay.key_secret' => 'test_secret']);

        $user = $this->cashier();

        $orderId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 100])
            ->json('razorpay_order_id');

        $payload = [
            'razorpay_payment_id' => 'pay_REPLAY01',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => hash_hmac('sha256', $orderId.'|pay_REPLAY01', 'test_secret'),
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/verify', $payload)
            ->assertOk();

        $user->refresh();
        $balanceAfterFirst = (float) $user->wallet_balance;

        // Replay of the SAME payment must not credit a second time.
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/wallet/recharge/verify', $payload)
            ->assertOk()
            ->assertJsonPath('wallet_balance', number_format($balanceAfterFirst, 2, '.', ''));

        $user->refresh();
        $this->assertSame($balanceAfterFirst, (float) $user->wallet_balance);
        $this->assertSame(
            1,
            WalletTransaction::query()
                ->where('user_id', $user->id)
                ->where('description', 'Razorpay recharge')
                ->count()
        );
    }

    public function test_recharge_verify_rejects_an_order_belonging_to_another_user(): void
    {
        $owner = $this->cashier(['email' => 'owner.wallet@example.test']);
        $attacker = $this->cashier(['email' => 'attacker.wallet@example.test']);

        $orderId = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/wallet/recharge/initiate', ['amount' => 100])
            ->json('razorpay_order_id');

        $this->actingAs($attacker, 'sanctum')
            ->postJson('/api/wallet/recharge/verify', [
                'razorpay_payment_id' => 'pay_STOLEN1',
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => 'whatever',
            ])
            ->assertStatus(404);

        $attacker->refresh();
        // Untouched: the column's 200 sign-up default — never a stolen credit.
        $this->assertSame('200.00', number_format((float) $attacker->wallet_balance, 2, '.', ''));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function cashier(array $overrides = []): User
    {
        $store = $overrides['store_id'] ?? Store::factory()->create()->id;

        return User::factory()->create(array_merge([
            'store_id' => $store,
            'role' => UserRole::Cashier,
        ], $overrides));
    }
}
