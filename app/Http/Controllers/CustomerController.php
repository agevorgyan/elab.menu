<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $search = $request->get('search');
        $consentFilter = $request->get('marketing');

        $query = Customer::where('vendor_id', $vendor->id);

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

        // Stats summary
        $totalCustomers = Customer::where('vendor_id', $vendor->id)->count();
        $optedInCustomers = Customer::where('vendor_id', $vendor->id)->where('marketing_opt_in', true)->count();
        $totalRevenue = Customer::where('vendor_id', $vendor->id)->sum('total_spent');

        return view('admin.customers.index', compact('vendor', 'customers', 'totalCustomers', 'optedInCustomers', 'totalRevenue', 'search', 'consentFilter'));
    }

    public function show(Customer $customer)
    {
        $vendor = Auth::user()->vendor;
        if ($customer->vendor_id !== $vendor->id) {
            abort(403);
        }

        $customer->recalculateStats();
        $orders = $customer->orders()->with('items', 'location')->paginate(20);

        return view('admin.customers.show', compact('vendor', 'customer', 'orders'));
    }

    public function store(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        Customer::create([
            'vendor_id' => $vendor->id,
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
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        $customer->update([
            'name' => $validated['name'],
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
        $customers = Customer::where('vendor_id', $vendor->id)->orderBy('name', 'asc')->get();

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
