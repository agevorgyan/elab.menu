@extends('layouts.app')

@section('title', 'Theme & Storefront Branding - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-palette text-amber-400"></i> Storefront Theme & Branding
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Customize visual templates, brand colors, and logos with live storefront preview.</p>
    </div>
</div>

<div class="grid-2">
    <!-- Form Customizer -->
    <div class="card">
        <form action="{{ route('admin.branding.update') }}" method="POST">
            @csrf
            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem;">1. Choose Digital Menu Template</h3>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem;">
                @foreach($templates as $tmpl)
                    <label style="display: flex; align-items: center; gap: 1rem; padding: 0.85rem; background: rgba(0,0,0,0.25); border: 2px solid {{ $vendor->menu_template_id == $tmpl->id ? '#f59e0b' : 'var(--border-color)' }}; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="menu_template_id" value="{{ $tmpl->id }}" {{ $vendor->menu_template_id == $tmpl->id ? 'checked' : '' }}>
                        <img src="{{ $tmpl->preview_image }}" style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">{{ $tmpl->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $tmpl->description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>

            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem;">2. Brand Color Palette & Logo</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Primary Color Accent</label>
                    <input type="color" name="primary_color" value="{{ $vendor->primary_color ?? '#e11d48' }}" style="width: 100%; height: 40px; background: none; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Secondary Color</label>
                    <input type="color" name="secondary_color" value="{{ $vendor->secondary_color ?? '#4f46e5' }}" style="width: 100%; height: 40px; background: none; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Theme Mode</label>
                <select name="theme_mode" style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
                    <option value="dark" {{ $vendor->theme_mode == 'dark' ? 'selected' : '' }}>Sleek Dark Mode</option>
                    <option value="light" {{ $vendor->theme_mode == 'light' ? 'selected' : '' }}>Clean Light Mode</option>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Logo Image URL</label>
                <input type="url" name="logo" value="{{ $vendor->logo }}" placeholder="https://..." style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Cover Header URL</label>
                <input type="url" name="cover_image" value="{{ $vendor->cover_image }}" placeholder="https://..." style="width: 100%; padding: 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1rem;">
                <i class="fa-solid fa-floppy-disk"></i> Save Theme & Branding
            </button>
        </form>
    </div>

    <!-- Live Preview Frame -->
    <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #000; border-radius: 24px; padding: 1.5rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
            📱 Live Mobile Storefront Preview
        </div>

        <div style="width: 320px; height: 600px; border: 10px solid #1f2937; border-radius: 36px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); position: relative; background: #0f172a;">
            <iframe src="{{ route('client.menu', ['vendor_slug' => $vendor->slug]) }}" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>
@endsection
