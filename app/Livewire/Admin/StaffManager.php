<?php

namespace App\Livewire\Admin;

use App\Models\Enums\UserRole;
use App\Models\User;
use App\Services\Audit;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * OWNER ADMIN — staff management (must-add #5: staff + shifts).
 *
 * The owner adds cashiers (name/email/phone/password), deactivates leavers,
 * and reviews who is behind the counter. Shift open/close lives on the POS
 * ShiftPanel; every open/close is audited for the owner trail.
 */
#[Layout('layouts.app')]
final class StaffManager extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public ?string $error = null;

    public ?string $success = null;

    public function mount(): void
    {
        $this->authorize('access-admin-portal');
    }

    #[Computed]
    public function staff()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email', 'phone', 'role', 'is_active', 'last_login_at']);
    }

    public function create(): void
    {
        $this->authorize('access-admin-portal');
        $this->reset('error', 'success');

        $name = substr(trim($this->name), 0, 100);
        $email = strtolower(trim($this->email));

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($this->password) < 6) {
            $this->error = 'Name, a valid email and a 6+ character password are required.';

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error = 'That email is already registered.';

            return;
        }

        $user = User::withoutGlobalScopes()->create([
            'store_id' => auth()->user()->store_id,
            'name' => $name,
            'email' => $email,
            'phone' => substr(trim($this->phone), 0, 20) ?: null,
            'password' => Hash::make($this->password),
            'role' => UserRole::Cashier,
            'is_active' => true,
        ]);

        Audit::record(
            auth()->user(), AuditLog::ACTION_STAFF_CREATED,
            'Staff member '.$name.' ('.$email.') added.',
            entityType: 'user', entityId: $user->id, entityName: $name,
        );

        $this->success = $name.' added as cashier.';
        $this->reset('name', 'email', 'phone', 'password');
        unset($this->staff);
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('access-admin-portal');

        $user = User::query()->findOrFail($id);

        if ((int) $user->id === (int) auth()->id()) {
            $this->error = 'You cannot deactivate your own account.';

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        Audit::record(
            auth()->user(), AuditLog::ACTION_STAFF_DEACTIVATED,
            'Staff member '.$user->name.' '.($user->is_active ? 'reactivated' : 'deactivated').'.',
            entityType: 'user', entityId: $user->id, entityName: $user->name,
        );
        unset($this->staff);
    }

    public function render()
    {
        return view('livewire.admin.staff-manager');
    }
}
