<?php

namespace App\Http\Controllers;

use App\Models\MenuTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'secondary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'bg_color' => 'nullable|string|max:20',
            'theme_mode' => 'required|string|in:dark,light',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'cover_image' => 'nullable|string',
            'cover_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'custom_css' => 'nullable|string',
        ]);

        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            if (! empty($vendor->logo) && ! str_starts_with($vendor->logo, 'http://') && ! str_starts_with($vendor->logo, 'https://')) {
                $oldLogoPath = ltrim(str_replace('/storage/', '', $vendor->logo), '/');
                if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
            }
            $path = $request->file('logo_file')->store('branding', 'public');
            $validated['logo'] = '/storage/'.$path;
        }

        if ($request->hasFile('cover_file') && $request->file('cover_file')->isValid()) {
            if (! empty($vendor->cover_image) && ! str_starts_with($vendor->cover_image, 'http://') && ! str_starts_with($vendor->cover_image, 'https://')) {
                $oldCoverPath = ltrim(str_replace('/storage/', '', $vendor->cover_image), '/');
                if ($oldCoverPath && Storage::disk('public')->exists($oldCoverPath)) {
                    Storage::disk('public')->delete($oldCoverPath);
                }
            }
            $path = $request->file('cover_file')->store('branding', 'public');
            $validated['cover_image'] = '/storage/'.$path;
        }

        unset($validated['logo_file'], $validated['cover_file']);

        $vendor->update($validated);

        return back()->with('success', 'Storefront branding and theme successfully saved!');
    }
}
