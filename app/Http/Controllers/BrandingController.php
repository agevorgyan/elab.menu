<?php

namespace App\Http\Controllers;

use App\Models\MenuTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandingController extends Controller
{
    public function index()
    {
        $vendor = Auth::user()->vendor;
        $templates = MenuTemplate::where('is_active', true)->get();
        return view('admin.branding.index', compact('vendor', 'templates'));
    }

    public function update(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'menu_template_id' => 'required|exists:menu_templates,id',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'theme_mode' => 'required|string|in:dark,light',
            'logo' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'custom_css' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return back()->with('success', 'Storefront branding and theme successfully saved!');
    }
}
