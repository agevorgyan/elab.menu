<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallWaiterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize inputs to prevent XSS.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->notes ? strip_tags($this->notes) : null,
            'table_number' => $this->table_number ? strip_tags($this->table_number) : null,
        ]);
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
            'table_number' => 'required|string|max:50',
            'type' => 'required|string|in:call_waiter,bill_cash,bill_card',
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where('vendor_id', $vendorId),
            ],
            'notes' => 'nullable|string|max:255',
        ];
    }
}
