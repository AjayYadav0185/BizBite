<?php

namespace App\Models;

use App\Models\Enums\UserRole;
use App\Models\Scopes\StoreScope;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ScopedBy(StoreScope::class)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tbl_pos_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'wallet_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Models\Casts\LenientEnumCast::class.':'.UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'wallet_balance' => 'decimal:2',
        ];
    }

    /**
     * Get the store that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Store>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the orders created by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Order>
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Wallet ledger entries for this user (newest first when ordered).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\WalletTransaction>
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Current wallet points as float (1 point = 1 currency unit).
     */
    public function walletBalanceFloat(): float
    {
        return (float) ($this->wallet_balance ?? 0);
    }

    /**
     * Determine if the user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === UserRole::Admin;
    }
}
