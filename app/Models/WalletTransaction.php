<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer Wallet ledger — one row per points movement.
 *
 * Conventions: `tbl_pos_` prefix, signed `amount` (credit +, debit -),
 * `type` credit/debit, free-text `description` + `reference_id`
 * (bill id or razorpay payment id).
 */
class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_wallet_transactions';

    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const DESC_SIGNUP_BONUS = 'Sign-up bonus';

    public const DESC_BILL_DEDUCTION = 'Bill deduction';

    public const DESC_RECHARGE = 'Razorpay recharge';

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'reference_id',
        'razorpay_order_id',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
