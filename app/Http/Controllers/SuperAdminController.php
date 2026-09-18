<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Models\MenuTemplate;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
        $plans = SubscriptionPlan::withCount('vendors')->get();

        return view('superadmin.dashboard', compact(
            'totalVendors',
            'totalLocations',
            'totalOrders',
            'totalRevenue',
            'recentVendors',
            'templates',
            'plans'
        ));
    }

    public function vendorsIndex()
    {
        $vendors = Vendor::with('locations', 'menuTemplate', 'plan')->latest()->get();
        $templates = MenuTemplate::all();
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('superadmin.vendors', compact('vendors', 'templates', 'plans'));
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

        $plan = SubscriptionPlan::where('slug', $validated['subscription_plan'])->first() 
            ?? SubscriptionPlan::first();

        $vendor = Vendor::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'type' => $validated['type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subscription_plan' => $plan?->slug ?? 'pro',
            'subscription_plan_id' => $plan?->id,
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'subscription_expires_at' => now()->addDays(14),
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

    // Plan Management
    public function plansIndex()
    {
        $plans = SubscriptionPlan::withCount('vendors')->orderBy('sort_order', 'asc')->get();
        return view('superadmin.plans.index', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|string|in:monthly,yearly,custom',
            'duration_days' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $validated['features'] ?? ''))));

        SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => $validated['price'],
            'currency' => 'AMD',
            'billing_interval' => $validated['billing_interval'],
            'duration_days' => $validated['duration_days'],
            'trial_days' => $validated['trial_days'],
            'description' => $validated['description'] ?? null,
            'features' => $featuresArray,
            'is_custom' => $validated['price'] == 0 || $validated['billing_interval'] === 'custom',
            'is_active' => true,
            'sort_order' => (SubscriptionPlan::max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('success', 'Բաժանորդագրության փաթեթը հաջողությամբ ստեղծվեց։');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|string|in:monthly,yearly,custom',
            'duration_days' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
        ]);

        $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $validated['features'] ?? ''))));

        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'billing_interval' => $validated['billing_interval'],
            'duration_days' => $validated['duration_days'],
            'trial_days' => $validated['trial_days'],
            'description' => $validated['description'] ?? null,
            'features' => $featuresArray,
            'is_custom' => $validated['price'] == 0 || $validated['billing_interval'] === 'custom',
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Բաժանորդագրության փաթեթը թարմացվեց։');
    }

    public function togglePlan(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', 'Փաթեթի կարգավիճակը թարմացվեց։');
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->vendors()->count() > 0) {
            return back()->with('error', 'Հնարավոր չէ ջնջել փաթեթը, քանի որ այն կցված է վենդորների։');
        }
        $plan->delete();
        return back()->with('success', 'Փաթեթը հաջողությամբ ջնջվեց։');
    }

    // Vendor Subscriptions & Payment Logs
    public function subscriptionsIndex()
    {
        $vendors = Vendor::with('plan', 'payments')->latest()->get();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        return view('superadmin.subscriptions.index', compact('vendors', 'plans'));
    }

    public function updateVendorSubscription(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_status' => 'required|string|in:trialing,active,expired,cancelled',
            'subscription_expires_at' => 'nullable|date',
            'custom_plan_notes' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::find($validated['subscription_plan_id']);

        $vendor->update([
            'subscription_plan_id' => $plan->id,
            'subscription_plan' => $plan->slug,
            'subscription_status' => $validated['subscription_status'],
            'subscription_expires_at' => $validated['subscription_expires_at'] ? Carbon::parse($validated['subscription_expires_at']) : null,
            'custom_plan_notes' => $validated['custom_plan_notes'] ?? null,
            'is_active' => $validated['subscription_status'] !== 'expired',
        ]);

        return back()->with('success', "{$vendor->name}-ի բաժանորդագրությունը թարմացվեց։");
    }

    public function storeVendorPayment(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'invoice_number' => 'nullable|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'status' => 'required|string|in:paid,pending,failed',
            'notes' => 'nullable|string',
        ]);

        SubscriptionPayment::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $vendor->subscription_plan_id,
            'amount' => $validated['amount'],
            'currency' => $vendor->currency ?? 'AMD',
            'payment_method' => $validated['payment_method'],
            'invoice_number' => $validated['invoice_number'] ?? 'INV-' . strtoupper(Str::random(6)),
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->has('extend_subscription') && $validated['status'] === 'paid') {
            $vendor->update([
                'subscription_status' => 'active',
                'subscription_expires_at' => Carbon::parse($validated['period_end']),
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'Վճարման գրանցումը հաջողությամբ պահպանվեց։');
    }
}
