<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Location;
use App\Models\WaiterCall;
use App\Http\Requests\UpdateOrderStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $status = $request->get('status', 'all');

        $orders = Order::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with('items')
            ->latest()
            ->paginate(15);

        $location = Location::find($activeLocationId);

        $waiterCalls = WaiterCall::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.orders.index', compact('vendor', 'orders', 'location', 'status', 'waiterCalls'));
    }

    public function feed(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);
        $status = $request->get('status', 'all');
        $lastOrderId = (int) $request->get('last_order_id', 0);

        $query = Order::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with('items')
            ->latest();

        $orders = (clone $query)->paginate(15);
        $latestOrderId = Order::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->max('id') ?? 0;

        $pendingCount = Order::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->where('status', 'pending')
            ->count();

        $waiterCalls = WaiterCall::where('vendor_id', $vendor->id)
            ->when($activeLocationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->where('status', 'pending')
            ->latest()
            ->get();

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
        abort_if($order->vendor_id !== auth()->user()->vendor_id, 403);

        $validated = $request->validated();

        $order->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $order->status, 'message' => "Order #{$order->order_number} updated to {$order->status}."]);
        }

        return back()->with('success', "Order #{$order->order_number} status updated to {$order->status}.");
    }

    public function updateWaiterCallStatus(Request $request, WaiterCall $waiterCall)
    {
        abort_if($waiterCall->vendor_id !== auth()->user()->vendor_id, 403);

        $status = $request->input('status', 'attended');
        if (!in_array($status, ['pending', 'attended', 'cancelled'])) {
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
}
