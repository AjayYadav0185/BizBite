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

class AuthController extends Controller
{
    /**
     * Log a user in and issue a Sanctum personal access token for the
     * Flutter app (Phase 2).
     *
     * Body:
     *   { "email": "cashier@store.local", "password": "secret" }
     *
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return Response::json([
                'message' => 'Invalid credentials.',
            ], status: 422);
        }

        abort_if(! $user->is_active, Response::json([
            'message' => 'Your account has been deactivated. Please contact the store owner.',
        ], status: 403));

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Audit::record(
            $user,
            AuditLog::ACTION_STAFF_LOGIN,
            $user->name.' signed in'.($request->filled('device_id') ? ' from device '.$request->input('device_id') : ' on web/POS').'.',
            entityType: 'session',
            entityName: $user->name,
        );

        // Flutter Phase 2: bind the device (FCM push + offline sync bookkeeping).
        $request->validate([
            'device_id' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:20'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        if ($request->filled('device_id')) {
            \App\Models\StoreDevice::updateOrCreate(
                ['store_id' => $user->store_id, 'device_id' => $request->string('device_id')],
                [
                    'user_id' => $user->id,
                    'platform' => $request->input('platform', 'android'),
                    'app_version' => $request->input('app_version'),
                    'fcm_token' => $request->input('fcm_token'),
                    'last_sync_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        $deviceLabel = $request->input('device_id') ? 'mobile:'.$request->input('device_id') : 'mobile';
        $newToken = $user->createToken($deviceLabel, ['*']);

        return Response::json([
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'store_id' => $user->store_id,
            ],
            'store' => $user->store ? [
                'id' => $user->store->id,
                'name' => $user->store->name,
                'upi_vpa' => $user->store->upi_vpa,
                'currency' => $user->store->currency ?? 'INR',
            ] : null,
        ]);
    }

    /**
     * Revoke the Sanctum token used for the current request (Flutter logout).
     *
     * Safe to call with any verb (GET/POST/DELETE) and safe to call twice:
     * if the token was already revoked, we still return success so the
     * Flutter client can always clear local state without a 405/500.
     */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return Response::json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
