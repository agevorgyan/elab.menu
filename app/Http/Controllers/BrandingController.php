<?php

namespace App\Http\Controllers;

use App\Models\MenuTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $validated = $request->validate([
            'menu_template_id' => 'required|exists:menu_templates,id',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'bg_color' => 'nullable|string|max:20',
            'theme_mode' => 'required|string|in:dark,light',
            'desktop_max_width' => 'nullable|string|max:20',
            'logo' => 'nullable|string',
            'logo_file' => $imageRule,
            'cover_image' => 'nullable|string',
            'cover_file' => $imageRule,
            'custom_css' => 'nullable|string',
        ]);

        $brandingDir = storage_path('app/public/branding');
        if (! is_dir($brandingDir)) {
            @mkdir($brandingDir, 0775, true);
        }

        if ($request->hasFile('logo_file') && $request->file('logo_file')->isValid()) {
            try {
                if (! empty($vendor->logo) && ! str_starts_with($vendor->logo, 'http://') && ! str_starts_with($vendor->logo, 'https://')) {
                    $oldLogoPath = ltrim(str_replace('/storage/', '', $vendor->logo), '/');
                    if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                        Storage::disk('public')->delete($oldLogoPath);
                    }
                }
                $path = $request->file('logo_file')->store('branding', 'public');
                if ($path) {
                    $validated['logo'] = '/storage/'.$path;
                }
            } catch (\Throwable $e) {
                Log::error('Branding logo upload failed: '.$e->getMessage(), ['exception' => $e]);

                return back()->withInput()->with('error', 'Լոգոյի վերբեռնումը ձախողվեց: '.$e->getMessage());
            }
        }

        if ($request->hasFile('cover_file') && $request->file('cover_file')->isValid()) {
            try {
                if (! empty($vendor->cover_image) && ! str_starts_with($vendor->cover_image, 'http://') && ! str_starts_with($vendor->cover_image, 'https://')) {
                    $oldCoverPath = ltrim(str_replace('/storage/', '', $vendor->cover_image), '/');
                    if ($oldCoverPath && Storage::disk('public')->exists($oldCoverPath)) {
                        Storage::disk('public')->delete($oldCoverPath);
                    }
                }
                $path = $request->file('cover_file')->store('branding', 'public');
                if ($path) {
                    $validated['cover_image'] = '/storage/'.$path;
                }
            } catch (\Throwable $e) {
                Log::error('Branding cover upload failed: '.$e->getMessage(), ['exception' => $e]);

                return back()->withInput()->with('error', 'Կազմի նկարի վերբեռնումը ձախողվեց: '.$e->getMessage());
            }
        }

        unset($validated['logo_file'], $validated['cover_file']);

        $vendor->update($validated);

        return back()->with('success', 'Storefront branding and theme successfully saved!');
    }
}
