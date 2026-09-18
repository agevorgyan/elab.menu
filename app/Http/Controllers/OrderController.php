<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Location;
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

        return view('admin.orders.index', compact('vendor', 'orders', 'location', 'status'));
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

        $hasNew = ($lastOrderId > 0 && $latestOrderId > $lastOrderId);

        $html = view('admin.orders.partials.order_cards', compact('orders', 'vendor'))->render();

        return response()->json([
            'success' => true,
            'latest_order_id' => $latestOrderId,
            'pending_count' => $pendingCount,
            'has_new' => $hasNew,
            'html' => $html,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Tenant security check
        if ($order->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized order action.');
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,preparing,ready,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $order->status, 'message' => "Order #{$order->order_number} updated to {$order->status}."]);
        }

        return back()->with('success', "Order #{$order->order_number} status updated to {$order->status}.");
    }
}
