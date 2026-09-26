<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Location;
use App\Models\Order;
use App\Models\WaiterCall;
use App\Services\OrderService;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $status = $request->get('status', 'all');

        $orders = $this->orderService->getOrders($activeLocationId, $status);
        $location = Location::find($activeLocationId);
        $waiterCalls = $this->orderService->getPendingWaiterCalls($activeLocationId);

        return view('admin.orders.index', compact('vendor', 'orders', 'location', 'status', 'waiterCalls'));
    }

    public function feed(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $status = $request->get('status', 'all');
        $lastOrderId = (int) $request->get('last_order_id', 0);

        $orders = $this->orderService->getOrders($activeLocationId, $status);
        $latestOrderId = $this->orderService->getLatestOrderId($activeLocationId);
        $pendingCount = $this->orderService->getPendingOrdersCount($activeLocationId);

        $waiterCalls = $this->orderService->getPendingWaiterCalls($activeLocationId);
        $waiterCallsCount = $waiterCalls->count();
        $waiterCallsHtml = view('admin.orders.partials.waiter_call_cards', compact('waiterCalls'))->render();

        $hasNew = ($lastOrderId > 0 && $latestOrderId > $lastOrderId);

        $html = view('admin.orders.partials.order_cards', compact('orders', 'vendor'))->render();

        return response()->json([
            'success' => true,
            'latest_order_id' => $latestOrderId,
            'pending_count' => $pendingCount,
            'waiter_calls_count' => $waiterCallsCount,
            'waiter_calls_html' => $waiterCallsHtml,
            'has_new' => $hasNew,
            'html' => $html,
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $validated = $request->validated();

        $order->update(['status' => $validated['status']]);

        // Broadcast real-time status update for kitchen screens & customer tracker
        try {
            event(new OrderStatusUpdated($order));
        } catch (\Throwable $e) {
            Log::warning('OrderStatusUpdated broadcast failed: '.$e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $order->status, 'message' => "Order #{$order->order_number} updated to {$order->status}."]);
        }

        return back()->with('success', "Order #{$order->order_number} status updated to {$order->status}.");
    }

    public function updateWaiterCallStatus(Request $request, WaiterCall $waiterCall)
    {
        $status = $request->input('status', 'attended');
        if (! in_array($status, ['pending', 'attended', 'cancelled'])) {
            $status = 'attended';
        }

        $waiterCall->update(['status' => $status]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $waiterCall->status,
                'message' => "Սեղան #{$waiterCall->table_number}-ի կանչը նշվեց որպես սպասարկված։",
            ]);
        }

        return back()->with('success', "Սեղան #{$waiterCall->table_number}-ի կանչը նշվեց որպես սպասարկված։");
    }

    /**
     * Get ESC/POS formatted receipt text and RawBT URL for thermal printing.
     */
    public function receiptText(Order $order, ThermalPrinterService $printerService)
    {
        $order->load(['items.product', 'location', 'vendor']);

        $receiptText = $printerService->generateReceiptText($order);
        $rawBtUrl = $printerService->getRawBtUrl($order);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'receipt_text' => $receiptText,
            'rawbt_url' => $rawBtUrl,
        ]);
    }
}
