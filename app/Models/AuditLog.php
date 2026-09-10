<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Owner-visible audit log.
 *
 * Tenant-scoped via StoreScope (admins only ever see their own store's logs)
 * and additionally protected by the `view-audit-logs` gate. There is
 * deliberately NO public API route exposing this model.
 */
#[ScopedBy(StoreScope::class)]
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_audit_logs';

    public const ACTION_PRICE_UPDATED = 'price_updated';
    public const ACTION_ITEM_CREATED = 'item_created';
    public const ACTION_ITEM_DELETED = 'item_deleted';
    public const ACTION_ITEM_AVAILABILITY = 'item_availability';
    public const ACTION_CATEGORY_CREATED = 'category_created';
    public const ACTION_CATEGORY_UPDATED = 'category_updated';
    public const ACTION_CATEGORY_DELETED = 'category_deleted';
    public const ACTION_ORDER_DISCOUNT = 'order_discount';
    public const ACTION_ORDER_CANCELLED = 'order_cancelled';
    public const ACTION_CREDIT_BILL = 'credit_bill';
    public const ACTION_PAYMENT_SETTLED = 'payment_settled';
    public const ACTION_STORE_SETTINGS = 'store_settings';
    public const ACTION_STAFF_LOGIN = 'staff_login';
    public const ACTION_STAFF_CREATED = 'staff_created';
    public const ACTION_STAFF_DEACTIVATED = 'staff_deactivated';

    protected $fillable = [
        'store_id',
        'user_id',
        'user_name',
        'action',
        'entity_type',
        'entity_id',
        'entity_name',
        'description',
        'old_values',
        'new_values',
        'amount',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
