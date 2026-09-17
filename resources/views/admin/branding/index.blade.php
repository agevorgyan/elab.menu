@extends('layouts.app')

@section('title', 'Theme & Storefront Branding - ' . $vendor->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700;">
            <i class="fa-solid fa-palette" style="color: var(--primary);"></i> Storefront Theme & Branding
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Customize visual templates, brand colors, and logos with live storefront preview.</p>
    </div>
</div>

<div class="grid-2">
    <!-- Form Customizer -->
    <div class="card">
        <form action="{{ route('admin.branding.update') }}" method="POST" id="brandingForm">
            @csrf
            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem; color: var(--text-main);">1. Choose Digital Menu Template</h3>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem;">
                @foreach($templates as $tmpl)
                    <label style="display: flex; align-items: center; gap: 1rem; padding: 0.85rem; background: var(--input-bg); border: 2px solid {{ $vendor->menu_template_id == $tmpl->id ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 12px; cursor: pointer; color: var(--text-main);">
                        <input type="radio" name="menu_template_id" value="{{ $tmpl->id }}" {{ $vendor->menu_template_id == $tmpl->id ? 'checked' : '' }} onchange="reloadPreview()">
                        <img src="{{ $tmpl->preview_image }}" style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">{{ $tmpl->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $tmpl->description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>

            <h3 style="font-family: 'Outfit'; font-size: 1.1rem; margin-bottom: 1rem; color: var(--text-main);">2. Brand Color Palette & Theme Mode</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Primary Color</label>
                    <input type="color" name="primary_color" value="{{ $vendor->primary_color ?? '#e11d48' }}" style="width: 100%; height: 42px; background: none; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Accent Color</label>
                    <input type="color" name="accent_color" value="{{ $vendor->accent_color ?? '#f59e0b' }}" style="width: 100%; height: 42px; background: none; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Secondary Color</label>
                    <input type="color" name="secondary_color" value="{{ $vendor->secondary_color ?? '#4f46e5' }}" style="width: 100%; height: 42px; background: none; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Background Color</label>
                    <input type="color" name="bg_color" value="{{ $vendor->bg_color ?? ($vendor->theme_mode == 'light' ? '#f8fafc' : '#09090b') }}" style="width: 100%; height: 42px; background: none; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Text Color</label>
                    <input type="color" name="text_color" value="{{ $vendor->text_color ?? ($vendor->theme_mode == 'light' ? '#0f172a' : '#f4f4f5') }}" style="width: 100%; height: 42px; background: none; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Storefront Theme Mode</label>
                <select name="theme_mode" id="themeModeSelect" onchange="reloadPreview()" style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
                    <option value="dark" {{ $vendor->theme_mode == 'dark' ? 'selected' : '' }}>Sleek Dark Mode</option>
                    <option value="light" {{ $vendor->theme_mode == 'light' ? 'selected' : '' }}>Clean Light Mode</option>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Logo Image URL</label>
                <input type="url" name="logo" value="{{ $vendor->logo }}" placeholder="https://..." style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.35rem;">Cover Header URL</label>
                <input type="url" name="cover_image" value="{{ $vendor->cover_image }}" placeholder="https://..." style="width: 100%; padding: 0.65rem 0.9rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); font-size: 0.9rem; outline: none;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1rem;">
                <i class="fa-solid fa-floppy-disk"></i> Save Theme & Branding
            </button>
        </form>
    </div>

    <!-- Live Preview Frame -->
    <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--input-bg); border-radius: 24px; padding: 1.5rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
            📱 LIVE MOBILE STOREFRONT PREVIEW
        </div>

        <div style="width: 330px; height: 620px; border: 10px solid #1f2937; border-radius: 36px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4); position: relative; background: #0f172a;">
            <iframe id="previewIframe" src="{{ route('client.menu', ['vendor_slug' => $vendor->slug]) }}" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>

<script>
    function reloadPreview() {
        const form = document.getElementById('brandingForm');
        if (!form) return;
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        const iframe = document.getElementById('previewIframe');
        if (!iframe) return;
        const baseUrl = "{{ route('client.menu', ['vendor_slug' => $vendor->slug]) }}";
        iframe.src = baseUrl + '?' + params.toString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const themeSelect = id('themeModeSelect');
        const bgInput = document.querySelector('input[name="bg_color"]');
        const textInput = document.querySelector('input[name="text_color"]');

        function id(name) { return document.getElementById(name); }

        if (themeSelect) {
            themeSelect.addEventListener('change', function() {
                if (this.value === 'light') {
                    if (bgInput && bgInput.value === '#09090b') bgInput.value = '#f8fafc';
                    if (textInput && textInput.value === '#f4f4f5') textInput.value = '#0f172a';
                } else if (this.value === 'dark') {
                    if (bgInput && bgInput.value === '#f8fafc') bgInput.value = '#09090b';
                    if (textInput && textInput.value === '#0f172a') textInput.value = '#f4f4f5';
                }
                reloadPreview();
            });
        }

        document.querySelectorAll('#brandingForm input, #brandingForm select').forEach(el => {
            el.addEventListener('input', reloadPreview);
            el.addEventListener('change', reloadPreview);
        });

        // Initial preview load with active form values
        reloadPreview();
    });
</script>
@endsection
