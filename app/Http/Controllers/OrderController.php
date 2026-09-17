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

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,preparing,ready,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $order->status]);
        }

        return back()->with('success', "Order #{$order->order_number} status updated to {$order->status}.");
    }
}
