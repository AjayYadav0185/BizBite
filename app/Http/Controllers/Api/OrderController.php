<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Unified order entry point.
 *
 * The exact same `CheckoutService` is executed whether the payload arrives
 * from the web POS (Phase 1) or the Flutter app (Phase 2). The controller is
 * API style but is also invoked by the web POS via AJAX/JSON.
 */
class OrderController extends Controller
{
    /**
     * Save an order.
     *
     * Body:
     *   {
     *     "items": [{ "food_item_id": 1, "quantity": 2 }],
     *     "payment_mode": "cash" | "upi" | "card"
     *   }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*.food_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['integer', 'min:1'],
            'payment_mode' => ['in:cash,upi,card'],
        ]);

        $user = $request->user();

        $result = CheckoutService::place($payload, $user);

        return Response::json([
            'message' => 'Order placed successfully.',
            'order_id' => $result['order']->id,
            'order_number' => $result['order']->order_number,
            'total_amount' => $result['total_amount'],
            'items' => $result['items'],
        ], status: 201);
    }
}