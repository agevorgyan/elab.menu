<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vendorSlug = $this->route('vendor_slug');
        $vendor = $vendorSlug ? Vendor::where('slug', $vendorSlug)->first() : null;
        $vendorId = $vendor?->id;

        return [
            'location_id' => [
                'required',
                Rule::exists('locations', 'id')->where('vendor_id', $vendorId),
            ],
            'table_number' => 'required_if:type,dine_in|nullable|string',
            'delivery_address' => 'required_if:type,delivery|nullable|string|max:500',
            'type' => 'required|string|in:dine_in,takeaway,delivery,whatsapp',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'required_if:type,delivery,takeaway|nullable|string|max:50',
            'customer_email' => 'nullable|email',
            'customer_birthdate' => 'nullable|date',
            'marketing_opt_in' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string|in:cash,pos_terminal,idram,telcell,fastshift,arca,stripe',
            'active_order_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('vendor_id', $vendorId),
            ],
            'items.*.variation_id' => 'nullable|integer',
            'items.*.variation_name' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
