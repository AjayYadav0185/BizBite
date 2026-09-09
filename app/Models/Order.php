<?php

namespace App\Models;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\OrderType;
use App\Models\Enums\PaymentMode;
use App\Models\Enums\PaymentStatus;
use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class Order extends Model
{
    use HasFactory;

    protected $table = 'tbl_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'order_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'round_off',
        'total_amount',
        'payment_mode',
        'payment_status',
        'status',
        'order_type',
        'upi_ref',
        'invoice_number',
        'customer_name',
        'customer_phone',
        'idempotency_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_mode' => PaymentMode::class,
            'payment_status' => PaymentStatus::class,
            'status' => OrderStatus::class,
            'order_type' => OrderType::class,
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

    /**
     * Get the payment attempts for the order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Payment>
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
