<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff shift / cash-drawer session (Business Plan: staff shifts).
 *
 * One open shift per staff member at a time. Opening cash is counted at
 * handover; expected cash = opening + cash sales during the shift; the
 * closing variance tells the owner if the drawer is short/over.
 */
#[ScopedBy(StoreScope::class)]
class Shift extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_shifts';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'store_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        // The shift owner is authoritative via `user_id`; the shift row itself
        // is already tenant-scoped. Re-filtering the owner through StoreScope
        // would return null whenever the current tenant context differs
        // (e.g. an admin closing a shift, or a stale request context), which
        // crashed ShiftService::close() with "read property on null".
        return $this->belongsTo(User::class)->withoutGlobalScope(StoreScope::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
