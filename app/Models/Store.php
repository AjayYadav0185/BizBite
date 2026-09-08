<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'address',
        'print_header',
        'print_footer',
    ];

    /**
     * Get the users that belong to the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\User>
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the categories that belong to the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Category>
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the food items that belong to the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\FoodItem>
     */
    public function foodItems()
    {
        return $this->hasMany(FoodItem::class);
    }

    /**
     * Get the orders that belong to the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Order>
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}