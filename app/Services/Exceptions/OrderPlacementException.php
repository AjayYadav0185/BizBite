<?php

namespace App\Services\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown by {@see \App\Services\OrderService} whenever a checkout cannot be
 * safely committed (empty cart, cross-tenant item ids, unavailable menu item,
 * stale price data, etc.).
 *
 * Callers (Livewire POS component / API controller) catch this exception and
 * surface the message directly to the cashier or the Flutter client.
 */
class OrderPlacementException extends RuntimeException
{
    /**
     * Create a new order placement exception.
     *
     * @param  string  $message   Human readable, cashier-safe explanation.
     * @param  int  $code         Optional machine readable error code.
     * @param  \Throwable|null  $previous  Wrapped underlying failure.
     */
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Convenience constructor for an empty cart rejection.
     */
    public static function emptyCart(): self
    {
        return new self('Cannot settle an empty cart. Add at least one item.', 422);
    }

    /**
     * Convenience constructor for items that are missing, not part of the
     * current tenant's menu, or currently toggled off availability.
     *
     * @param  list<string>  $names
     */
    public static function unavailableItems(array $names): self
    {
        return new self(
            'These items are no longer available: '.implode(', ', $names).'. Please refresh the menu.',
            409
        );
    }
}