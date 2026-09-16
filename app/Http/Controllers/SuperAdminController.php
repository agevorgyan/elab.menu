<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Models\MenuTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalVendors = Vendor::count();
        $totalLocations = Location::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        
        $recentVendors = Vendor::withCount('locations')->latest()->take(5)->get();
        $templates = MenuTemplate::withCount('vendors')->get();

        return view('superadmin.dashboard', compact(
            'totalVendors',
            'totalLocations',
            'totalOrders',
            'totalRevenue',
            'recentVendors',
            'templates'
        ));
    }

    public function vendorsIndex()
    {
        $vendors = Vendor::with('locations', 'menuTemplate')->latest()->get();
        $templates = MenuTemplate::all();
        return view('superadmin.vendors', compact('vendors', 'templates'));
    }

    public function storeVendor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:restaurant,cafe,hotel',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'subscription_plan' => 'required|string',
            'menu_template_id' => 'required|exists:menu_templates,id',
            'owner_name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $vendor = Vendor::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'type' => $validated['type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subscription_plan' => $validated['subscription_plan'],
            'menu_template_id' => $validated['menu_template_id'],
            'primary_color' => '#e11d48',
            'secondary_color' => '#4f46e5',
            'is_active' => true,
        ]);

        // Default Location
        Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Main Location',
            'slug' => 'main',
            'phone' => $validated['phone'] ?? null,
            'table_count' => 20,
            'is_active' => true,
        ]);

        // Create Owner User
        User::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['owner_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendor_owner',
            'phone' => $validated['phone'] ?? null,
        ]);

        return back()->with('success', 'Vendor & Owner Account successfully created!');
    }

    public function toggleStatus(Vendor $vendor)
    {
        $vendor->update(['is_active' => !$vendor->is_active]);
        return back()->with('success', 'Vendor status updated successfully.');
    }
}
