<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Discount / loyalty campaigns (Phase 3: loyalty/discount campaigns).
 *
 * `type` is percent|flat. A bill stores the applied `campaign_code` plus the
 * computed `campaign_discount` rupee amount so reporting stays exact even if
 * the campaign is edited later.
 */
#[ScopedBy(StoreScope::class)]
class Campaign extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_campaigns';

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FLAT = 'flat';

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'type',
        'value',
        'min_order_amount',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** Is this campaign currently redeemable? */
    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Rupee discount for a given subtotal (clamped to the subtotal, honouring
     * the minimum-order gate). Returns a "0.00"-style string.
     */
    public function discountFor(string $subtotal): string
    {
        if (! $this->isLive()) {
            return '0.00';
        }

        if (bccomp($subtotal, (string) $this->min_order_amount, 2) < 0) {
            return '0.00';
        }

        $discount = $this->type === self::TYPE_FLAT
            ? number_format((float) $this->value, 2, '.', '')
            : bcdiv(bcmul($subtotal, (string) $this->value, 4), '100', 2);

        return bccomp($discount, $subtotal, 2) > 0 ? $subtotal : $discount;
    }
}
