<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VendorSettingsController extends Controller
{
    /**
     * Display the vendor service & delivery settings, branch info, and legal details.
     */
    public function index(Request $request): View
    {
        $vendor = Auth::user()->vendor;
        $vendor->load('featuredProduct');

        $products = $vendor->products()
            ->with(['category'])
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $locationId = $request->get('location_id', session('active_location_id', $vendor->locations->first()?->id));
        $location = $vendor->locations()->find($locationId) ?? $vendor->locations->first();

        return view('admin.settings.index', compact('vendor', 'location', 'products'));
    }

    /**
     * Update vendor service fee, delivery configuration, branch information, and legal details.
     */
    public function update(Request $request): RedirectResponse
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            // Branch / Storefront Details
            'location_id' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:255',

            // Legal & Vendor Details (SuperAdmin)
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'operating_address' => 'nullable|string|max:500',
            'director_name' => 'nullable|string|max:255',
            'director_phone' => 'nullable|string|max:50',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_phone' => 'nullable|string|max:50',

            // Service fee
            'service_fee_enabled' => 'nullable|boolean',
            'service_fee_type' => 'required|string|in:percent,fixed',
            'service_fee_value' => 'required|numeric|min:0',
            'service_fee_min_order' => 'nullable|numeric|min:0',

            // Delivery
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'required|numeric|min:0',
            'delivery_min_amount' => 'required|numeric|min:0',
            'delivery_free_from' => 'nullable|numeric|min:0',

            // Takeaway
            'takeaway_enabled' => 'nullable|boolean',
            'takeaway_min_amount' => 'nullable|numeric|min:0',

            // Featured Dish / Dish of the Day
            'featured_dish_enabled' => 'nullable|boolean',
            'featured_product_id' => 'nullable|integer|exists:products,id',
            'featured_dish_badge' => 'nullable|string|max:100',
            'featured_dish_subtitle' => 'nullable|string|max:255',

            // AI Waiter Advisor
            'ai_waiter_enabled' => 'nullable|boolean',
            'ai_waiter_name' => 'nullable|string|max:100',
            'ai_waiter_priority_ingredients' => 'nullable|string|max:2000',
            'ai_waiter_welcome_text' => 'nullable|string|max:1000',
        ]);

        $validated['service_fee_enabled'] = $request->boolean('service_fee_enabled');
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');
        $validated['takeaway_enabled'] = $request->boolean('takeaway_enabled');
        $validated['featured_dish_enabled'] = $request->boolean('featured_dish_enabled');
        $validated['ai_waiter_enabled'] = $request->boolean('ai_waiter_enabled');

        $vendorUpdate = [
            'service_fee_enabled' => $validated['service_fee_enabled'],
            'service_fee_type' => $validated['service_fee_type'],
            'service_fee_value' => $validated['service_fee_value'],
            'service_fee_min_order' => $validated['service_fee_min_order'] ?? null,
            'delivery_enabled' => $validated['delivery_enabled'],
            'delivery_fee' => $validated['delivery_fee'],
            'delivery_min_amount' => $validated['delivery_min_amount'],
            'delivery_free_from' => $validated['delivery_free_from'] ?? null,
            'takeaway_enabled' => $validated['takeaway_enabled'],
            'takeaway_min_amount' => $validated['takeaway_min_amount'] ?? 0,
            'featured_dish_enabled' => $validated['featured_dish_enabled'],
            'featured_dish_badge' => $validated['featured_dish_badge'] ?? null,
            'featured_dish_subtitle' => $validated['featured_dish_subtitle'] ?? null,
            'ai_waiter_enabled' => $validated['ai_waiter_enabled'],
        ];

        if (array_key_exists('ai_waiter_name', $validated)) {
            $vendorUpdate['ai_waiter_name'] = ! empty($validated['ai_waiter_name']) ? $validated['ai_waiter_name'] : 'AI Մատուցող';
        }
        if (array_key_exists('ai_waiter_priority_ingredients', $validated)) {
            $vendorUpdate['ai_waiter_priority_ingredients'] = $validated['ai_waiter_priority_ingredients'];
        }
        if (array_key_exists('ai_waiter_welcome_text', $validated)) {
            $vendorUpdate['ai_waiter_welcome_text'] = $validated['ai_waiter_welcome_text'];
        }

        // Ensure selected featured product belongs to this vendor
        if (! empty($validated['featured_product_id'])) {
            $productExists = $vendor->products()->where('id', $validated['featured_product_id'])->exists();
            $vendorUpdate['featured_product_id'] = $productExists ? $validated['featured_product_id'] : null;
        } else {
            $vendorUpdate['featured_product_id'] = null;
        }

        if (! empty($validated['name'])) {
            $vendorUpdate['name'] = $validated['name'];
        }
        if (array_key_exists('phone', $validated)) {
            $vendorUpdate['phone'] = $validated['phone'];
        }
        if (array_key_exists('wifi_ssid', $validated)) {
            $vendorUpdate['wifi_ssid'] = $validated['wifi_ssid'];
        }
        if (array_key_exists('wifi_password', $validated)) {
            $vendorUpdate['wifi_password'] = $validated['wifi_password'];
        }
        if (array_key_exists('working_hours', $validated)) {
            $vendorUpdate['working_hours'] = $validated['working_hours'];
        }
        if (array_key_exists('legal_name', $validated)) {
            $vendorUpdate['legal_name'] = $validated['legal_name'];
        }
        if (array_key_exists('tax_id', $validated)) {
            $vendorUpdate['tax_id'] = $validated['tax_id'];
        }
        if (array_key_exists('operating_address', $validated)) {
            $vendorUpdate['operating_address'] = $validated['operating_address'];
        } elseif (array_key_exists('address', $validated) && ! empty($validated['address'])) {
            $vendorUpdate['operating_address'] = $validated['address'];
        }
        if (array_key_exists('director_name', $validated)) {
            $vendorUpdate['director_name'] = $validated['director_name'];
        }
        if (array_key_exists('director_phone', $validated)) {
            $vendorUpdate['director_phone'] = $validated['director_phone'];
        }
        if (array_key_exists('contact_person_name', $validated)) {
            $vendorUpdate['contact_person_name'] = $validated['contact_person_name'];
        }
        if (array_key_exists('contact_person_phone', $validated)) {
            $vendorUpdate['contact_person_phone'] = $validated['contact_person_phone'];
        }

        $vendor->update($vendorUpdate);

        // Update Location (Branch)
        $locationId = $validated['location_id'] ?? $request->get('location_id', session('active_location_id', $vendor->locations->first()?->id));
        $location = $vendor->locations()->find($locationId) ?? $vendor->locations->first();

        if ($location) {
            $locationUpdate = [];
            if (! empty($validated['name'])) {
                $locationUpdate['name'] = $validated['name'];
            }
            if (array_key_exists('address', $validated)) {
                $locationUpdate['address'] = $validated['address'];
            }
            if (array_key_exists('phone', $validated)) {
                $locationUpdate['phone'] = $validated['phone'];
            }
            if (array_key_exists('whatsapp_number', $validated)) {
                $locationUpdate['whatsapp_number'] = $validated['whatsapp_number'];
            }
            if (array_key_exists('wifi_ssid', $validated)) {
                $locationUpdate['wifi_ssid'] = $validated['wifi_ssid'];
            }
            if (array_key_exists('wifi_password', $validated)) {
                $locationUpdate['wifi_password'] = $validated['wifi_password'];
            }
            if (array_key_exists('working_hours', $validated)) {
                $locationUpdate['working_hours'] = $validated['working_hours'];
            }

            if (! empty($locationUpdate)) {
                $location->update($locationUpdate);
            }
        }

        return back()->with('success', 'Կարգավորումները հաջողությամբ պահպանվեցին:');
    }
}
