<?php

namespace App\Http\Controllers;

use App\Http\Requests\RenewSubscriptionRequest;
use App\Models\AnalyticsLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorAdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if (! $vendor) {
            abort(404, 'Vendor not found for user.');
        }

        $activeLocationId = $request->get('location_id', session('active_location_id', $vendor->locations->first()?->id));
        if ($activeLocationId) {
            session(['active_location_id' => $activeLocationId]);
        }

        $location = Location::find($activeLocationId);

        // Stats
        $ordersQuery = Order::where('vendor_id', $vendor->id);
        if ($location) {
            $ordersQuery->where('location_id', $location->id);
        }

        $todayOrders = (clone $ordersQuery)->whereDate('created_at', Carbon::today())->count();
        $todayRevenue = (clone $ordersQuery)->whereDate('created_at', Carbon::today())->where('status', 'completed')->sum('total_amount');
        $pendingOrdersCount = (clone $ordersQuery)->where('status', 'pending')->count();

        // Analytics visits (past 7 days)
        $analyticsQuery = AnalyticsLog::where('vendor_id', $vendor->id);
        if ($location) {
            $analyticsQuery->where('location_id', $location->id);
        }
        $dineInVisits = (clone $analyticsQuery)->where('channel', 'dine_in')->count();
        $orderingVisits = (clone $analyticsQuery)->where('channel', 'ordering')->count();

        $recentOrders = (clone $ordersQuery)->with('items')->latest()->take(6)->get();

        return view('admin.dashboard', compact(
            'vendor',
            'location',
            'todayOrders',
            'todayRevenue',
            'pendingOrdersCount',
            'dineInVisits',
            'orderingVisits',
            'recentOrders'
        ));
    }

    public function locationsIndex()
    {
        $vendor = Auth::user()->vendor;
        $locations = $vendor->locations;

        return view('admin.locations', compact('vendor', 'locations'));
    }

    public function storeLocation(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'table_count' => 'required|integer|min:1|max:200',
            'minimum_order_amount' => 'nullable|numeric|min:0',
        ]);

        Location::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'table_count' => $validated['table_count'],
            'minimum_order_amount' => $validated['minimum_order_amount'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'New Location added successfully!');
    }

    public function teamIndex()
    {
        $vendor = Auth::user()->vendor;
        $team = User::where('vendor_id', $vendor->id)->get();
        $locations = $vendor->locations;

        return view('admin.team', compact('vendor', 'team', 'locations'));
    }

    public function storeTeamMember(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:manager,staff',
            'location_id' => 'nullable|exists:locations,id',
            'password' => 'required|string|min:6',
        ]);

        if (! empty($validated['location_id'])) {
            $loc = Location::find($validated['location_id']);
            if (! $loc || $loc->vendor_id !== $vendor->id) {
                abort(403, 'Unauthorized location assignment.');
            }
        }

        User::create([
            'vendor_id' => $vendor->id,
            'location_id' => $validated['location_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Team member invited successfully!');
    }

    public function subscriptionIndex()
    {
        $vendor = Auth::user()->vendor;
        $vendor->load('plan', 'payments');
        $allPlans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order', 'asc')->get();

        return view('admin.subscription.index', compact('vendor', 'allPlans'));
    }

    /**
     * Renew or upgrade vendor SaaS subscription via selected payment gateway.
     */
    public function renewSubscription(RenewSubscriptionRequest $request, PaymentGatewayService $paymentService)
    {
        $vendor = Auth::user()->vendor;
        $validated = $request->validated();

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        $renewalData = $paymentService->initiateSubscriptionRenewal(
            $vendor,
            $plan,
            (int) $validated['period_months'],
            $validated['payment_method']
        );

        $payment = $renewalData['payment'];
        $result = $renewalData['result'];

        $methodNames = [
            'idram' => 'Idram Wallet / QR',
            'telcell' => 'Telcell Wallet',
            'fastshift' => 'FastShift',
            'arca' => 'ArCa / Ameriabank vPOS',
            'stripe' => 'Stripe',
            'bank_transfer' => 'Բանկային փոխանցում',
            'cash' => 'Կանխիկ',
        ];
        $selectedMethodName = $methodNames[$validated['payment_method']] ?? strtoupper($validated['payment_method']);

        if ($result->type === 'redirect' && $result->redirectUrl) {
            return redirect($result->redirectUrl)->with(
                'info',
                "Վճարման հարցումը ստեղծված է ({$selectedMethodName})։ Հաշիվ-ապրանքագիր: {$payment->invoice_number}։"
            );
        }

        return back()->with(
            'success',
            "Հաշիվ-ապրանքագիրը ստեղծված է ({$selectedMethodName})։ Հաշիվ-ապրանքագիր: {$payment->invoice_number}։ Վճարման հաստատումից հետո բաժանորդագրությունը կակտիվանա։"
        );
    }
}
