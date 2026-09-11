<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Razorpay recharge intent (initiate -> verify lifecycle).
 */
class WalletRecharge extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_wallet_recharges';

    protected $fillable = [
        'user_id',
        'amount',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
