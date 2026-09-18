<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $locations = $vendor->locations;

        $activeLocationId = $request->get('location_id', session('active_location_id', $locations->first()?->id));
        if ($request->has('location_id')) {
            session(['active_location_id' => $activeLocationId]);
        }

        $selectedLocation = ($activeLocationId && $activeLocationId !== 'all') 
            ? $locations->firstWhere('id', $activeLocationId) 
            : null;

        $search = $request->get('search');
        $consentFilter = $request->get('marketing');

        $query = Customer::where('vendor_id', $vendor->id)->with('location');

        if ($activeLocationId && $activeLocationId !== 'all') {
            $query->where(function ($q) use ($activeLocationId) {
                $q->where('location_id', $activeLocationId)
                  ->orWhereHas('orders', function ($oq) use ($activeLocationId) {
                      $oq->where('location_id', $activeLocationId);
                  });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($consentFilter === 'yes') {
            $query->where('marketing_opt_in', true);
        } elseif ($consentFilter === 'no') {
            $query->where('marketing_opt_in', false);
        }

        $customers = $query->orderBy('updated_at', 'desc')->paginate(15);

        // Stats summary filtered by active location
        $statsQuery = Customer::where('vendor_id', $vendor->id);
        if ($activeLocationId && $activeLocationId !== 'all') {
            $statsQuery->where(function ($q) use ($activeLocationId) {
                $q->where('location_id', $activeLocationId)
                  ->orWhereHas('orders', function ($oq) use ($activeLocationId) {
                      $oq->where('location_id', $activeLocationId);
                  });
            });
        }

        $totalCustomers = (clone $statsQuery)->count();
        $optedInCustomers = (clone $statsQuery)->where('marketing_opt_in', true)->count();

        if ($activeLocationId && $activeLocationId !== 'all') {
            $totalRevenue = Order::where('vendor_id', $vendor->id)
                ->where('location_id', $activeLocationId)
                ->sum('total_amount');
        } else {
            $totalRevenue = Customer::where('vendor_id', $vendor->id)->sum('total_spent');
        }

        return view('admin.customers.index', compact(
            'vendor',
            'locations',
            'activeLocationId',
            'selectedLocation',
            'customers',
            'totalCustomers',
            'optedInCustomers',
            'totalRevenue',
            'search',
            'consentFilter'
        ));
    }

    public function show(Customer $customer)
    {
        $vendor = Auth::user()->vendor;
        if ($customer->vendor_id !== $vendor->id) {
            abort(403);
        }

        $customer->load('location');
        $customer->recalculateStats();
        $orders = $customer->orders()->with('items', 'location')->paginate(20);

        return view('admin.customers.show', compact('vendor', 'customer', 'orders'));
    }

    public function store(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        $activeLocationId = session('active_location_id', $vendor->locations->first()?->id);

        Customer::create([
            'vendor_id' => $vendor->id,
            'location_id' => $validated['location_id'] ?? $activeLocationId,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'marketing_opt_in' => $request->has('marketing_opt_in'),
        ]);

        return back()->with('success', 'Customer added successfully!');
    }

    public function update(Request $request, Customer $customer)
    {
        $vendor = Auth::user()->vendor;
        if ($customer->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        $customer->update([
            'name' => $validated['name'],
            'location_id' => $validated['location_id'] ?? $customer->location_id,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'marketing_opt_in' => $request->has('marketing_opt_in'),
        ]);

        return back()->with('success', "Customer {$customer->name} details updated successfully!");
    }

    public function destroy(Customer $customer)
    {
        $vendor = Auth::user()->vendor;
        if ($customer->vendor_id !== $vendor->id) {
            abort(403);
        }

        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Customer record deleted.');
    }

    public function export(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $activeLocationId = session('active_location_id');

        $query = Customer::where('vendor_id', $vendor->id)->with('location');

        if ($activeLocationId && $activeLocationId !== 'all') {
            $query->where(function ($q) use ($activeLocationId) {
                $q->where('location_id', $activeLocationId)
                  ->orWhereHas('orders', function ($oq) use ($activeLocationId) {
                      $oq->where('location_id', $activeLocationId);
                  });
            });
        }

        $customers = $query->orderBy('name', 'asc')->get();

        $fileName = 'customers_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($customers, $vendor) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($file, [
                'Customer ID',
                'Branch / Location',
                'Full Name',
                'Phone Number',
                'Email Address',
                'Marketing Consent',
                'Total Orders Count',
                "Total Spent ({$vendor->currency})",
                'Last Order Date',
                'Registration Date',
                'Notes'
            ]);

            foreach ($customers as $c) {
                fputcsv($file, [
                    $c->id,
                    $c->location?->name ?? 'All Branches',
                    $c->name ?? 'Guest',
                    $c->phone ?? '-',
                    $c->email ?? '-',
                    $c->marketing_opt_in ? 'Yes (Opted In)' : 'No',
                    $c->total_orders_count,
                    number_format($c->total_spent, 2, '.', ''),
                    $c->last_order_at ? $c->last_order_at->format('Y-m-d H:i') : '-',
                    $c->created_at->format('Y-m-d H:i'),
                    $c->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
