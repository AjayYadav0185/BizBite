<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Store profile & branding API (My Profile → "About shop" / "Manage shop").
 *
 * Pins the contract the Flutter `StoreProfileModel` relies on:
 *   - every signed-in staff member can READ their own store's shop details;
 *   - only the owner (admin) can WRITE them (cashier → 403);
 *   - the store is always resolved from the caller's own user row, so the
 *     payload can never target another tenant;
 *   - every write lands in the owner-visible audit trail;
 *   - the logo upload/replace/remove lifecycle cleans up the previous file.
 */
class StoreProfileApiTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Reads
    // -----------------------------------------------------------------

    public function test_store_profile_is_readable_by_any_staff_member(): void
    {
        $store = Store::factory()->create([
            'name' => 'Apna Zaika',
            'address' => '12 MG Road',
            'city' => 'Indore',
        ]);

        foreach ([$this->admin($store), $this->cashier($store)] as $staff) {
            $this->actingAs($staff, 'sanctum')
                ->getJson('/api/store')
                ->assertOk()
                ->assertJsonPath('store.id', $store->id)
                ->assertJsonPath('store.name', 'Apna Zaika')
                ->assertJsonPath('store.address', '12 MG Road')
                ->assertJsonPath('store.city', 'Indore')
                ->assertJsonPath('store.logo_url', null);
        }
    }

    public function test_store_profile_requires_authentication(): void
    {
        $this->getJson('/api/store')->assertUnauthorized();
    }

    // -----------------------------------------------------------------
    // Writes (owner only)
    // -----------------------------------------------------------------

    public function test_owner_can_update_shop_details_and_they_are_audited(): void
    {
        $store = Store::factory()->create(['name' => 'Old Name']);
        $admin = $this->admin($store);

        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/store', [
            'name' => 'New Name',
            'phone' => '9876543210',
            'alternate_phone' => '9876500000',
            'address' => '55 Station Road',
            'city' => 'Bhopal',
            'state' => 'MP',
            'pincode' => '462001',
            'gstin' => '23ABCDE1234F1Z5',
            'fssai_license' => '1234567890123',
            'upi_vpa' => 'apna@upi',
            'currency' => 'INR',
            'default_gst_rate' => 5,
            'is_gst_enabled' => true,
            'print_header' => 'APNA ZAIKA',
            'print_footer' => 'Thank you, visit again!',
        ]);

        $response->assertOk()
            ->assertJsonPath('store.name', 'New Name')
            ->assertJsonPath('store.city', 'Bhopal')
            ->assertJsonPath('store.upi_vpa', 'apna@upi')
            ->assertJsonPath('store.print_header', 'APNA ZAIKA')
            ->assertJsonPath('store.is_gst_enabled', true);

        $this->assertDatabaseHas('tbl_pos_stores', [
            'id' => $store->id,
            'name' => 'New Name',
            'address' => '55 Station Road',
            'gstin' => '23ABCDE1234F1Z5',
            'print_footer' => 'Thank you, visit again!',
        ]);

        $this->assertDatabaseHas('tbl_pos_audit_logs', [
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_STORE_SETTINGS,
            'entity_type' => 'store',
            'entity_id' => $store->id,
        ]);
    }

    public function test_blank_fields_are_cleared_to_null(): void
    {
        $store = Store::factory()->create(['address' => 'Old address']);
        $admin = $this->admin($store);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/store', ['address' => '   ', 'phone' => ''])
            ->assertOk()
            ->assertJsonPath('store.address', null)
            ->assertJsonPath('store.phone', null);

        $this->assertNull($store->fresh()->address);
    }

public function test_cashier_cannot_update_shop_details(): void
    {
        $store = Store::factory()->create(['name' => 'Locked Name']);
        $cashier = $this->cashier($store);

        $this->actingAs($cashier, 'sanctum')
            ->putJson('/api/store', ['name' => 'Hacked Name'])
            ->assertForbidden();

        $this->assertSame('Locked Name', $store->fresh()->name);
    }

    public function test_shop_details_validation_rejects_bad_payloads(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/store', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/store', ['currency' => 'RUPEES'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/store', ['gstin' => 'THIS-IS-WAY-TOO-LONG'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gstin');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/store', ['default_gst_rate' => 99])
            ->assertStatus(422)
            ->assertJsonValidationErrors('default_gst_rate');
    }

    // -----------------------------------------------------------------
    // Logo lifecycle
    // -----------------------------------------------------------------

    public function test_owner_can_upload_replace_and_remove_the_shop_logo(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create();
        $admin = $this->admin($store);

        $first = UploadedFile::fake()->image('logo.png', 200, 200);

        $uploaded = $this->actingAs($admin, 'sanctum')
            ->post('/api/store/logo', ['logo' => $first], ['Accept' => 'application/json'])
            ->assertOk();

        $firstPath = (string) $uploaded->json('store.logo_path');
        $this->assertNotSame('', $firstPath);
        Storage::disk('public')->assertExists($firstPath);
        $this->assertStringContainsString($firstPath, (string) $uploaded->json('store.logo_url'));
        $this->assertSame($firstPath, $store->fresh()->logo_path);

        // Replacing the logo must delete the previous file (no orphans).
        $second = UploadedFile::fake()->image('logo-2.jpg', 300, 300);

        $replaced = $this->actingAs($admin, 'sanctum')
            ->post('/api/store/logo', ['logo' => $second], ['Accept' => 'application/json'])
            ->assertOk();

        $secondPath = (string) $replaced->json('store.logo_path');
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);

        // Removing the logo clears the column + the file.
        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/store/logo')
            ->assertOk()
            ->assertJsonPath('store.logo_path', null)
            ->assertJsonPath('store.logo_url', null);

        Storage::disk('public')->assertMissing($secondPath);
        $this->assertNull($store->fresh()->logo_path);
    }

    public function test_cashier_cannot_upload_a_shop_logo(): void
    {
        Storage::fake('public');

        $cashier = $this->cashier();

        $this->actingAs($cashier, 'sanctum')
            ->post('/api/store/logo', [
                'logo' => UploadedFile::fake()->image('logo.png'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->assertNull($cashier->fresh()->store->logo_path);
    }

    public function test_logo_upload_rejects_a_non_image_file(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->post('/api/store/logo', [
                'logo' => UploadedFile::fake()->create('invoice.pdf', 40, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('logo');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function admin(?Store $store = null): User
    {
        return User::factory()->create([
            'store_id' => ($store ?? Store::factory()->create())->id,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
    }

    private function cashier(?Store $store = null): User
    {
        return User::factory()->create([
            'store_id' => ($store ?? Store::factory()->create())->id,
            'role' => UserRole::Cashier,
            'is_active' => true,
        ]);
    }
}