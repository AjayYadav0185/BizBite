<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sanctum API order endpoint for the Phase 2 Flutter application.
 *
 * This controller holds NO business logic: it validates the HTTP payload,
 * injects the SAME {@see OrderService} the Staff POS Livewire component uses,
 * and serializes the resulting {@see \App\Services\OrderReceipt} to JSON.
 * Bill numbering, price snapshotting and transactional integrity are
 * therefore byte-for-byte identical between the web POS and the mobile app.
 */
final class OrderApiController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Save an order placed from the Flutter app.
     *
     * Body:
     *   {
     *     "items": [{ "food_item_id": 1, "quantity": 2 }],
     *     "payment_mode": "cash" | "upi" | "card"
     *   }
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.food_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['integer', 'min:1'],
            'payment_mode' => ['in:cash,upi,card'],
        ]);

        try {
            $receipt = $this->orders->place($request->user(), $payload);
        } catch (OrderPlacementException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
            ], $exception->getCode() >= 400 && $exception->getCode() < 500
                ? $exception->getCode()
                : 422
            );
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            ...$receipt->toArray(),
        ], status: 201);
    }
}