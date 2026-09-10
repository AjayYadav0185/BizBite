<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class Payment extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_payments';

    protected $fillable = [
        'store_id',
        'order_id',
        'mode',
        'amount',
        'status',
        'upi_ref',
        'upi_txn_id',
        'vpa',
        'provider',
        'provider_payload',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
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
}
