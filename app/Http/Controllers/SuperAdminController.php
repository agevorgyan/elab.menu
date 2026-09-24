<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'slug' => Str::slug($validated['name']).'-'.Str::random(4),
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
        $vendor->update(['is_active' => ! $vendor->is_active]);

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
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', 'Փաթեթի կարգավիճակը թարմացվեց։');
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->vendors()->count() > 0) {
            return back()->with('error', 'Հնարավոր չէ ջնջել փաթեթը, քանի որ այն կցված է գործընկերների։');
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
            'invoice_number' => $validated['invoice_number'] ?? 'INV-'.strtoupper(Str::random(6)),
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

    public function settingsIndex()
    {
        $settings = SystemSetting::getAll();

        return view('superadmin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'contact_phone' => 'required|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_telegram' => 'nullable|string|max:100',
            'contact_email' => 'required|email|max:100',
            'social_facebook' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'demo_vendor_slug' => 'nullable|string|max:100',
            'trial_days' => 'required|integer|min:1|max:90',
            'hero_title_hy' => 'nullable|string|max:255',
            'hero_title_en' => 'nullable|string|max:255',
            'hero_subtitle_hy' => 'nullable|string|max:500',
            'hero_subtitle_en' => 'nullable|string|max:500',
        ]);

        foreach ($validated as $key => $value) {
            $group = str_starts_with($key, 'contact_') ? 'contact' : (str_starts_with($key, 'social_') ? 'social' : 'landing');
            SystemSetting::set($key, $value, $group);
        }

        return back()->with('success', 'Համակարգի և լենդինգի կարգավորումները հաջողությամբ պահպանվեցին։');
    }

    public function editVendor(Vendor $vendor)
    {
        $vendor->load(['locations', 'users.location', 'menuTemplate', 'plan']);
        $templates = MenuTemplate::all();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.vendor_edit', compact('vendor', 'templates', 'plans'));
    }

    public function updateVendor(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:vendors,slug,'.$vendor->id,
            'type' => 'required|string|in:restaurant,cafe,hotel',
            'custom_domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'currency' => 'required|string|in:AMD,USD,EUR,RUB',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'legal_address' => 'nullable|string|max:500',
            'operating_address' => 'nullable|string|max:500',
            'director_name' => 'nullable|string|max:255',
            'director_phone' => 'nullable|string|max:50',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_phone' => 'nullable|string|max:50',
            'menu_template_id' => 'required|exists:menu_templates,id',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'theme_mode' => 'nullable|string|in:light,dark,auto',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'service_fee_enabled' => 'nullable|boolean',
            'service_fee_type' => 'nullable|string|in:percentage,fixed',
            'service_fee_value' => 'nullable|numeric|min:0',
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_min_amount' => 'nullable|numeric|min:0',
            'takeaway_enabled' => 'nullable|boolean',
            'takeaway_min_amount' => 'nullable|numeric|min:0',
        ]);

        if (! empty($validated['custom_domain'])) {
            $domain = preg_replace('#^https?://#i', '', trim($validated['custom_domain']));
            $domain = rtrim(explode('/', $domain)[0], '/');
            $validated['custom_domain'] = strtolower($domain);
        } else {
            $validated['custom_domain'] = null;
        }

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['service_fee_enabled'] = $request->boolean('service_fee_enabled');
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');
        $validated['takeaway_enabled'] = $request->boolean('takeaway_enabled');

        if (! empty($validated['subscription_plan_id'])) {
            $plan = SubscriptionPlan::find($validated['subscription_plan_id']);
            if ($plan) {
                $validated['subscription_plan'] = $plan->slug;
            }
        }

        $vendor->update($validated);

        return back()->with('success', 'Գործընկերոջ տվյալները և հղումները հաջողությամբ թարմացվեցին։');
    }

    public function storeVendorLocation(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'table_count' => 'required|integer|min:1|max:500',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'allow_dine_in_orders' => 'nullable|boolean',
            'allow_whatsapp_orders' => 'nullable|boolean',
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        if (Location::where('vendor_id', $vendor->id)->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::random(4);
        }

        Location::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'wifi_ssid' => $validated['wifi_ssid'] ?? null,
            'wifi_password' => $validated['wifi_password'] ?? null,
            'working_hours' => $validated['working_hours'] ?? null,
            'table_count' => $validated['table_count'],
            'minimum_order_amount' => $validated['minimum_order_amount'] ?? 0,
            'allow_dine_in_orders' => $request->boolean('allow_dine_in_orders', true),
            'allow_whatsapp_orders' => $request->boolean('allow_whatsapp_orders', true),
            'is_active' => true,
        ]);

        return back()->with('success', 'Նոր մասնաճյուղը հաջողությամբ ավելացվեց։');
    }

    public function updateVendorLocation(Request $request, Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',
            'table_count' => 'required|integer|min:1|max:500',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'allow_dine_in_orders' => 'nullable|boolean',
            'allow_whatsapp_orders' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }
        $validated['allow_dine_in_orders'] = $request->boolean('allow_dine_in_orders');
        $validated['allow_whatsapp_orders'] = $request->boolean('allow_whatsapp_orders');
        $validated['is_active'] = $request->boolean('is_active', true);

        $location->update($validated);

        return back()->with('success', "«{$location->name}» մասնաճյուղի տվյալները հաջողությամբ թարմացվեցին։");
    }

    public function destroyVendorLocation(Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        if ($vendor->locations()->count() <= 1) {
            return back()->with('error', 'Հնարավոր չէ ջնջել վերջին մնացած մասնաճյուղը։ Գործընկերը պետք է ունենա առնվազն մեկ ակտիվ մասնաճյուղ։');
        }

        User::where('location_id', $location->id)->update(['location_id' => null]);

        $locationName = $location->name;
        $location->delete();

        return back()->with('success', "«{$locationName}» մասնաճյուղը հաջողությամբ ջնջվեց։");
    }

    public function updateVendorLocationTables(Request $request, Vendor $vendor, Location $location)
    {
        abort_if($location->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'table_count' => 'required|integer|min:1|max:500',
        ]);

        $location->update(['table_count' => $validated['table_count']]);

        return back()->with('success', "«{$location->name}» մասնաճյուղի սեղանների քանակը փոխվեց՝ {$validated['table_count']}։");
    }

    public function storeVendorUser(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:vendor_owner,branch_manager,manager,waiter,kitchen_staff,staff',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6',
        ]);

        if (! empty($validated['location_id'])) {
            $loc = Location::find($validated['location_id']);
            abort_if(! $loc || $loc->vendor_id !== $vendor->id, 403, 'Invalid location assignment');
        }

        User::create([
            'vendor_id' => $vendor->id,
            'location_id' => $validated['location_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Նոր աշխատակիցը/օգտատերը հաջողությամբ ավելացվեց։');
    }

    public function updateVendorUser(Request $request, Vendor $vendor, User $user)
    {
        abort_if($user->vendor_id !== $vendor->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|string|in:vendor_owner,branch_manager,manager,waiter,kitchen_staff,staff',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:6',
        ]);

        if (! empty($validated['location_id'])) {
            $loc = Location::find($validated['location_id']);
            abort_if(! $loc || $loc->vendor_id !== $vendor->id, 403, 'Invalid location assignment');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'location_id' => $validated['location_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return back()->with('success', "«{$user->name}» օգտատիրոջ տվյալները հաջողությամբ թարմացվեցին։");
    }

    public function destroyVendorUser(Vendor $vendor, User $user)
    {
        abort_if($user->vendor_id !== $vendor->id, 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Հնարավոր չէ ջնջել ընթացիկ մուտք գործած հաշիվը։');
        }

        if ($vendor->users()->count() <= 1) {
            return back()->with('error', 'Հնարավոր չէ ջնջել գործընկերոջ վերջին մնացած օգտատիրոջը։');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "«{$userName}» օգտատերը հաջողությամբ ջնջվեց։");
    }

    public function updateProfileSecurity(Request $request)
    {
        $user = auth()->user();
        $type = $request->input('action_type', 'password');

        if ($type === 'email') {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'email' => 'required|email|unique:users,email,'.$user->id,
            ], [
                'email.unique' => 'Այս էլ․ փոստի հասցեն արդեն գրանցված է համակարգում։',
            ]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։'])->with('error_type', 'email');
            }

            $user->update(['email' => $validated['email']]);

            return back()->with('success', 'Ձեր էլ․ փոստի հասցեն հաջողությամբ թարմացվեց։');
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Նոր գաղտնաբառի հաստատումը չի համընկնում։',
            'password.min' => 'Գաղտնաբառը պետք է լինի առնվազն 6 նիշ։',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։'])->with('error_type', 'password');
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Ձեր գաղտնաբառը հաջողությամբ փոխվեց։');
    }
}
