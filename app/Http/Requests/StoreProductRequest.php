<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!Auth::check() || !Auth::user()->vendor_id) {
            return false;
        }

        if ($this->filled('category_id')) {
            $category = Category::withoutGlobalScopes()->find($this->input('category_id'));
            if (!$category || $category->vendor_id !== Auth::user()->vendor_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
            'hy_description' => 'nullable|string',
            'ru_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'dietary_tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'calories' => 'nullable|integer',
            'protein_g' => 'nullable|numeric',
            'carbs_g' => 'nullable|numeric',
            'fat_g' => 'nullable|numeric',
            'preparation_time_min' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
        ];
    }
}
