<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Facades\Storage;

/**
 * Canonical JSON shape of a store's profile / branding block.
 *
 * Every endpoint that embeds the store (`POST /api/login`, `GET /api/menu`,
 * `GET|PUT /api/store`) serializes through here, so the Flutter
 * `StoreProfileModel` always receives the same keys no matter which screen
 * fetched it — the mobile "About shop" card, receipt templates and the POS
 * hero card all read the identical contract.
 */
final class StorePayload
{
    /**
     * Serialize a store (or null when the account has no store linked).
     *
     * @return array<string, mixed>|null
     */
    public static function for(?Store $store): ?array
    {
        if ($store === null) {
            return null;
        }

        return [
            'id' => $store->id,
            'name' => $store->name,
            'phone' => $store->phone,
            'alternate_phone' => $store->alternate_phone,
            'address' => $store->address,
            'city' => $store->city,
            'state' => $store->state,
            'pincode' => $store->pincode,
            'gstin' => $store->gstin,
            'fssai_license' => $store->fssai_license,
            'upi_vpa' => $store->upi_vpa,
            'currency' => $store->currency ?? 'INR',
            'default_gst_rate' => (string) ($store->default_gst_rate ?? '0.00'),
            'is_gst_enabled' => (bool) $store->is_gst_enabled,
            'print_header' => $store->print_header,
            'print_footer' => $store->print_footer,
            'logo_path' => $store->logo_path,
            'logo_url' => self::logoUrl($store),
            'updated_at' => $store->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Publicly reachable URL of the store logo (null when none uploaded).
     *
     * `logo_path` is stored as a relative path on the `public` disk
     * (`storage/app/public/store-logos/...`); an absolute URL already stored
     * by an operator is returned untouched.
     */
    public static function logoUrl(Store $store): ?string
    {
        $path = $store->logo_path;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
