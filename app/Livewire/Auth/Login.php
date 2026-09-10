<?php

namespace App\Livewire\Auth;

use App\Models\AuditLog;
use App\Models\Enums\UserRole;
use App\Services\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Shared entry login for BOTH portals.
 *
 * After a successful credential check the user is routed by role:
 *   - admin   -> Owner Admin Portal   (/admin)
 *   - cashier -> Staff POS Portal     (/pos)
 * Admins may also enter the POS (the gates allow it); cashiers can never
 * reach the admin console — enforced by the role gates + middleware.
 */
#[Layout('layouts.guest')]
#[Title('BizBite — Sign in')]
final class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function render()
    {
        return view('livewire.auth.login');
    }

    public function authenticate(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Block deactivated staff accounts at the door (mirrors the same check
        // in Api\AuthController@login). Without this a disabled cashier could
        // keep signing in and placing bills on the web POS.
        if (! $user->is_active) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact the store owner.',
            ]);
        }

        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Audit::record(
            $user,
            AuditLog::ACTION_STAFF_LOGIN,
            $user->name.' signed in on web/POS.',
            entityType: 'session',
            entityName: $user->name,
        );

        $this->redirect(
            Auth::user()?->role === UserRole::Admin ? route('admin.dashboard') : route('pos.billing'),
            navigate: true
        );
    }
}
