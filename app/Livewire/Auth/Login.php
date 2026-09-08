<?php

namespace App\Livewire\Auth;

use App\Models\Enums\UserRole;
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

        session()->regenerate();

        $this->redirect(
            Auth::user()?->role === UserRole::Admin ? route('admin.dashboard') : route('pos.billing'),
            navigate: true
        );
    }
}