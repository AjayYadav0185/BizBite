<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderReceipt;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sanctum API order endpoint for the Phase 2 Flutter application.
 *
 * This controller holds NO business logic: it validates the HTTP payload,
 * injects the SAME {@see OrderService} the Staff POS Livewire component uses,
 * and serializes the resulting {@see OrderReceipt} to JSON.
 * Bill numbering, price snapshotting and transactional integrity are
 * therefore byte-for-byte identical between the web POS and the mobile app.
 */
final class OrderApiController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Save an order placed from the Flutter app.
     *
     * Body (both shapes accepted — the controller normalizes them):
     *   { "payment_mode": "cash",
     *     "items": [{ "id": 12, "quantity": 2 }]            // mobile alias
     *     "items": [{ "food_item_id": 12, "quantity": 2 }]  // canonical
     *   }
     */
    public function store(Request $request): JsonResponse
    {
        // Normalize the mobile payload: accept `id` as a lean alias for the
        // canonical `food_item_id` so the Flutter client can send the
        // documented compact shape {"id": 12, "quantity": 2}.
        $items = collect((array) $request->input('items', []))
            ->filter(fn ($line) => is_array($line))
            ->map(fn (array $line): array => [
                'food_item_id' => $line['food_item_id'] ?? $line['id'] ?? null,
                'quantity' => $line['quantity'] ?? 1,
            ])
            ->values()
            ->all();

        $request->merge(['items' => $items]);

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
