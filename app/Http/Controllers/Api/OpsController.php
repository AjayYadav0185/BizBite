<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Services\ReportService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OpsController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly RefundService $refunds,
        private readonly ShiftService $shifts,
        private readonly ReportService $reports,
    ) {}

    private function fail(OrderPlacementException $exception): JsonResponse
    {
        $code = $exception->getCode();

        return response()->json([
            'message' => $exception->getMessage(),
            'error_code' => $code,
        ], $code >= 400 && $code < 500 ? $code : 422);
    }

    public function refund(Request $request, Order $order): JsonResponse
    {
        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:200'],
            'mode' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $order = $this->refunds->issue(
                $request->user(), $order,
                (string) $payload['amount'], $payload['reason'], $payload['mode'] ?? 'cash'
            );
        } catch (OrderPlacementException $exception) {
            return $this->fail($exception);
        }

        return response()->json(['message' => 'Refund issued.', 'order' => $order->load('refunds')]);
    }

    public function delivery(Request $request, Order $order): JsonResponse
    {
        $payload = $request->validate([
            'delivery_status' => ['required', 'string', Rule::in(['pending', 'assigned', 'out', 'delivered', 'failed'])],
            'delivery_agent' => ['nullable', 'string', 'max:80'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->orders->updateDelivery(
                $request->user(), $order,
                $payload['delivery_status'],
                $payload['delivery_agent'] ?? null,
                $payload['delivery_address'] ?? null
            );
        } catch (OrderPlacementException $exception) {
            return $this->fail($exception);
        }

        return response()->json(['message' => 'Delivery updated.', 'order' => $order]);
    }

    public function shifts(): JsonResponse
    {
        return response()->json([
            'shifts' => \App\Models\Shift::query()->with('user:id,name')->latest('opened_at')->limit(30)->get(),
        ]);
    }

    public function openShift(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $shift = $this->shifts->open($request->user(), (string) ($payload['opening_cash'] ?? '0.00'), $payload['notes'] ?? null);
        } catch (OrderPlacementException $exception) {
            return $this->fail($exception);
        }

        return response()->json(['message' => 'Shift opened.', 'shift' => $shift], 201);
    }

    public function closeShift(Request $request, \App\Models\Shift $shift): JsonResponse
    {
        $payload = $request->validate(['closing_cash' => ['nullable', 'numeric', 'min:0']]);

        try {
            $shift = $this->shifts->close($request->user(), $shift, (string) ($payload['closing_cash'] ?? '0.00'));
        } catch (OrderPlacementException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => 'Shift closed.',
            'shift' => $shift,
            'variance' => $this->shifts->variance($shift),
        ]);
    }

    public function hourly(Request $request): JsonResponse
    {
        $payload = $request->validate(['date' => ['nullable', 'date']]);
        $date = $payload['date'] ?? now()->toDateString();

        return response()->json(['date' => $date, 'hourly' => $this->reports->hourly($date)]);
    }

    public function bestSellers(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $today = now()->toDateString();

        return response()->json([
            'best_sellers' => $this->reports->bestSellers(
                $payload['from'] ?? $today, $payload['to'] ?? $today, (int) ($payload['limit'] ?? 10)
            ),
        ]);
    }

    public function range(Request $request): JsonResponse
    {
        $payload = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $today = now()->toDateString();

        return response()->json($this->reports->range($payload['from'] ?? $today, $payload['to'] ?? $today));
    }

    public function tables(): JsonResponse
    {
        return response()->json(['tables' => \App\Models\DiningTable::query()->orderBy('table_number')->get()]);
    }
}
