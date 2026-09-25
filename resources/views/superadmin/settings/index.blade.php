@extends('layouts.app')

@section('title', 'Լենդինգի & Կոնտակտների Կարգավորումներ - SuperAdmin')

@section('styles')
<style>
    .settings-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .settings-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-card);
    }
    .settings-section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color-light);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .form-input, .form-textarea {
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: var(--text-main);
        font-size: 0.92rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-input:focus, .form-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    .input-hint {
        font-size: 0.75rem;
        color: var(--text-subtle);
    }
    .btn-save {
        background: var(--primary-gradient);
        color: #ffffff;
        font-weight: 600;
        padding: 0.85rem 2rem;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        box-shadow: 0 4px 14px var(--primary-glow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--primary-glow);
    }
    .preview-box {
        background: rgba(245, 158, 11, 0.08);
        border: 1px dashed rgba(245, 158, 11, 0.3);
        border-radius: 16px;
        padding: 1.25rem;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .branding-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .branding-card:hover {
        border-color: var(--primary);
    }
    .branding-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .branding-preview-box {
        height: 100px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem;
        position: relative;
        overflow: hidden;
    }
    .branding-preview-box.light-bg {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.04);
    }
    .branding-preview-box.dark-bg {
        background: #070913;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.4);
    }
    .branding-preview-box.tab-mockup {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .browser-tab-preview {
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 8px 8px 0 0;
        padding: 0.5rem 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .social-preview-mockup {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.35);
    }
    .social-preview-img-wrap {
        width: 100%;
        height: 160px;
        background: #0b0f19;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .social-preview-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .social-preview-body {
        padding: 0.85rem 1rem;
    }
    .social-preview-domain {
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        display: block;
        margin-bottom: 0.25rem;
    }
    .social-preview-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: #f8fafc;
        margin-bottom: 0.25rem;
        line-height: 1.35;
    }
    .social-preview-desc {
        font-size: 0.78rem;
        color: #94a3b8;
        line-height: 1.45;
        margin: 0;
    }
</style>
@endsection

@section('content')
<div class="settings-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-amber">
                    <i class="fa-solid fa-sliders"></i> Համակարգի Կարգավորումներ
                </span>
            </div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--text-main);">
                Լենդինգ Էջի & Կոնտակտների Կառավարում
            </h1>
            <p style="color: var(--text-muted); font-size: 0.88rem;">
                Այստեղից կարող եք փոփոխել գլխավոր լենդինգ էջում (menu.elab.am) երևացող կոնտակտները, սոցիալական հղումները և պարամետրերը։
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('landing') }}" target="_blank" class="btn" style="background: var(--bg-card-hover); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.75rem 1.25rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Դիտել Լենդինգը
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem;">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('superadmin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- 1. Brand Identity, Logos & Favicon -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-palette" style="color: #f59e0b;"></i>
                <span>1. Համակարգի Բրենդինգ & Լոգոներ (Brand Identity & Logos)</span>
            </div>

            <div class="form-grid" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-font"></i> Համակարգի Անվանում (Site Name) *
                    </label>
                    <input type="text" name="site_name" class="form-input" value="{{ old('site_name', $settings['site_name'] ?? 'menu by eLab') }}" placeholder="menu by eLab կամ QRMenu">
                    <span class="input-hint">Ցուցադրվում է նավիգացիայում, էջերի վերնագրերում և նամակներում</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-tag"></i> Սուբանվանում / Կարգախոս (Tagline / Subtitle)
                    </label>
                    <input type="text" name="site_tagline" class="form-input" value="{{ old('site_tagline', $settings['site_tagline'] ?? 'Խելացի Ռեստորանային QR Մենյու & Պատվերների Համակարգ') }}" placeholder="Խելացի Ռեստորանային QR Մենյու & Պատվերների Համակարգ">
                    <span class="input-hint">Օգտագործվում է մուտքի էջում և SEO նկարագրություններում</span>
                </div>
            </div>

            <!-- Logos & Favicon Asset Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 1.25rem;">
                <!-- 1. Light Mode Logo -->
                <div class="branding-card">
                    <div class="branding-card-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 0.35rem 0.6rem; border-radius: 8px;">
                                <i class="fa-solid fa-sun"></i>
                            </span>
                            <strong style="color: var(--text-main); font-size: 0.95rem;">Լոգո Բաց Ֆոնի Համար</strong>
                        </div>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Light Mode</span>
                    </div>

                    <div class="branding-preview-box light-bg">
                        <img src="{{ \App\Models\SystemSetting::getLogoLight() }}" id="preview_logo_light" alt="Logo Light" style="max-height: 52px; max-width: 90%; object-fit: contain;">
                    </div>

                    <div style="margin-top: 0.85rem;">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.35rem;">
                            Վերբեռնել նոր լոգո (PNG, SVG, WebP)
                        </label>
                        <input type="file" name="logo_light_file" accept="image/*" class="form-input" style="padding: 0.45rem 0.6rem; font-size: 0.82rem;" onchange="previewImage(this, 'preview_logo_light')">
                    </div>

                    @if(!empty($settings['site_logo_light']))
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.76rem; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <input type="checkbox" name="remove_logo_light" value="1">
                                <span>Վերականգնել լռելյայնը</span>
                            </label>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ basename($settings['site_logo_light']) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- 2. Dark Mode Logo -->
                <div class="branding-card">
                    <div class="branding-card-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; padding: 0.35rem 0.6rem; border-radius: 8px;">
                                <i class="fa-solid fa-moon"></i>
                            </span>
                            <strong style="color: var(--text-main); font-size: 0.95rem;">Լոգո Մուգ Ֆոնի Համար</strong>
                        </div>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Dark Mode & Landing</span>
                    </div>

                    <div class="branding-preview-box dark-bg">
                        <img src="{{ \App\Models\SystemSetting::getLogoDark() }}" id="preview_logo_dark" alt="Logo Dark" style="max-height: 52px; max-width: 90%; object-fit: contain;">
                    </div>

                    <div style="margin-top: 0.85rem;">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.35rem;">
                            Վերբեռնել նոր լոգո (PNG, SVG, WebP)
                        </label>
                        <input type="file" name="logo_dark_file" accept="image/*" class="form-input" style="padding: 0.45rem 0.6rem; font-size: 0.82rem;" onchange="previewImage(this, 'preview_logo_dark')">
                    </div>

                    @if(!empty($settings['site_logo_dark']))
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.76rem; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <input type="checkbox" name="remove_logo_dark" value="1">
                                <span>Վերականգնել լռելյայնը</span>
                            </label>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ basename($settings['site_logo_dark']) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- 3. Favicon -->
                <div class="branding-card">
                    <div class="branding-card-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.35rem 0.6rem; border-radius: 8px;">
                                <i class="fa-solid fa-globe"></i>
                            </span>
                            <strong style="color: var(--text-main); font-size: 0.95rem;">Ֆավիկոն (Favicon / Icon)</strong>
                        </div>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Browser Tab</span>
                    </div>

                    <div class="branding-preview-box tab-mockup">
                        <div class="browser-tab-preview">
                            <img src="{{ \App\Models\SystemSetting::getFavicon() }}" id="preview_favicon" alt="Favicon" style="width: 22px; height: 22px; object-fit: contain; border-radius: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: #f8fafc; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ \App\Models\SystemSetting::getSiteName() }}
                            </span>
                        </div>
                    </div>

                    <div style="margin-top: 0.85rem;">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.35rem;">
                            Վերբեռնել (ICO, PNG, SVG - max 2MB)
                        </label>
                        <input type="file" name="favicon_file" accept=".ico,.png,.svg,.jpg" class="form-input" style="padding: 0.45rem 0.6rem; font-size: 0.82rem;" onchange="previewImage(this, 'preview_favicon')">
                    </div>

                    @if(!empty($settings['site_favicon']))
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.76rem; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <input type="checkbox" name="remove_favicon" value="1">
                                <span>Վերականգնել լռելյայնը</span>
                            </label>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ basename($settings['site_favicon']) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. SEO & Social Meta (OpenGraph) -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-magnifying-glass-chart" style="color: #06b6d4;"></i>
                <span>2. SEO Օպտիմիզացիա & Սոցիալական Ցանցերի Մետատվյալներ (SEO & OpenGraph)</span>
            </div>

            <div class="form-grid">
                <!-- SEO Title (HY) -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-heading"></i> Գլխավոր SEO Title (Հայերեն) *
                    </label>
                    <input type="text" name="seo_title" id="input_seo_title" class="form-input" value="{{ old('seo_title', $settings['seo_title'] ?? '') }}" placeholder="menu by eLab — Ժամանակակից QR Մենյու Համակարգ" oninput="updateLiveSocialPreview()">
                    <span class="input-hint">Երևում է Google որոնման և բրաուզերի tab-ում (առաջարկվում է 50-60 նիշ)</span>
                </div>

                <!-- SEO Title (EN) -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-globe"></i> SEO Title (English - optional)
                    </label>
                    <input type="text" name="seo_title_en" class="form-input" value="{{ old('seo_title_en', $settings['seo_title_en'] ?? '') }}" placeholder="menu by eLab — Smart QR Menu & Ordering Platform">
                    <span class="input-hint">Անգլերեն լեզվով այցելուների համար</span>
                </div>

                <!-- SEO Description (HY) -->
                <div class="form-group full-width">
                    <label class="form-label">
                        <i class="fa-solid fa-align-left"></i> SEO Meta Description (Հայերեն)
                    </label>
                    <textarea name="seo_description" id="input_seo_desc" rows="2" class="form-textarea" placeholder="Ժամանակակից ինտերակտիվ QR մենյու, սեղանից պատվերներ, մատուցողի կանչ և օնլայն վճարումներ ռեստորանների և սրճարանների համար։" oninput="updateLiveSocialPreview()">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea>
                    <span class="input-hint">Որոնողական համակարգերի տեքստային նկարագրություն (առաջարկվում է 140-160 նիշ)</span>
                </div>

                <!-- SEO Description (EN) -->
                <div class="form-group full-width">
                    <label class="form-label">
                        <i class="fa-solid fa-globe"></i> SEO Meta Description (English - optional)
                    </label>
                    <textarea name="seo_description_en" rows="2" class="form-textarea" placeholder="Modern interactive QR Menu system with table ordering, waiter calls, and online payments for restaurants and cafes.">{{ old('seo_description_en', $settings['seo_description_en'] ?? '') }}</textarea>
                </div>

                <!-- SEO Keywords -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-tags"></i> SEO Keywords (Բանալի Բառեր)
                    </label>
                    <input type="text" name="seo_keywords" class="form-input" value="{{ old('seo_keywords', $settings['seo_keywords'] ?? 'qr menu, qrmenu, qr menyu, restaurant menu, elab menu, ռեստորանային մենյու, պատվերներ սեղանից') }}" placeholder="ստորակետերով բաժանված բառեր">
                    <span class="input-hint">Հիմնաբառեր որոնողական ռոբոտների համար</span>
                </div>

                <!-- Footer Copyright -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-copyright"></i> Կայքի Copyright Տեքստ
                    </label>
                    <input type="text" name="footer_copyright" class="form-input" value="{{ old('footer_copyright', $settings['footer_copyright'] ?? '© 2026 eLab. Բոլոր իրավունքները պաշտպանված են։') }}" placeholder="© 2026 eLab. Բոլոր իրավունքները պաշտպանված են։">
                    <span class="input-hint">Ցուցադրվում է լենդինգի ստորոտում</span>
                </div>

                <!-- OpenGraph Social Image Preview & Upload -->
                <div class="form-group full-width" style="margin-top: 0.5rem;">
                    <label class="form-label">
                        <i class="fa-solid fa-share-nodes"></i> OpenGraph Social Share Preview & Նկար (Facebook, Telegram, WhatsApp)
                    </label>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 1.25rem; align-items: start;">
                        <!-- Social Mockup Card -->
                        <div class="social-preview-mockup">
                            <div class="social-preview-img-wrap">
                                <img src="{{ \App\Models\SystemSetting::getOgImage() }}" id="preview_og_image" alt="Social Preview">
                            </div>
                            <div class="social-preview-body">
                                <span class="social-preview-domain">{{ request()->getHost() }}</span>
                                <h4 class="social-preview-title" id="mockup_title">{{ \App\Models\SystemSetting::getSeoTitle() }}</h4>
                                <p class="social-preview-desc" id="mockup_desc">{{ Str::limit(\App\Models\SystemSetting::getSeoDescription(), 110) }}</p>
                            </div>
                        </div>

                        <!-- Upload & Controls -->
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 1.25rem;">
                            <label class="form-label" style="font-size: 0.85rem; margin-bottom: 0.4rem;">
                                Վերբեռնել OpenGraph Նկար (1200x630 px)
                            </label>
                            <input type="file" name="og_image_file" accept="image/*" class="form-input" style="padding: 0.5rem 0.75rem;" onchange="previewImage(this, 'preview_og_image')">
                            <span class="input-hint" style="margin-top: 0.4rem; display: block;">
                                Այս նկարը կցուցադրվի Telegram-ում, Facebook-ում, WhatsApp-ում կամ Viber-ում հղումը ուղարկելիս։
                            </span>

                            @if(!empty($settings['seo_og_image']))
                                <div style="margin-top: 0.75rem;">
                                    <label style="font-size: 0.8rem; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                                        <input type="checkbox" name="remove_og_image" value="1">
                                        <span>Ջնջել և վերականգնել լռելյայնը</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Contacts Section -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-headset" style="color: #f59e0b;"></i>
                <span>3. Պաշտոնական Կոնտակտներ (Լենդինգ և Աջակցություն)</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-phone"></i> Հիմնական Հեռախոսահամար *
                    </label>
                    <input type="text" name="contact_phone" class="form-input" required value="{{ old('contact_phone', $settings['contact_phone'] ?? '+37455776066') }}" placeholder="+37455776066">
                    <span class="input-hint">Ցուցադրվում է լենդինգի գլխամասում և ստորոտում</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i> WhatsApp Համար
                    </label>
                    <input type="text" name="contact_whatsapp" class="form-input" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '+37455776066') }}" placeholder="+37455776066">
                    <span class="input-hint">Արագ կապի կոճակների համար (առանց բացատների)</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-brands fa-telegram" style="color: #229ed9;"></i> Telegram Համար կամ Username
                    </label>
                    <input type="text" name="contact_telegram" class="form-input" value="{{ old('contact_telegram', $settings['contact_telegram'] ?? '+37455776066') }}" placeholder="+37455776066 կամ elab_menu">
                    <span class="input-hint">Telegram ալիքի կամ չատի համար</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-envelope"></i> Էլ. Փոստ (Official Email) *
                    </label>
                    <input type="email" name="contact_email" class="form-input" required value="{{ old('contact_email', $settings['contact_email'] ?? 'menu@elab.am') }}" placeholder="menu@elab.am">
                    <span class="input-hint">Հաճախորդների դիմումների և հարցումների համար</span>
                </div>
            </div>
        </div>

        <!-- 4. Social Media Links -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-share-nodes" style="color: #3b82f6;"></i>
                <span>4. Սոցիալական Ցանցեր</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-brands fa-facebook" style="color: #1877f2;"></i> Facebook Էջ / Link
                    </label>
                    <input type="text" name="social_facebook" class="form-input" value="{{ old('social_facebook', $settings['social_facebook'] ?? 'https://facebook.com/elab.menu') }}" placeholder="https://facebook.com/elab.menu կամ @elab.menu">
                    <span class="input-hint">Ֆեյսբուքյան պաշտոնական էջի հղումը</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-brands fa-instagram" style="color: #e1306c;"></i> Instagram Էջ / Link
                    </label>
                    <input type="text" name="social_instagram" class="form-input" value="{{ old('social_instagram', $settings['social_instagram'] ?? 'https://instagram.com/elab.menu') }}" placeholder="https://instagram.com/elab.menu կամ @elab.menu">
                    <span class="input-hint">Ինստագրամյան պաշտոնական էջի հղումը</span>
                </div>
            </div>
        </div>

        <!-- 5. Landing Page Experience & Demo -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: #10b981;"></i>
                <span>5. Լենդինգի Գործառույթներ & Դեմո Ռեստորան</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-utensils"></i> Դեմո Ռեստորանի Slug *
                    </label>
                    <input type="text" name="demo_vendor_slug" class="form-input" value="{{ old('demo_vendor_slug', $settings['demo_vendor_slug'] ?? 'bistro-yerevan') }}" placeholder="bistro-yerevan">
                    <span class="input-hint">Լենդինգի «Տեսնել Դեմոն» կոճակը կբացի այս գործընկերոջ մենյուն (/m/{slug})</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-calendar-check"></i> Անվճար Փորձաշրջանի Օրեր (Trial Days) *
                    </label>
                    <input type="number" name="trial_days" class="form-input" min="1" max="90" required value="{{ old('trial_days', $settings['trial_days'] ?? 14) }}">
                    <span class="input-hint">Ցուցադրվում է լենդինգի CTA-ներում և գրանցման ժամանակ (լռելյայն՝ 14)</span>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">
                        <i class="fa-solid fa-heading"></i> Հատուկ Վերնագրի Override (Հայերեն - ըստ ցանկության)
                    </label>
                    <input type="text" name="hero_title_hy" class="form-input" value="{{ old('hero_title_hy', $settings['hero_title_hy'] ?? '') }}" placeholder="Թողեք դատարկ՝ լռելյայն օպտիմիզացված վերնագիրը օգտագործելու համար">
                </div>

                <div class="form-group full-width">
                    <label class="form-label">
                        <i class="fa-solid fa-heading"></i> Հատուկ Վերնագրի Override (English - optional)
                    </label>
                    <input type="text" name="hero_title_en" class="form-input" value="{{ old('hero_title_en', $settings['hero_title_en'] ?? '') }}" placeholder="Leave blank to use default high-converting title">
                </div>
            </div>

            <div class="preview-box">
                <div>
                    <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 0.25rem;">
                        <i class="fa-solid fa-box-open" style="color: #f59e0b;"></i> Փաթեթների Գները (Pricing)
                    </strong>
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        Գները և ֆունկցիաները ավտոմատ վերցվում են <a href="{{ route('superadmin.plans.index') }}" style="color: var(--primary); text-decoration: underline;">«Փաթեթներ» (Plans)</a> բաժնից։ Փաթեթներում կատարված ցանկացած փոփոխություն ակնթարթորեն արտացոլվում է լենդինգում։
                    </span>
                </div>
                <a href="{{ route('superadmin.plans.index') }}" class="btn" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.82rem;">
                    Կառավարել Փաթեթները
                </a>
            </div>
        </div>

        <!-- 6. Telegram System Settings (Platform Default & Alerts) -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-brands fa-telegram" style="color: #229ed9;"></i>
                <span>6. Telegram Ծանուցումների Կարգավորումներ (Platform Default & Admin Alerts)</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-robot" style="color: #229ed9;"></i> Հարթակի Լռելյայն Telegram Bot Token
                    </label>
                    <input type="password" name="telegram_bot_token" id="saTelegramBotToken" class="form-input" value="{{ old('telegram_bot_token', $settings['telegram_bot_token'] ?? '') }}" placeholder="123456789:ABCdefGHIjklMNOpqr... (@BotFather)">
                    <span class="input-hint">Եթե ռեստորանը չունի սեփական բոտ, ծանուցումները կուղարկվեն այս բոտի միջոցով</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-hashtag" style="color: #229ed9;"></i> SuperAdmin Alert Chat ID / Group ID
                    </label>
                    <input type="text" name="telegram_admin_chat_id" id="saTelegramChatId" class="form-input" value="{{ old('telegram_admin_chat_id', $settings['telegram_admin_chat_id'] ?? '') }}" placeholder="-100123456789 կամ անձնական Chat ID">
                    <span class="input-hint">Այս չատում կստանաք համակարգային թեստեր և ադմինիստրատիվ ազդանշաններ</span>
                </div>
            </div>

            <!-- Test Connection Box -->
            <div class="preview-box" style="margin-top: 1.25rem; background: rgba(34, 158, 217, 0.08); border: 1px dashed rgba(34, 158, 217, 0.35);">
                <div>
                    <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 0.25rem;">
                        <i class="fa-solid fa-paper-plane" style="color: #229ed9;"></i> Ստուգել Telegram Կապը & Ուղարկել Թեստ
                    </strong>
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        Ստուգեք, որ Bot Token-ը և SuperAdmin Chat ID-ն ճիշտ են կարգավորված և հաղորդագրությունը հաջողությամբ հասնում է։
                    </span>
                </div>
                <button type="button" id="btnTestSaTelegram" onclick="testSuperAdminTelegram()" class="btn" style="background: #229ed9; color: #fff; border-radius: 10px; padding: 0.55rem 1.2rem; font-size: 0.85rem; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-paper-plane"></i> Ուղարկել Թեստ
                </button>
            </div>

            <div id="saTelegramTestResult" style="display: none; margin-top: 1rem; border-radius: 12px; padding: 0.85rem 1.15rem; font-size: 0.88rem; font-weight: 600;"></div>
        </div>

        <!-- Submit Button -->
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; margin-bottom: 2.5rem;">
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Պահպանել Բոլոր Կարգավորումները
            </button>
        </div>
    </form>

    <!-- 5. Admin Profile & Security Settings (2FA, Email & Password Change) -->
    <div id="security-section" style="margin-top: 1.5rem; padding-top: 2rem; border-top: 2px solid var(--border-color); margin-bottom: 3rem;" x-data="securityModule()">
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-indigo">
                    <i class="fa-solid fa-shield-halved"></i> Անվտանգություն & 2FA
                </span>
            </div>
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                Ադմինիստրատորի Անվտանգություն & 2FA
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
                Կառավարեք երկփուլային նույնականացումը (2FA), փոխեք էլ․ փոստի հասցեն և մուտքի գաղտնաբառը։
            </p>
        </div>

        <!-- 2FA Management Banner / Card -->
        <div class="settings-card" style="margin-bottom: 1.75rem; background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, rgba(245, 158, 11, 0.04) 100%); border: 1.5px solid {{ auth()->user()->hasTwoFactorEnabled() ? '#10b981' : 'var(--border-color)' }};">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 52px; height: 52px; border-radius: 14px; background: {{ auth()->user()->hasTwoFactorEnabled() ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ auth()->user()->hasTwoFactorEnabled() ? '#10b981' : '#f59e0b' }}; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit';">
                                Երկփուլային Նույնականացում (2FA)
                            </h3>
                            @if(auth()->user()->hasTwoFactorEnabled())
                                <span class="badge badge-emerald" style="font-weight: 700;">
                                    <i class="fa-solid fa-circle-check"></i> Ակտիվ ({{ auth()->user()->two_factor_type === 'authenticator' ? 'Google Authenticator' : 'Email Code' }})
                                </span>
                            @else
                                <span class="badge badge-amber" style="font-weight: 700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Անջատված
                                </span>
                            @endif
                        </div>
                        <p style="margin: 0.3rem 0 0 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
                            Պաշտպանում է ձեր հաշիվը <strong>/login</strong> մուտք գործելիս, ինչպես նաև <strong>գաղտնաբառ</strong> և <strong>էլ․ փոստ</strong> փոխելիս։
                        </p>
                    </div>
                </div>

                <div>
                    @if(auth()->user()->hasTwoFactorEnabled())
                        <button type="button" @click="showDisableModal = true" class="btn btn-secondary" style="border-radius: 12px; font-weight: 700; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                            <i class="fa-solid fa-power-off"></i> Անջատել 2FA-ն
                        </button>
                    @else
                        <button type="button" @click="showEnableModal = true" class="btn btn-primary" style="border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem;">
                            <i class="fa-solid fa-lock"></i> Միացնել 2FA Պաշտպանությունը
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
            <!-- Change Email Form -->
            <div class="settings-card" style="margin-bottom: 0;">
                <div class="settings-section-title">
                    <i class="fa-solid fa-envelope" style="color: #6366f1;"></i>
                    <span>Էլ․ Փոստի Փոփոխություն (2FA)</span>
                </div>
                <form action="{{ route('superadmin.settings.security') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" value="email">

                    <div style="display: flex; flex-direction: column; gap: 1.15rem;">
                        <div class="form-group">
                            <label class="form-label">Ընթացիկ Էլ․ Փոստ</label>
                            <input type="text" value="{{ auth()->user()->email }}" disabled class="form-input" style="opacity: 0.7; cursor: not-allowed;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Նոր Էլ․ Փոստ *</label>
                            <input type="email" name="email" required class="form-input" placeholder="new-admin@qrmenu.local" value="{{ old('action_type') === 'email' ? old('email') : '' }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ընթացիկ Գաղտնաբառ *</label>
                            <input type="password" name="current_password" required class="form-input" placeholder="••••••••">
                        </div>

                        <!-- 2FA Code Field -->
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                <label class="form-label" style="margin-bottom: 0;">
                                    2FA Անվտանգության Կոդ {{ auth()->user()->hasTwoFactorEnabled() ? '*' : '(եթե ունեք)' }}
                                </label>
                                <button type="button" @click="sendCode('email', $event)" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                    <i class="fa-solid fa-paper-plane" style="color: #f59e0b;"></i> <span x-text="countdownEmail > 0 ? 'Սպասեք ' + countdownEmail + 'վ' : 'Ուղարկել 2FA կոդ'">Ուղարկել 2FA կոդ</span>
                                </button>
                            </div>
                            <input type="text" name="two_factor_code" {{ auth()->user()->hasTwoFactorEnabled() ? 'required' : '' }} class="form-input" placeholder="6-նիշ կոդ (Email կամ Authenticator)" maxlength="8" autocomplete="one-time-code">
                            <div x-show="msgEmail" x-text="msgEmail" style="font-size: 0.78rem; color: #10b981; margin-top: 0.35rem; font-weight: 600;"></div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem;">
                                <i class="fa-solid fa-check"></i> Թարմացնել Էլ․ Փոստը
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="settings-card" style="margin-bottom: 0;">
                <div class="settings-section-title">
                    <i class="fa-solid fa-lock" style="color: #10b981;"></i>
                    <span>Գաղտնաբառի Փոփոխություն (2FA)</span>
                </div>
                <form action="{{ route('superadmin.settings.security') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" value="password">

                    <div style="display: flex; flex-direction: column; gap: 1.15rem;">
                        <div class="form-group">
                            <label class="form-label">Ընթացիկ Գաղտնաբառ *</label>
                            <input type="password" name="current_password" required class="form-input" placeholder="••••••••">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Նոր Գաղտնաբառ * (նվազագույնը 6 նիշ)</label>
                            <input type="password" name="password" required minlength="6" class="form-input" placeholder="••••••••">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Հաստատել Նոր Գաղտնաբառը *</label>
                            <input type="password" name="password_confirmation" required minlength="6" class="form-input" placeholder="••••••••">
                        </div>

                        <!-- 2FA Code Field -->
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                <label class="form-label" style="margin-bottom: 0;">
                                    2FA Անվտանգության Կոդ {{ auth()->user()->hasTwoFactorEnabled() ? '*' : '(եթե ունեք)' }}
                                </label>
                                <button type="button" @click="sendCode('password', $event)" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                    <i class="fa-solid fa-paper-plane" style="color: #f59e0b;"></i> <span x-text="countdownPass > 0 ? 'Սպասեք ' + countdownPass + 'վ' : 'Ուղարկել 2FA կոդ'">Ուղարկել 2FA կոդ</span>
                                </button>
                            </div>
                            <input type="text" name="two_factor_code" {{ auth()->user()->hasTwoFactorEnabled() ? 'required' : '' }} class="form-input" placeholder="6-նիշ կոդ (Email կամ Authenticator)" maxlength="8" autocomplete="one-time-code">
                            <div x-show="msgPass" x-text="msgPass" style="font-size: 0.78rem; color: #10b981; margin-top: 0.35rem; font-weight: 600;"></div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem;">
                                <i class="fa-solid fa-key"></i> Փոխել Գաղտնաբառը
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ENABLE 2FA -->
        <div x-show="showEnableModal" x-transition.opacity class="modern-modal-overlay" style="display: none;">
            <div class="modern-modal-box" @click.outside="showEnableModal = false" style="max-width: 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="width: 38px; height: 38px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <h3 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            Միացնել 2FA Պաշտպանությունը
                        </h3>
                    </div>
                    <button type="button" @click="showEnableModal = false" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- 2FA Type Switcher -->
                <div style="display: flex; gap: 0.5rem; background: var(--bg-body); padding: 0.35rem; border-radius: 12px; margin-bottom: 1.25rem;">
                    <button type="button" @click="enableType = 'email'" :class="enableType === 'email' ? 'btn btn-primary' : 'btn btn-secondary'" style="flex: 1; border-radius: 10px; font-size: 0.85rem; justify-content: center; padding: 0.55rem;">
                        <i class="fa-solid fa-envelope"></i> Email Կոդով
                    </button>
                    <button type="button" @click="enableType = 'authenticator'" :class="enableType === 'authenticator' ? 'btn btn-primary' : 'btn btn-secondary'" style="flex: 1; border-radius: 10px; font-size: 0.85rem; justify-content: center; padding: 0.55rem;">
                        <i class="fa-solid fa-mobile-screen-button"></i> Authenticator App
                    </button>
                </div>

                <form action="{{ route('superadmin.settings.security') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" value="2fa_enable">
                    <input type="hidden" name="type" :value="enableType">
                    <input type="hidden" name="secret" value="{{ $setupSecret ?? '' }}">

                    <!-- Authenticator Info & QR -->
                    <div x-show="enableType === 'authenticator'" style="margin-bottom: 1.25rem; text-align: center;">
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                            Սկանավորեք այս QR կոդը <strong>Google Authenticator</strong> (կամ Authy) հավելվածով․
                        </p>
                        <div style="display: inline-block; background: #ffffff; padding: 12px; border-radius: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); border: 1px solid var(--border-color); margin-bottom: 0.75rem;">
                            <img src="{{ $qrCodeUrl ?? '' }}" alt="2FA QR Code" style="width: 170px; height: 170px; display: block;">
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                            Կամ մուտքագրեք գաղտնի բանալին ձեռքով՝
                            <div style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: #f59e0b; margin-top: 0.25rem; letter-spacing: 0.1em; user-select: all;">
                                {{ $setupSecret ?? '' }}
                            </div>
                        </div>
                    </div>

                    <!-- Email Info -->
                    <div x-show="enableType === 'email'" style="margin-bottom: 1.25rem;">
                        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 12px; padding: 0.85rem 1rem; font-size: 0.84rem; color: var(--text-secondary); margin-bottom: 1rem;">
                            <i class="fa-solid fa-circle-info" style="color: #f59e0b; margin-right: 0.35rem;"></i>
                            Հաստատման կոդը կուղարկվի <strong>{{ auth()->user()->maskedEmail() }}</strong> հասցեին։
                        </div>
                        <button type="button" @click="sendCode('setup', $event)" class="btn btn-secondary" style="width: 100%; justify-content: center; border-radius: 10px; font-weight: 600; font-size: 0.86rem; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-paper-plane" style="color: #f59e0b;"></i> <span x-text="countdownSetup > 0 ? 'Սպասեք ' + countdownSetup + 'վ' : 'Ուղարկել Ստուգիչ Կոդ'">Ուղարկել Ստուգիչ Կոդ</span>
                        </button>
                        <div x-show="msgSetup" x-text="msgSetup" style="font-size: 0.78rem; color: #10b981; font-weight: 600; text-align: center;"></div>
                    </div>

                    <!-- Test Code Input -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label class="form-label">Մուտքագրեք 6-նիշ Ստուգիչ Կոդը *</label>
                        <input type="text" name="code" required class="form-input" placeholder="••••••" maxlength="8" style="font-family: monospace; font-size: 1.3rem; letter-spacing: 0.3em; text-align: center;">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" class="btn btn-secondary" @click="showEnableModal = false">Չեղարկել</button>
                        <button type="submit" class="btn btn-primary" style="font-weight: 700;">
                            <i class="fa-solid fa-shield-check"></i> Հաստատել և Միացնել
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: DISABLE 2FA -->
        <div x-show="showDisableModal" x-transition.opacity class="modern-modal-overlay" style="display: none;">
            <div class="modern-modal-box" @click.outside="showDisableModal = false" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-family: 'Outfit'; font-size: 1.2rem; font-weight: 800; color: #ef4444; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Անջատել 2FA-ն
                    </h3>
                    <button type="button" @click="showDisableModal = false" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 1.25rem;">
                    Երկփուլային նույնականացումն անջատելու դեպքում ձեր հաշվի պաշտպանության մակարդակը կնվազի։ Հաստատելու համար մուտքագրեք ընթացիկ գաղտնաբառը․
                </p>

                <form action="{{ route('superadmin.settings.security') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action_type" value="2fa_disable">

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label class="form-label">Ընթացիկ Գաղտնաբառ *</label>
                        <input type="password" name="current_password" required class="form-input" placeholder="••••••••">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" class="btn btn-secondary" @click="showDisableModal = false">Չեղարկել</button>
                        <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444; font-weight: 700;">
                            Այո, Անջատել 2FA-ն
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function securityModule() {
    return {
        showEnableModal: false,
        showDisableModal: false,
        enableType: 'email',
        countdownEmail: 0,
        countdownPass: 0,
        countdownSetup: 0,
        msgEmail: '',
        msgPass: '',
        msgSetup: '',

        async sendCode(action, event) {
            try {
                const res = await fetch('{{ route('security.2fa.send_code') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ action: action })
                });
                const data = await res.json();
                if (data.success) {
                    if (action === 'email') {
                        this.msgEmail = data.message;
                        this.startTimer('email');
                    } else if (action === 'password') {
                        this.msgPass = data.message;
                        this.startTimer('pass');
                    } else if (action === 'setup') {
                        this.msgSetup = data.message;
                        this.startTimer('setup');
                    }
                }
            } catch (err) {
                console.error(err);
            }
        },

        startTimer(type) {
            let count = 60;
            if (type === 'email') this.countdownEmail = count;
            if (type === 'pass') this.countdownPass = count;
            if (type === 'setup') this.countdownSetup = count;

            const timer = setInterval(() => {
                count--;
                if (type === 'email') this.countdownEmail = count;
                if (type === 'pass') this.countdownPass = count;
                if (type === 'setup') this.countdownSetup = count;

                if (count <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        }
    }
}

function testSuperAdminTelegram() {
    const btn = document.getElementById('btnTestSaTelegram');
    const resultBox = document.getElementById('saTelegramTestResult');
    const chatId = document.getElementById('saTelegramChatId')?.value;
    const botToken = document.getElementById('saTelegramBotToken')?.value;

    if (!chatId || !chatId.trim()) {
        resultBox.style.display = 'block';
        resultBox.style.background = 'rgba(239, 68, 68, 0.12)';
        resultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        resultBox.style.color = '#ef4444';
        resultBox.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Խնդրում ենք լրացնել SuperAdmin Chat ID դաշտը։';
        return;
    }

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ուղարկվում է...';
    resultBox.style.display = 'none';

    fetch('{{ route("superadmin.settings.telegram.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            chat_id: chatId.trim(),
            bot_token: botToken ? botToken.trim() : null,
        }),
    })
    .then(res => res.json())
    .then(data => {
        resultBox.style.display = 'block';
        if (data.success) {
            resultBox.style.background = 'rgba(16, 185, 129, 0.12)';
            resultBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            resultBox.style.color = '#10b981';
            resultBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + (data.message || 'Թեստային հաղորդագրությունը հաջողությամբ ուղարկվեց։');
        } else {
            resultBox.style.background = 'rgba(239, 68, 68, 0.12)';
            resultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            resultBox.style.color = '#ef4444';
            resultBox.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + (data.message || 'Սխալ՝ չհաջողվեց ուղարկել հաղորդագրությունը։');
        }
    })
    .catch(err => {
        resultBox.style.display = 'block';
        resultBox.style.background = 'rgba(239, 68, 68, 0.12)';
        resultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        resultBox.style.color = '#ef4444';
        resultBox.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Կապի խափանում. ' + err.message;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateLiveSocialPreview() {
    const titleInput = document.getElementById('input_seo_title');
    const descInput = document.getElementById('input_seo_desc');
    const mockupTitle = document.getElementById('mockup_title');
    const mockupDesc = document.getElementById('mockup_desc');

    if (titleInput && mockupTitle) {
        mockupTitle.innerText = titleInput.value.trim() || '{{ \App\Models\SystemSetting::getSeoTitle() }}';
    }
    if (descInput && mockupDesc) {
        mockupDesc.innerText = descInput.value.trim() || '{{ Str::limit(\App\Models\SystemSetting::getSeoDescription(), 110) }}';
    }
}
</script>
@endsection
