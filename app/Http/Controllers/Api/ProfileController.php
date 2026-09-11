<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

/**
 * Self-service profile management for the mobile app (cashier + admin).
 *
 * Endpoints:
 *   GET  /api/profile            → own profile (never store_id writable)
 *   PUT  /api/profile            → update name / phone
 *   PUT  /api/profile/password   → change password (re-checks current one)
 *
 * Rules mirrored from the web portal staff management:
 *   - email is intentionally NOT self-editable (it is the login credential;
 *     the store owner changes it via staff management).
 *   - role / store_id / is_active are never accepted from the client.
 *   - every mutation lands in the tenant audit trail.
 */
class ProfileController extends Controller
{
    /**
     * GET /api/profile — the caller's own profile block.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return Response::json([
            'user' => $this->serialize($user),
        ]);
    }

    /**
     * PUT /api/profile — update own display name and phone number.
     *
     * Body: { "name": "...", "phone": "...|(omitted to clear)" }
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $old = ['name' => $user->name, 'phone' => $user->phone];

        $user->name = trim($data['name']);
        $user->phone = $data['phone'] !== null ? trim($data['phone']) : null;
        $user->save();

        Audit::record(
            $user,
            AuditLog::ACTION_PROFILE_UPDATED,
            $user->name.' updated their own profile details.',
            entityType: 'user',
            entityId: $user->id,
            entityName: $user->name,
            old: $old,
            new: ['name' => $user->name, 'phone' => $user->phone],
        );

        return Response::json([
            'message' => 'Profile updated successfully.',
            'user' => $this->serialize($user->fresh()),
        ]);
    }

    /**
     * PUT /api/profile/password — change own password.
     *
     * Body: { "current_password": "...", "password": "...", "password_confirmation": "..." }
     */
    public function password(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return Response::json([
                'message' => 'Your current password is incorrect.',
                'errors' => ['current_password' => ['Your current password is incorrect.']],
            ], status: 422);
        }

        $user->password = $data['password'];
        $user->save();

        Audit::record(
            $user,
            AuditLog::ACTION_PROFILE_UPDATED,
            $user->name.' changed their own password.',
            entityType: 'user',
            entityId: $user->id,
            entityName: $user->name,
        );

        return Response::json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Consistent profile shape (matches the login payload + GET /api/user).
     */
    private function serialize(?User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'store_id' => $user->store_id,
        ];
    }
}