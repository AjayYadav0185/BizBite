<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Store;
use App\Services\Audit;
use App\Support\India;
use Closure;
use Livewire\Component;

/**
 * OWNER ADMIN — Store & billing settings.
 *
 * Everything that legally has to appear on an Indian restaurant bill lives
 * here: trade name, address, GSTIN (format + check digit validated), FSSAI
 * licence (14 digits), plus the UPI ID used for the POS payment QR and the
 * receipt header/footer lines. A live 80mm preview renders the real receipt
 * template with sample items.
 *
 * The store is resolved through the signed-in owner, so cross-tenant writes
 * are impossible by construction.
 */
final class StoreSettings extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $pincode = '';

    public bool $gstEnabled = false;

    public string $gstin = '';

    public string $fssai = '';

    public string $upiVpa = '';

    public string $printHeader = '';

    public string $printFooter = '';

    public bool $saved = false;

    private const FIELDS = [
        'name' => 'name', 'phone' => 'phone', 'address' => 'address', 'city' => 'city',
        'state' => 'state', 'pincode' => 'pincode', 'gstin' => 'gstin', 'fssai' => 'fssai_license',
        'upiVpa' => 'upi_vpa', 'printHeader' => 'print_header', 'printFooter' => 'print_footer',
    ];

    public function mount(): void
    {
        $this->authorize('access-admin-portal');

        $store = $this->store();
        foreach (self::FIELDS as $prop => $column) {
            $this->{$prop} = (string) $store->{$column};
        }
        $this->gstEnabled = (bool) $store->is_gst_enabled;
    }

    private function store(): Store
    {
        return auth()->user()->store;
    }

    public function updated(string $property): void
    {
        $this->saved = false;

        if ($property === 'gstin') {
            $this->gstin = strtoupper(trim($this->gstin));
            // Pre-fill the state from the GSTIN state code.
            if (($state = India::stateFromGstin($this->gstin)) && blank($this->state)) {
                $this->state = $state;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'regex:/^[1-9][0-9]{5}$/'],
            'gstEnabled' => ['boolean'],
            'gstin' => [
                $this->gstEnabled ? 'required' : 'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ! India::isValidGstin($value)) {
                        $fail('This GSTIN is not valid — check for a typo (15 characters, e.g. 07ABCDE1234F1Z2).');
                    }
                },
            ],
            'fssai' => ['nullable', 'regex:'.India::FSSAI_REGEX],
            'upiVpa' => ['nullable', 'regex:'.India::VPA_REGEX],
            'printHeader' => ['nullable', 'string', 'max:120'],
            'printFooter' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'pincode.regex' => 'PIN code must be 6 digits.',
            'fssai.regex' => 'FSSAI licence / registration number must be 14 digits.',
            'upiVpa.regex' => 'UPI ID looks wrong — it should look like shopname@okaxis.',
            'gstin.required' => 'GSTIN is required to print GST tax invoices.',
        ];
    }

    public function save(): void
    {
        $this->authorize('access-admin-portal');

        $this->gstin = strtoupper(trim($this->gstin));
        $this->validate();

        $store = $this->store();
        $new = ['is_gst_enabled' => $this->gstEnabled];
        foreach (self::FIELDS as $prop => $column) {
            $new[$column] = trim($this->{$prop}) ?: null;
        }
        $new['name'] = trim($this->name);

        $old = $store->only(array_keys($new));
        $store->update($new);

        $changed = array_keys(array_diff_assoc(array_map('strval', $new), array_map('strval', $old)));
        if ($changed !== []) {
            Audit::record(
                auth()->user(),
                AuditLog::ACTION_STORE_SETTINGS,
                'Store settings updated: '.implode(', ', $changed).'.',
                entityType: 'store',
                entityId: $store->id,
                entityName: $store->name,
                old: array_intersect_key($old, array_flip($changed)),
                new: array_intersect_key($new, array_flip($changed)),
            );
        }

        $this->saved = true;
    }

    /** Sample bill rendered through the real receipt template. */
    public function previewReceipt(): array
    {
        return [
            'order_number' => 'BB-'.now()->format('ymd').'-0042',
            'placed_at' => now()->format('d M Y, h:i A'),
            'order_type' => 'dine_in',
            'table_number' => '4',
            'cashier' => auth()->user()->name,
            'customer_name' => null,
            'customer_phone' => null,
            'items' => [
                ['food_item_name' => 'Paneer Butter Masala', 'quantity' => 1, 'price' => '280.00', 'subtotal' => '280.00'],
                ['food_item_name' => 'Butter Naan', 'quantity' => 3, 'price' => '50.00', 'subtotal' => '150.00'],
                ['food_item_name' => 'Masala Chaas', 'quantity' => 2, 'price' => '60.00', 'subtotal' => '120.00'],
            ],
            'total_quantity' => 6,
            'subtotal' => '550.00',
            'discount_amount' => '0.00',
            'round_off' => '0.00',
            'total_amount' => '550.00',
            'tax_breakup' => [['rate' => 5, 'taxable' => '523.81', 'cgst' => '13.10', 'sgst' => '13.09', 'tax' => '26.19']],
            'amount_in_words' => India::words(550),
            'sac_code' => India::SAC_RESTAURANT,
            'payment_mode' => 'upi',
            'store' => [
                'name' => $this->name, 'address' => $this->address, 'city' => $this->city, 'pincode' => $this->pincode,
                'phone' => $this->phone, 'gstin' => strtoupper($this->gstin), 'fssai' => $this->fssai,
                'gst_enabled' => $this->gstEnabled, 'print_header' => $this->printHeader, 'print_footer' => $this->printFooter,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.store-settings', ['states' => India::STATES]);
    }
}
