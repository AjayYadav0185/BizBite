<?php

namespace App\Models;

use App\Models\Scopes\OrderStoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Order items are snapshots of the sold food item. Because they carry no
 * `store_id` column of their own, tenancy is enforced through the parent
 * order via the `OrderStoreScope` global scope.
 */
#[ScopedBy(OrderStoreScope::class)]
class OrderItem extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'food_item_name',
        'quantity',
        'price',
        'subtotal',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the order that the item belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Order>
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}