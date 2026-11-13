<?php

namespace Tests\Feature;

use App\Livewire\Admin\StoreSettings;
use App\Livewire\Pos\BillingDashboard;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * NCR market-readiness: GST invoice, UPI QR, hold/resume, reprint, WhatsApp
 * e-bill, counter validations, store settings and install command.
 */
class MarketReadyTest extends TestCase
{
    use RefreshDatabase;

    private function store(array $over = []): Store
    {
        return Store::factory()->create(array_merge([
            'name' => 'Apna Dhaba',
            'gstin' => '07ABCDE1234F1Z2',
            'fssai_license' => '10012043001234',
            'is_gst_enabled' => true,
            'upi_vpa' => 'apnadhaba@okaxis',
        ], $over));
    }

    private function user(Store $store, UserRole $role = UserRole::Cashier): User
    {
        return User::factory()->create(['store_id' => $store->id, 'role' => $role]);
    }

    private function item(Store $store, float $price = 105.00, int $gst = 5): FoodItem
    {
        return FoodItem::factory()->create([
            'store_id' => $store->id, 'price' => $price, 'gst_rate' => $gst,
            'is_available' => true, 'stock_quantity' => null,
        ]);
    }

    public function test_gst_receipt_carries_gstin_fssai_and_balanced_breakup(): void
    {
        $store = $this->store();
        $user = $this->user($store);
        $a = $this->item($store, 105.00, 5);
        $b = $this->item($store, 118.00, 18);

        $component = Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('addItem', $a->id)
            ->call('addItem', $b->id)
            ->set('discountInput', '23')
            ->call('checkout', 'cash')
            ->assertSet('showReceipt', true)
            ->assertSee('TAX INVOICE')
            ->assertSee('GSTIN: 07ABCDE1234F1Z2')
            ->assertSee('FSSAI Lic. No: 10012043001234');

        $receipt = $component->get('lastReceipt');
        $order = Order::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('07ABCDE1234F1Z2', $receipt['store']['gstin']);
        $this->assertCount(2, $receipt['tax_breakup']);
        $sum = array_reduce($receipt['tax_breakup'], fn ($c, $r) => bcadd($c, $r['tax'], 2), '0');
        $this->assertSame(number_format((float) $order->tax_amount, 2, '.', ''), $sum);
        $this->assertStringStartsWith('Rupees Two Hundred', $receipt['amount_in_words']);
    }

    public function test_unregistered_store_prints_bill_of_supply_without_tax_lines(): void
    {
        $store = $this->store(['is_gst_enabled' => false]);
        $user = $this->user($store);
        $item = $this->item($store);

        Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('checkout', 'cash')
            ->assertSee('BILL OF SUPPLY')
            ->assertDontSee('TAX INVOICE')
            ->assertDontSee('CGST');
    }

    public function test_on_screen_total_includes_coupon_and_matches_saved_bill(): void
    {
        $store = $this->store();
        $user = $this->user($store);
        $item = $this->item($store, 99.50);
        Campaign::factory()->create(['store_id' => $store->id, 'code' => 'NCR10', 'value' => 10]);

        $component = Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('addItem', $item->id)        // 199.00
            ->set('campaignInput', 'ncr10')
            ->assertSet('campaignDiscount', '19.90')
            ->assertSet('campaignStatus', 'applied')
            ->assertSet('payable', '179.00');    // 179.10 rounded to the rupee

        $component->call('checkout', 'upi');

        $this->assertSame('179.00', (string) Order::query()->withoutGlobalScopes()->value('total_amount'));
    }

    public function test_unknown_coupon_is_flagged_and_not_applied(): void
    {
        $store = $this->store();
        $item = $this->item($store);

        Livewire::actingAs($this->user($store))->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->set('campaignInput', 'FAKE99')
            ->assertSet('campaignStatus', 'invalid')
            ->assertSet('campaignDiscount', '0.00');
    }

    public function test_upi_qr_link_uses_store_vpa_and_exact_amount(): void
    {
        $store = $this->store();
        $item = $this->item($store, 250.00);

        $component = Livewire::actingAs($this->user($store))->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('setPaymentMode', 'upi');

        $this->assertStringContainsString('pa=apnadhaba@okaxis', $component->get('upiLink'));
        $this->assertStringContainsString('am=250.00', $component->get('upiLink'));
    }

    public function test_hold_and_resume_bill_round_trips_the_cart(): void
    {
        $store = $this->store();
        $user = $this->user($store);
        $item = $this->item($store);

        $component = Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('setOrderType', 'dine_in')
            ->set('tableNumberInput', 'T5')
            ->call('holdBill')
            ->assertSet('cart', [])
            ->assertSet('tableNumberInput', '');

        $held = $component->get('heldBills');
        $this->assertCount(1, $held);
        $first = array_values($held)[0];
        $this->assertSame('Table T5', $first['label']);

        // Held bills survive a fresh page load for the same cashier.
        $fresh = Livewire::actingAs($user)->test(BillingDashboard::class);
        $this->assertCount(1, $fresh->get('heldBills'));

        $fresh->call('resumeBill', $first['id'])
            ->assertSet('tableNumberInput', 'T5')
            ->assertSet('orderType', 'dine_in')
            ->assertSet("cart.{$item->id}.quantity", 1);
        $this->assertCount(0, $fresh->get('heldBills'));
    }

    public function test_held_bills_are_private_to_each_cashier(): void
    {
        $store = $this->store();
        $item = $this->item($store);

        Livewire::actingAs($this->user($store))->test(BillingDashboard::class)
            ->call('addItem', $item->id)->call('holdBill');

        $other = Livewire::actingAs($this->user($store))->test(BillingDashboard::class);
        $this->assertSame([], $other->get('heldBills'));
    }

