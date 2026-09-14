<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Partial-refund ledger (Business Plan §5.3 / must-add #3).
 *
 * Cancel-void already exists via OrderService::updateStatus(); this table
 * records money returned WITHOUT voiding the whole bill (partial refund +
 * reason), plus full post-completion refunds. Tenant scoped via StoreScope.
 */
#[ScopedBy(StoreScope::class)]
class Refund extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_refunds';

    protected $fillable = [
        'store_id',
        'order_id',
        'user_id',
        'amount',
        'mode',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
