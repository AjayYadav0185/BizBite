<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Services\Audit;
use App\Support\StorePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Store profile & branding for the Flutter app (the "About shop" /
 * "Manage shop" block on the My Profile screen).
 *
 * Endpoints:
 *   GET    /api/store        → own store profile + branding (all staff)
 *   PUT    /api/store        → update shop details (owner only)
 *   POST   /api/store/logo   → upload/replace the shop logo (owner only)
 *   DELETE /api/store/logo   → remove the shop logo (owner only)
 *
 * TENANCY: `Store` is intentionally NOT tenant-scoped (it is the tenant). It
 * is always resolved through the authenticated user's own `store()` relation,
 * so a cross-tenant write is impossible by construction — no store id is ever
 * accepted from the client. Every write lands in the owner-visible audit
 * trail (ACTION_STORE_SETTINGS), exactly like the web Receipt Customizer.
 */
class StoreController extends Controller
{
    /**
     * GET /api/store — the caller's store profile (read-only for cashiers).
     */
    public function show(Request $request): JsonResponse
    {
        $store = $this->storeFor($request->user());

        abort_if($store === null, 404, 'No store is linked to this account.');

        return response()->json([
            'store' => StorePayload::for($store),
        ]);
    }

    /**
     * PUT /api/store — update the shop details.
     *
     * Body (every key optional; only the keys present are written):
     *   { "name", "phone", "alternate_phone", "address", "city", "state",
     *     "pincode", "gstin", "fssai_license", "upi_vpa", "currency",
     *     "default_gst_rate", "is_gst_enabled", "print_header",
     *     "print_footer" }
     *
     * Empty strings are normalized to NULL so a cleared field really clears
     * (mirrors the Receipt Customizer's `trim(...) ?: null`).
     */
    public function update(Request $request): JsonResponse
    {
        $store = $this->storeFor($request->user());

        abort_if($store === null, 404, 'No store is linked to this account.');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'gstin' => ['nullable', 'string', 'max:15'],
            'fssai_license' => ['nullable', 'string', 'max:30'],
            'upi_vpa' => ['nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'default_gst_rate' => ['nullable', 'numeric', 'min:0', 'max:28'],
            'is_gst_enabled' => ['nullable', 'boolean'],
            'print_header' => ['nullable', 'string', 'max:120'],
            'print_footer' => ['nullable', 'string', 'max:120'],
        ]);

        // Text columns are stored trimmed; blank strings clear the column.
        $textKeys = [
            'name', 'phone', 'alternate_phone', 'address', 'city', 'state',
            'pincode', 'gstin', 'fssai_license', 'upi_vpa', 'currency',
            'print_header', 'print_footer',
        ];

        // Before-values for the audit row (only the keys being written).
        $old = $store->only(array_keys($data));

        foreach ($data as $key => $value) {
            if (in_array($key, $textKeys, true)) {
                // `currency` is NOT NULL on the schema: a blank submission
                // silently keeps the store's current currency instead of
                // 500-ing on the NOT NULL constraint.
                if ($key === 'currency') {
                    $trimmed = trim((string) $value);
                    $store->currency = $trimmed === ''
                        ? ($store->currency ?: 'INR')
                        : strtoupper($trimmed);
                    continue;
                }

                $store->{$key} = $this->text($value);
            } elseif ($key === 'default_gst_rate') {
                $store->default_gst_rate = $value === null
                    ? null
                    : number_format((float) $value, 2, '.', '');
            } elseif ($key === 'is_gst_enabled') {
                $store->is_gst_enabled = (bool) $value;
            }
        }

        $dirty = $store->getDirty();
        $store->save();

        if ($dirty !== []) {
            Audit::record(
                $request->user(),
                AuditLog::ACTION_STORE_SETTINGS,
                'Shop profile updated.',
                entityType: 'store',
                entityId: $store->id,
                entityName: $store->name,
                old: $old,
                new: $store->only(array_keys($dirty)),
            );
        }

        return response()->json([
            'message' => 'Shop details updated.',
            'store' => StorePayload::for($store->fresh()),
        ]);
    }

    /**
     * POST /api/store/logo — upload (or replace) the shop logo.
     *
     * Multipart body: { "logo": <jpg|jpeg|png|webp, max 2 MB> }
     *
     * The file lands on the `public` disk (`storage/app/public/store-logos`)
     * and the previous file is deleted so a store never leaks orphaned
     * uploads. Run `php artisan storage:link` once per environment so
     * `logo_url` resolves.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $store = $this->storeFor($request->user());

        abort_if($store === null, 404, 'No store is linked to this account.');

        $request->validate([
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $previous = $store->logo_path;

        $path = $request->file('logo')->store('store-logos', 'public');

        if (! is_string($path) || $path === '') {
            return response()->json([
                'message' => 'The logo could not be saved. Please try again.',
            ], 500);
        }

        $store->logo_path = $path;
        $store->save();

        // Best effort: a leftover file must never fail the upload.
        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        Audit::record(
            $request->user(),
            AuditLog::ACTION_STORE_SETTINGS,
            'Shop logo updated.',
            entityType: 'store',
            entityId: $store->id,
            entityName: $store->name,
            old: ['logo_path' => $previous],
            new: ['logo_path' => $path],
        );

        return response()->json([
            'message' => 'Shop logo updated.',
            'store' => StorePayload::for($store->fresh()),
        ]);
    }

    /**
     * DELETE /api/store/logo — drop the uploaded logo (the app then falls back
     * to the store monogram avatar).
     */
    public function destroyLogo(Request $request): JsonResponse
    {
        $store = $this->storeFor($request->user());

        abort_if($store === null, 404, 'No store is linked to this account.');

        $previous = $store->logo_path;

        $store->logo_path = null;
        $store->save();

        if (is_string($previous) && $previous !== '') {
            Storage::disk('public')->delete($previous);
        }

        Audit::record(
            $request->user(),
            AuditLog::ACTION_STORE_SETTINGS,
            'Shop logo removed.',
            entityType: 'store',
            entityId: $store->id,
            entityName: $store->name,
            old: ['logo_path' => $previous],
            new: ['logo_path' => null],
        );

        return response()->json([
            'message' => 'Shop logo removed.',
            'store' => StorePayload::for($store->fresh()),
        ]);
    }

    /**
     * The authenticated user's own store — the only store this controller can
     * ever read or write.
     */
    private function storeFor(?User $user): ?Store
    {
        return $user?->store()->first();
    }

    /**
     * Trim a text value, collapsing "" / null into NULL so a cleared field
     * really clears.
     */
    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
