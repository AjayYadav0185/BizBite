<?php

namespace App\Models\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Human label for POS / queue badges. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'New',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Still being worked on the kitchen / counter floor — the orders the
     * live OrderQueue board shows by default.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Preparing, self::Ready], strict: true);
    }

    /** The natural next step in the fulfilment flow (null when terminal). */
    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Preparing,
            self::Preparing => self::Ready,
            self::Ready => self::Completed,
            self::Completed, self::Cancelled => null,
        };
    }
}
