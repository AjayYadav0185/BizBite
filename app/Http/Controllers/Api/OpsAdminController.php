<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\DiningTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OpsAdminController extends Controller
{
    public function storeTable(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $number = trim($payload['table_number']);

        if (DiningTable::query()->where('table_number', $number)->exists()) {
            return response()->json(['message' => 'Table '.$number.' already exists.'], 422);
        }

        $table = DiningTable::create([
            'store_id' => $request->user()->store_id,
            'table_number' => $number,
            'seats' => (int) ($payload['seats'] ?? 4),
            'status' => DiningTable::STATUS_AVAILABLE,
        ]);

        return response()->json(['message' => 'Table created.', 'table' => $table], 201);
    }

    public function updateTable(Request $request, DiningTable $table): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['nullable', Rule::in([DiningTable::STATUS_AVAILABLE, DiningTable::STATUS_OCCUPIED, DiningTable::STATUS_RESERVED])],
            'seats' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $table->update(array_filter([
            'status' => $payload['status'] ?? null,
            'seats' => $payload['seats'] ?? null,
        ], fn ($value) => $value !== null));

        if (($payload['status'] ?? null) === DiningTable::STATUS_AVAILABLE) {
            $table->update(['current_order_id' => null]);
        }

        return response()->json(['message' => 'Table updated.', 'table' => $table->fresh()]);
    }

    public function destroyTable(DiningTable $table): JsonResponse
    {
        $table->delete();

        return response()->json(['message' => 'Table deleted.']);
    }

    public function campaigns(): JsonResponse
    {
        return response()->json(['campaigns' => Campaign::query()->orderByDesc('created_at')->limit(100)->get()]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40'],
            'type' => ['required', \Illuminate\Validation\Rule::in([Campaign::TYPE_PERCENT, Campaign::TYPE_FLAT])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $code = strtoupper(trim($payload['code']));

        if (Campaign::query()->where('code', $code)->exists()) {
            return response()->json(['message' => 'Code '.$code.' already exists.'], 422);
        }

        $campaign = Campaign::create([
            'store_id' => $request->user()->store_id,
            'name' => trim($payload['name']),
            'code' => $code,
            'type' => $payload['type'],
            'value' => number_format((float) $payload['value'], 2, '.', ''),
            'min_order_amount' => number_format((float) ($payload['min_order_amount'] ?? 0), 2, '.', ''),
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        return response()->json(['message' => 'Campaign created.', 'campaign' => $campaign], 201);
    }

    public function updateCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', \Illuminate\Validation\Rule::in([Campaign::TYPE_PERCENT, Campaign::TYPE_FLAT])],
            'value' => ['nullable', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $campaign->update(array_filter([
            'name' => isset($payload['name']) ? trim($payload['name']) : null,
            'type' => $payload['type'] ?? null,
            'value' => isset($payload['value']) ? number_format((float) $payload['value'], 2, '.', '') : null,
            'min_order_amount' => isset($payload['min_order_amount']) ? number_format((float) $payload['min_order_amount'], 2, '.', '') : null,
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : null,
        ], fn ($value) => $value !== null));

        return response()->json(['message' => 'Campaign updated.', 'campaign' => $campaign->fresh()]);
    }

    public function destroyCampaign(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted.']);
    }

    public function staff(): JsonResponse
    {
        return response()->json([
            'staff' => \App\Models\User::query()->orderBy('name')->get(['id', 'name', 'email', 'phone', 'role', 'is_active', 'last_login_at']),
        ]);
    }

    public function storeStaff(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);
        $email = strtolower(trim($payload['email']));

        if (\App\Models\User::withoutGlobalScopes()->where('email', $email)->exists()) {
            return response()->json(['message' => 'That email is already registered.'], 422);
        }

        $user = \App\Models\User::withoutGlobalScopes()->create([
            'store_id' => $request->user()->store_id,
            'name' => trim($payload['name']),
            'email' => $email,
            'phone' => isset($payload['phone']) ? substr(trim($payload['phone']), 0, 20) ?: null : null,
            'password' => \Illuminate\Support\Facades\Hash::make($payload['password']),
            'role' => \App\Models\Enums\UserRole::Cashier,
            'is_active' => true,
        ]);

        \App\Services\Audit::record(
            $request->user(), \App\Models\AuditLog::ACTION_STAFF_CREATED,
            'Staff member '.$user->name.' ('.$email.') added.',
            entityType: 'user', entityId: $user->id, entityName: $user->name,
        );

        return response()->json(['message' => 'Cashier added.', 'user' => $user], 201);
    }

    public function updateStaff(Request $request, \App\Models\User $user): JsonResponse
    {
        $payload = $request->validate(['is_active' => ['required', 'boolean']]);

        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update(['is_active' => (bool) $payload['is_active']]);

        \App\Services\Audit::record(
            $request->user(), \App\Models\AuditLog::ACTION_STAFF_DEACTIVATED,
            'Staff member '.$user->name.' '.($user->is_active ? 'reactivated' : 'deactivated').'.',
            entityType: 'user', entityId: $user->id, entityName: $user->name,
        );

        return response()->json(['message' => 'Staff updated.', 'user' => $user->fresh()]);
    }
}
