<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! Auth::check() || ! Auth::user()->vendor_id) {
            return false;
        }

        $product = $this->route('product');
        if ($product && $product->vendor_id !== Auth::user()->vendor_id) {
            return false;
        }

        if ($this->filled('category_id')) {
            $category = Category::withoutGlobalScopes()->find($this->input('category_id'));
            if (! $category || $category->vendor_id !== Auth::user()->vendor_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $imageRule = extension_loaded('fileinfo')
            ? ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:5120']
            : ['nullable', 'file', 'max:5120', function ($attribute, $value, $fail) {
                if ($value instanceof UploadedFile) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg'])) {
                        $fail('The '.$attribute.' must be a valid image file (jpeg, png, jpg, gif, webp, svg).');
                    }
                }
            }];

        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
            'hy_description' => 'nullable|string',
            'ru_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'discount_days' => 'nullable|array',
            'discount_days.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',
            'discount_start_time' => 'nullable|date_format:H:i',
            'discount_end_time' => 'nullable|date_format:H:i',
            'is_discount_active' => 'nullable|boolean',
            'image' => 'nullable|string',
            'image_file' => $imageRule,
            'dietary_tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'calories' => 'nullable|integer',
            'protein_g' => 'nullable|numeric',
            'carbs_g' => 'nullable|numeric',
            'fat_g' => 'nullable|numeric',
            'preparation_time_min' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            'variations' => 'nullable|array',
            'variations.*.id' => 'nullable|integer',
            'variations.*.name' => 'nullable|string|max:255',
            'variations.*.hy_name' => 'nullable|string|max:255',
            'variations.*.ru_name' => 'nullable|string|max:255',
            'variations.*.name_translations' => 'nullable|array',
            'variations.*.price' => 'nullable|numeric|min:0',
            'variations.*.is_default' => 'nullable',
        ];
    }
}
