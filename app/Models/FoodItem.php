<?php

namespace App\Models;

use App\Models\Enums\FoodType;
use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class FoodItem extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_food_items';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'price',
        'is_available',
        'stock_quantity',
        'low_stock_threshold',
        'food_type',
        'gst_rate',
        'sort_order',
        'uuid',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'food_type' => FoodType::class,
            'gst_rate' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Is stock counting enabled for this item?
     *
     * A NULL `stock_quantity` means "not tracked" (unlimited / made to order),
     * which is the default so existing menu items keep selling unchanged.
     */
    public function tracksStock(): bool
    {
        return $this->stock_quantity !== null;
    }

    /** Tracked item with nothing left on the shelf. */
    public function isOutOfStock(): bool
    {
        return $this->tracksStock() && $this->stock_quantity <= 0;
    }

    /**
     * Tracked item that is still sellable but at or below the owner's
     * re-order threshold — the rows the admin low-stock alert surfaces.
     */
    public function isLowStock(): bool
    {
        return $this->tracksStock()
            && $this->stock_quantity > 0
            && $this->stock_quantity <= (int) $this->low_stock_threshold;
    }

    /** Can this item satisfy the requested quantity right now? */
    public function canFulfil(int $quantity): bool
    {
        return $this->is_available
            && (! $this->tracksStock() || $this->stock_quantity >= $quantity);
    }

    /**
     * Get the store that the food item belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Store>
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the category that the food item belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category>
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}