    public function test_reprint_rebuilds_a_duplicate_copy(): void
    {
        $store = $this->store();
        $user = $this->user($store);
        $item = $this->item($store);

        $component = Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('checkout', 'cash');
        $orderId = Order::query()->withoutGlobalScopes()->value('id');

        $component->call('closeReceipt')
            ->set('lastReceipt', null)
            ->call('reprint', $orderId)
            ->assertDispatched('trigger-print')
            ->assertSet('lastReceipt.order_id', $orderId)
            ->assertSee('DUPLICATE COPY');
    }

    public function test_whatsapp_link_targets_customer_mobile(): void
    {
        $store = $this->store();
        $item = $this->item($store);

        $component = Livewire::actingAs($this->user($store))->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->set('customerNameInput', 'Amit')
            ->set('customerPhoneInput', '+91 98110 45678')
            ->call('checkout', 'cash');

        $this->assertStringStartsWith('https://wa.me/919811045678?text=', $component->get('whatsappLink'));
        $this->assertSame('9811045678', Order::query()->withoutGlobalScopes()->value('customer_phone'));
    }

    public function test_counter_guards_credit_dine_in_and_bad_mobile(): void
    {
        $store = $this->store();
        $item = $this->item($store);
        $pos = fn () => Livewire::actingAs($this->user($store))->test(BillingDashboard::class)->call('addItem', $item->id);

        $pos()->call('checkout', 'credit')->assertSet('error', 'Credit (pay later) bills need the customer name or mobile.');
        $pos()->call('setOrderType', 'dine_in')->call('checkout', 'cash')->assertSet('error', 'Enter the table number for a dine-in bill.');
        $pos()->set('customerPhoneInput', '12345')->call('checkout', 'cash')->assertSet('error', 'Customer mobile must be a valid 10-digit Indian number.');

        $this->assertSame(0, Order::query()->withoutGlobalScopes()->count());
    }

    public function test_owner_saves_valid_store_settings_with_audit(): void
    {
        $store = $this->store(['gstin' => null, 'is_gst_enabled' => false]);
        $owner = $this->user($store, UserRole::Admin);

        Livewire::actingAs($owner)->test(StoreSettings::class)
            ->set('gstEnabled', true)
            ->set('gstin', '06abcde1234f1z4')
            ->assertSet('gstin', '06ABCDE1234F1Z4')
            ->assertSet('state', 'Haryana')
            ->set('pincode', '122001')
            ->set('upiVpa', 'dhaba@okhdfcbank')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $store->fresh();
        $this->assertSame('06ABCDE1234F1Z4', $fresh->gstin);
        $this->assertSame('122001', $fresh->pincode);
        $this->assertSame('dhaba@okhdfcbank', $fresh->upi_vpa);
    }

    public function test_store_settings_reject_bad_gstin_fssai_upi_and_pincode(): void
    {
        $store = $this->store();
        $owner = $this->user($store, UserRole::Admin);

        Livewire::actingAs($owner)->test(StoreSettings::class)
            ->set('gstin', '07ABCDE1234F1Z9')
            ->set('fssai', '12345')
            ->set('upiVpa', 'not-a-vpa')
            ->set('pincode', '12')
            ->call('save')
            ->assertHasErrors(['gstin', 'fssai', 'upiVpa', 'pincode']);

        $this->assertSame('07ABCDE1234F1Z2', $store->fresh()->gstin);
    }

    public function test_gst_enabled_requires_a_gstin_and_save_is_audited(): void
    {
        $store = $this->store(['gstin' => null, 'is_gst_enabled' => false]);
        $owner = $this->user($store, UserRole::Admin);

        Livewire::actingAs($owner)->test(StoreSettings::class)
            ->set('gstEnabled', true)
            ->call('save')
            ->assertHasErrors(['gstin' => 'required'])
            ->set('gstin', '07ABCDE1234F1Z2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);

        $this->assertTrue($store->fresh()->is_gst_enabled);
        $this->assertTrue(AuditLog::query()->withoutGlobalScopes()->where('action', AuditLog::ACTION_STORE_SETTINGS)->exists());
    }

    public function test_cashier_cannot_open_store_settings(): void
    {
        $store = $this->store();

        Livewire::actingAs($this->user($store))->test(StoreSettings::class)->assertForbidden();
    }

    public function test_old_receipt_tab_link_opens_store_settings(): void
    {
        $store = $this->store();
        $owner = $this->user($store, UserRole::Admin);

        $this->actingAs($owner)->get('/admin?tab=receipt')->assertOk()->assertSee('Store &amp; billing settings', false);
    }

    public function test_install_command_creates_store_and_owner_once(): void
    {
        $this->artisan('bizbite:install', [
            '--store' => 'Sharma Sweets', '--name' => 'Ravi Sharma',
            '--email' => 'Owner@Sharma.in', '--password' => 'Secret123', '--no-interaction' => true,
        ])->assertSuccessful();

        $owner = User::query()->withoutGlobalScopes()->where('email', 'owner@sharma.in')->firstOrFail();
        $this->assertSame(UserRole::Admin, $owner->role);
        $this->assertSame('Sharma Sweets', $owner->store->name);
        $this->assertSame($owner->id, $owner->store->owner_user_id);

        $this->artisan('bizbite:install', ['--if-empty' => true, '--email' => 'x@y.in', '--no-interaction' => true])
            ->expectsOutputToContain('already set up')
            ->assertSuccessful();
        $this->assertSame(1, User::query()->withoutGlobalScopes()->count());
    }

    public function test_install_command_rejects_weak_password(): void
    {
        $this->artisan('bizbite:install', [
            '--store' => 'X', '--name' => 'Y', '--email' => 'a@b.in', '--password' => 'p', '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame(0, User::query()->withoutGlobalScopes()->count());
    }
}
