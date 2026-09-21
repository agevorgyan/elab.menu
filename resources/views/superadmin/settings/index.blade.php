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

    <form action="{{ route('superadmin.settings.update') }}" method="POST">
        @csrf

        <!-- 1. Contacts Section -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-headset" style="color: #f59e0b;"></i>
                <span>1. Պաշտոնական Կոնտակտներ (Լենդինգ և Աջակցություն)</span>
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

        <!-- 2. Social Media Links -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-share-nodes" style="color: #3b82f6;"></i>
                <span>2. Սոցիալական Ցանցեր</span>
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

        <!-- 3. Landing Page Experience & Demo -->
        <div class="settings-card">
            <div class="settings-section-title">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: #10b981;"></i>
                <span>3. Լենդինգի Գործառույթներ & Դեմո Ռեստորան</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-utensils"></i> Դեմո Ռեստորանի Slug *
                    </label>
                    <input type="text" name="demo_vendor_slug" class="form-input" value="{{ old('demo_vendor_slug', $settings['demo_vendor_slug'] ?? 'bistro-yerevan') }}" placeholder="bistro-yerevan">
                    <span class="input-hint">Լենդինգի «Տեսնել Դեմոն» կոճակը կբացի այս վենդորի մենյուն (/m/{slug})</span>
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

        <!-- Submit Button -->
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; margin-bottom: 3rem;">
            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Պահպանել Բոլոր Կարգավորումները
            </button>
        </div>
    </form>
</div>
@endsection
