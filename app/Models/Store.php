<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    /**
     * Every business + framework table uses the `tbl_` prefix.
     */
    protected $table = 'tbl_stores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'pincode',
        'gstin',
        'fssai_license',
        'upi_vpa',
        'currency',
        'default_gst_rate',
        'is_gst_enabled',
        'is_active',
        'print_header',
        'print_footer',
        'logo_path',
        'owner_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_gst_rate' => 'decimal:2',
            'is_gst_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

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

    /**
     * Get the registered Flutter/POS devices for the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\StoreDevice>
     */
    public function devices()
    {
        return $this->hasMany(StoreDevice::class);
    }

    /**
     * Get the payments collected for the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Payment>
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the audit trail for the store (owner eyes only via gate).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\AuditLog>
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
