<?php

namespace App\Models;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\PaymentMode;
use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'order_number',
        'total_amount',
        'payment_mode',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'payment_mode' => PaymentMode::class,
            'status' => OrderStatus::class,
        ];
    }

    /**
     * Get the store that the order belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Store>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user that created the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items that belong to the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OrderItem>
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}