<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enums\OrderStatus;
use App\Models\Enums\OrderType;
use App\Models\Order;
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
     *   { "payment_mode": "upi", "order_type": "takeaway",
     *     "discount_amount": "20.00",
     *     "customer_name": "Amit", "customer_phone": "98110XXXXX",
     *     "upi_ref": "UTR / UPI txn id",
     *     "idempotency_key": "client-generated uuid (safe to retry)",
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
            'payment_mode' => ['in:cash,upi,card,credit,split'],
            'order_type' => ['in:dine_in,takeaway,parcel,delivery'],
            'discount_amount' => ['numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'string', 'max:15'],
            'notes' => ['nullable', 'string', 'max:200'],
            'upi_ref' => ['nullable', 'string', 'max:60'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'campaign_code' => ['nullable', 'string', 'max:40'],
            'tendered_amount' => ['nullable', 'numeric', 'min:0'],
            'split_details' => ['nullable', 'array'],
            'split_details.*.mode' => ['required_with:split_details', 'in:cash,upi,card,credit,split'],
            'split_details.*.amount' => ['required_with:split_details', 'numeric', 'min:0.01'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'delivery_agent' => ['nullable', 'string', 'max:80'],
            'delivery_status' => ['nullable', 'in:pending,assigned,out,delivered,failed'],
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

    /**
     * Today's bills for the caller's store — the kitchen/counter queue.
     *
     * Query string:
     *   ?status=open   → only pending / preparing / ready (kitchen floor)
     *   ?type=dine_in|takeaway|parcel|delivery → the §4.5 order-type split
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');

        $orders = Order::query()
            ->with(['items', 'user:id,name'])
            ->whereDate('created_at', now()->toDateString())
            ->when($request->query('status') === 'open', fn ($query) => $query->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
            ]))
            ->when(
                is_string($type) && in_array($type, array_column(OrderType::cases(), 'value'), strict: true),
                fn ($query) => $query->where('order_type', $type)
            )
            ->oldest('created_at')
            ->limit(100)
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Advance or void a bill (§4.4 order status flow).
     *
     * Body: { "status": "preparing" | "ready" | "completed" | "cancelled" }
     *
     * Tenant isolation is structural: `{order}` is resolved through the
     * StoreScope-decorated Order model, so another store's bill 404s.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:pending,preparing,ready,completed,cancelled'],
        ]);

        try {
            $order = $this->orders->updateStatus(
                $request->user(),
                $order,
                OrderStatus::from($payload['status'])
            );
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
            'message' => 'Order status updated.',
            'order' => $order,
        ]);
    }
}
