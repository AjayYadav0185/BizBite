<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Dining tables for dine-in service (Phase 3: table management).
 *
 * `status` is available|occupied|reserved. `current_order_id` links the
 * live bill sitting on the table so the queue board can show it.
 */
#[ScopedBy(StoreScope::class)]
class DiningTable extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_dining_tables';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_RESERVED = 'reserved';

    protected $fillable = [
        'store_id',
        'table_number',
        'seats',
        'status',
        'current_order_id',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }
}
