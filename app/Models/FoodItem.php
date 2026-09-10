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
            'food_type' => FoodType::class,
            'gst_rate' => 'integer',
            'sort_order' => 'integer',
        ];
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