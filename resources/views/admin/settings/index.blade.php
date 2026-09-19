@extends('layouts.app')

@section('title', __('Ռեստորանի Կարգավորումներ') . ' - ' . $vendor->name)

@section('content')
<div style="max-width: 1050px; margin: 0 auto;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem;">
                <span style="background: rgba(245, 158, 11, 0.15); color: var(--primary); width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                {{ __('Կարգավորումներ') }}
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0 0;">
                {{ __('Կառավարեք մասնաճյուղի հանրային տվյալները, իրավաբանական տեղեկությունները, սպասարկման վճարը և առաքման պարամետրերը') }}
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug]) }}" target="_blank" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 600;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> {{ __('Դիտել մենյուն') }}
            </a>
            <button type="submit" form="vendorSettingsForm" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem;">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('Պահպանել') }}
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; color: #ef4444;">
            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> {{ __('Ուշադրություն. Լրացված տվյալներում առկա են սխալներ') }}
            </div>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.88rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" id="vendorSettingsForm">
        @csrf
        @if($location)
            <input type="hidden" name="location_id" value="{{ $location->id }}">
        @endif

        <div style="display: grid; grid-template-columns: 1fr; gap: 1.75rem;">

            <!-- 1. BRANCH & STOREFRONT PUBLIC INFO CARD -->
            <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.75rem; box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-store"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Մասնաճյուղի և Մենյուի Տեղեկություն') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted);">
                                {{ __('Այս տվյալները հասանելի են հաճախորդներին մենյույի «Տեղեկություն» բաժնում') }}
                            </p>
                        </div>
                    </div>

                    <span style="font-size: 0.78rem; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-eye"></i> {{ __('Երևում է մենյուում') }}
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                    <!-- Ֆիրմային անվանում -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Ֆիրմային անվանում') }} <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="name" value="{{ old('name', $location?->name ?? $vendor->name) }}" required class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-utensils"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Ռեստորանի կամ մասնաճյուղի հանրային անվանումը') }}
                        </span>
                    </div>

                    <!-- Հասցե -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Հասցե') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="address" value="{{ old('address', $location?->address ?? ($vendor->operating_address ?? $vendor->legal_address)) }}" class="form-control" placeholder="Օրինակ՝ ք. Երևան, Ամիրյան 18" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Մասնաճյուղի փաստացի գտնվելու հասցեն') }}
                        </span>
                    </div>

                    <!-- Հեռախոսահամար -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Հեռախոսահամար') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="phone" value="{{ old('phone', $location?->phone ?? $vendor->phone) }}" class="form-control" placeholder="+374 10 123456" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Հաճախորդների զանգերի համար նախատեսված համար') }}
                        </span>
                    </div>

                    <!-- WhatsApp -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            WhatsApp
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $location?->whatsapp_number ?? $vendor->phone) }}" class="form-control" placeholder="+374 91 123456" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: #25d366; font-size: 1.1rem;">
                                <i class="fa-brands fa-whatsapp"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Այս համարին կուղարկվեն WhatsApp պատվերները') }}
                        </span>
                    </div>

                    <!-- Wi-Fi SSID -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Wi-Fi ցանց (SSID)') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="wifi_ssid" value="{{ old('wifi_ssid', $location?->wifi_ssid ?? ($vendor->wifi_ssid ?? ($vendor->name . ' Guest'))) }}" class="form-control" placeholder="Օրինակ՝ Bistro_Guest_WiFi" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--primary); font-size: 1rem;">
                                <i class="fa-solid fa-wifi"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Հյուրերի Wi-Fi ցանցի անվանումը') }}
                        </span>
                    </div>

                    <!-- Wi-Fi Password -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Wi-Fi գաղտնաբառ') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="wifi_password" value="{{ old('wifi_password', $location?->wifi_password ?? ($vendor->wifi_password ?? 'guest' . str_pad($vendor->id, 4, '0', STR_PAD_LEFT))) }}" class="form-control" placeholder="Գաղտնաբառ" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--primary); font-size: 1rem;">
                                <i class="fa-solid fa-key"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Հաճախորդը կարող է պատճենել այն մեկ սեղմումով') }}
                        </span>
                    </div>

                    <!-- Աշխատանքային օրեր և ժամեր -->
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Աշխատանքային օրեր և ժամեր') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="working_hours" value="{{ old('working_hours', $location?->working_hours ?? ($vendor->working_hours ?? '10:00 - 23:00 (Ամեն օր / Daily)')) }}" class="form-control" placeholder="Օրինակ՝ 10:00 - 23:00 (Ամեն օր / Daily)" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--primary); font-size: 1rem;">
                                <i class="fa-solid fa-clock"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Օրինակ՝ «Երկ-Կիրակի՝ 10:00 - 23:00»') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. LEGAL & CONTACT INFORMATION CARD (SUPERADMIN) -->
            <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.75rem; box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(99, 102, 241, 0.15); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Իրավաբանական և Կոնտակտային Տվյալներ') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted);">
                                {{ __('Տվյալները հասանելի են հարթակի գլխավոր ադմինիստրատորին (SuperAdmin)') }}
                            </p>
                        </div>
                    </div>

                    <span style="font-size: 0.78rem; font-weight: 700; background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-shield-halved"></i> {{ __('Երևում է սուպերադմինում') }}
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                    <!-- Իրավաբանական անվանում -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Իրավաբանական անվանում') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}" class="form-control" placeholder="Օրինակ՝ «Բիստրո Գրուպ» ՍՊԸ" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-building"></i>
                            </span>
                        </div>
                    </div>

                    <!-- ՀՎՀՀ -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('ՀՎՀՀ (Tax ID)') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="tax_id" value="{{ old('tax_id', $vendor->tax_id) }}" class="form-control" placeholder="02589412" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-receipt"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Գործունեության հասցեն -->
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Գործունեության հասցեն') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="operating_address" value="{{ old('operating_address', $vendor->operating_address ?? ($location?->address ?? $vendor->legal_address)) }}" class="form-control" placeholder="Փաստացի գործունեության հասցե" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-map-pin"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Տնօրեն, հեռախոսահամար -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Տնօրեն (Անուն, Ազգանուն)') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="director_name" value="{{ old('director_name', $vendor->director_name) }}" class="form-control" placeholder="Տնօրենի Անուն" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Տնօրենի հեռախոսահամար') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="director_phone" value="{{ old('director_phone', $vendor->director_phone) }}" class="form-control" placeholder="+374 91 000000" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-mobile-screen"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Մենեջեր, հեռախոսահամար -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Մենեջեր / Կոնտակտային անձ') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $vendor->contact_person_name) }}" class="form-control" placeholder="Մենեջերի Անուն" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-user-gear"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Մենեջերի հեռախոսահամար') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="contact_person_phone" value="{{ old('contact_person_phone', $vendor->contact_person_phone) }}" class="form-control" placeholder="+374 93 000000" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-mobile-screen"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. SERVICE FEE CARD -->
            <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.75rem; box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-bell-concierge"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Սպասարկման Վճար (Ռեստորանում)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted);">
                                {{ __('Գանձվում է ռեստորանում (Dine-in / Սեղանի մոտ) գտնվող հյուրերի պատվերներից') }}
                            </p>
                        </div>
                    </div>

                    <!-- Enable Switch -->
                    <label class="modern-switch-wrapper" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; user-select: none;">
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);" id="serviceFeeStatusLabel">
                            {{ old('service_fee_enabled', $vendor->service_fee_enabled) ? __('Ակտիվ է') : __('Անջատված է') }}
                        </span>
                        <input type="checkbox" name="service_fee_enabled" value="1" id="serviceFeeToggle" {{ old('service_fee_enabled', $vendor->service_fee_enabled) ? 'checked' : '' }} onchange="toggleServiceFeeFields()" style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer;">
                    </label>
                </div>

                <div id="serviceFeeContainer" style="{{ old('service_fee_enabled', $vendor->service_fee_enabled) ? '' : 'opacity: 0.55; pointer-events: none;' }}; transition: opacity 0.2s ease;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                        <!-- Fee Type -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Վճարի Տեսակ') }}
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <label class="type-pill {{ old('service_fee_type', $vendor->service_fee_type ?? 'percent') === 'percent' ? 'selected' : '' }}" id="pillPercent" style="cursor: pointer; border: 1.5px solid var(--border-color); border-radius: 12px; padding: 0.75rem; display: flex; align-items: center; gap: 0.5rem; justify-content: center; font-weight: 700; font-size: 0.88rem; transition: all 0.2s;">
                                    <input type="radio" name="service_fee_type" value="percent" {{ old('service_fee_type', $vendor->service_fee_type ?? 'percent') === 'percent' ? 'checked' : '' }} onchange="handleFeeTypeChange('percent')" style="display: none;">
                                    <i class="fa-solid fa-percent"></i> {{ __('Տոկոսային (%)') }}
                                </label>
                                <label class="type-pill {{ old('service_fee_type', $vendor->service_fee_type) === 'fixed' ? 'selected' : '' }}" id="pillFixed" style="cursor: pointer; border: 1.5px solid var(--border-color); border-radius: 12px; padding: 0.75rem; display: flex; align-items: center; gap: 0.5rem; justify-content: center; font-weight: 700; font-size: 0.88rem; transition: all 0.2s;">
                                    <input type="radio" name="service_fee_type" value="fixed" {{ old('service_fee_type', $vendor->service_fee_type) === 'fixed' ? 'checked' : '' }} onchange="handleFeeTypeChange('fixed')" style="display: none;">
                                    <i class="fa-solid fa-coins"></i> {{ __('Ֆիքսված գումար') }}
                                </label>
                            </div>
                        </div>

                        <!-- Fee Value -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Սպասարկման վճարի չափը') }} <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="service_fee_value" id="serviceFeeValue" value="{{ old('service_fee_value', $vendor->service_fee_value ?? 10) }}" required class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span id="serviceFeeSuffix" style="position: absolute; right: 1rem; font-weight: 800; color: var(--primary); font-size: 0.95rem;">
                                    {{ old('service_fee_type', $vendor->service_fee_type ?? 'percent') === 'percent' ? '%' : $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Օրինակ՝ 10% կամ 500 AMD') }}
                            </span>
                        </div>

                        <!-- Minimum Order for Service Fee -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Նվազագույն պատվերի գումար') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="service_fee_min_order" id="serviceFeeMinOrder" value="{{ old('service_fee_min_order', $vendor->service_fee_min_order) }}" placeholder="0" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span style="position: absolute; right: 1rem; font-weight: 800; color: var(--text-muted); font-size: 0.85rem;">
                                    {{ $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Վճարը կգործի միայն եթե պատվերը գերազանցում է այս գումարը (դատարկ = բոլոր պատվերներին)') }}
                            </span>
                        </div>
                    </div>

                    <!-- Live Calculation Preview Card -->
                    <div style="margin-top: 1.25rem; background: var(--bg-body); border: 1px dashed var(--border-color); border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <span style="color: var(--primary); font-size: 1.1rem;"><i class="fa-solid fa-calculator"></i></span>
                            <span style="font-size: 0.88rem; color: var(--text-muted);">
                                {{ __('Հաշվարկման օրինակ 15,000') }} {{ $vendor->currency }} {{ __('պատվերի դեպքում՝') }}
                            </span>
                        </div>
                        <div style="font-size: 0.95rem; font-weight: 800; color: var(--text-main);" id="serviceFeePreviewBox">
                            —
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. DELIVERY SETTINGS CARD -->
            <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 1.75rem; box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-motorcycle"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Առաքման Ծառայության Կարգավորումներ') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted);">
                                {{ __('Սահմանեք առաքման վճարը, նվազագույն պատվերը և անվճար առաքման շեմը') }}
                            </p>
                        </div>
                    </div>

                    <!-- Enable Switch -->
                    <label class="modern-switch-wrapper" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; user-select: none;">
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);" id="deliveryStatusLabel">
                            {{ old('delivery_enabled', $vendor->delivery_enabled) ? __('Ակտիվ է') : __('Անջատված է') }}
                        </span>
                        <input type="checkbox" name="delivery_enabled" value="1" id="deliveryToggle" {{ old('delivery_enabled', $vendor->delivery_enabled) ? 'checked' : '' }} onchange="toggleDeliveryFields()" style="width: 20px; height: 20px; accent-color: #3b82f6; cursor: pointer;">
                    </label>
                </div>

                <div id="deliveryContainer" style="{{ old('delivery_enabled', $vendor->delivery_enabled) ? '' : 'opacity: 0.55; pointer-events: none;' }}; transition: opacity 0.2s ease;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                        <!-- Delivery Fee -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Առաքման Վճար') }} <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="delivery_fee" id="deliveryFeeInput" value="{{ old('delivery_fee', $vendor->delivery_fee ?? 0) }}" required class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span style="position: absolute; right: 1rem; font-weight: 800; color: #3b82f6; font-size: 0.85rem;">
                                    {{ $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Ստանդարտ ֆիքսված վճար առաքման համար (0 = անվճար)') }}
                            </span>
                        </div>

                        <!-- Minimum Order for Delivery -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Նվազագույն պատվերի գումար') }} <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="delivery_min_amount" id="deliveryMinAmount" value="{{ old('delivery_min_amount', $vendor->delivery_min_amount ?? 0) }}" required class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span style="position: absolute; right: 1rem; font-weight: 800; color: var(--text-muted); font-size: 0.85rem;">
                                    {{ $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Առաքման նվազագույն շեմ (0 = առանց սահմանափակման)') }}
                            </span>
                        </div>

                        <!-- Free Delivery From Threshold -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Անվճար առաքում սկսած') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="delivery_free_from" id="deliveryFreeFrom" value="{{ old('delivery_free_from', $vendor->delivery_free_from) }}" placeholder="Առանց անվճարի" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span style="position: absolute; right: 1rem; font-weight: 800; color: #10b981; font-size: 0.85rem;">
                                    {{ $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Այս գումարը գերազանցելու դեպքում առաքումը կդառնա 0 AMD (դատարկ = միշտ վճարովի)') }}
                            </span>
                        </div>
                    </div>

                    <!-- Storefront Motivation Banner Preview -->
                    <div style="margin-top: 1.25rem; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                                <i class="fa-solid fa-gift"></i>
                            </span>
                            <div>
                                <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main);" id="deliveryPreviewText">
                                    {{ __('Անվճար առաքման հուշում զամբյուղում') }}
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);" id="deliveryPreviewSubtext">
                                    {{ __('Հաճախորդը կտեսնի պրոգրես բար, որը խթանում է պատվերի գումարի աճը') }}
                                </div>
                            </div>
                        </div>
                        <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981; font-weight: 800; border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                            <i class="fa-solid fa-sparkles"></i> {{ __('Խթանիչ Ֆունկցիա') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bottom Save Bar -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 0.5rem; margin-bottom: 3rem;">
                <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.6rem; border-radius: 14px; font-weight: 700; padding: 0.85rem 2.2rem; font-size: 1rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3);">
                    <i class="fa-solid fa-check"></i> {{ __('Պահպանել Բոլոր Կարգավորումները') }}
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.type-pill:hover {
    border-color: var(--primary) !important;
}
.type-pill.selected {
    border-color: var(--primary) !important;
    background: rgba(245, 158, 11, 0.12) !important;
    color: var(--primary) !important;
}
</style>

<script>
const CURRENCY = "{{ $vendor->currency }}";

function toggleServiceFeeFields() {
    const isChecked = document.getElementById('serviceFeeToggle').checked;
    const container = document.getElementById('serviceFeeContainer');
    const label = document.getElementById('serviceFeeStatusLabel');
    if (isChecked) {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        label.textContent = "{{ __('Ակտիվ է') }}";
    } else {
        container.style.opacity = '0.55';
        container.style.pointerEvents = 'none';
        label.textContent = "{{ __('Անջատված է') }}";
    }
    updateCalculationsPreview();
}

function toggleDeliveryFields() {
    const isChecked = document.getElementById('deliveryToggle').checked;
    const container = document.getElementById('deliveryContainer');
    const label = document.getElementById('deliveryStatusLabel');
    if (isChecked) {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        label.textContent = "{{ __('Ակտիվ է') }}";
    } else {
        container.style.opacity = '0.55';
        container.style.pointerEvents = 'none';
        label.textContent = "{{ __('Անջատված է') }}";
    }
    updateCalculationsPreview();
}

function handleFeeTypeChange(type) {
    const pillPercent = document.getElementById('pillPercent');
    const pillFixed = document.getElementById('pillFixed');
    const suffix = document.getElementById('serviceFeeSuffix');

    if (type === 'percent') {
        pillPercent.classList.add('selected');
        pillFixed.classList.remove('selected');
        suffix.textContent = '%';
    } else {
        pillFixed.classList.add('selected');
        pillPercent.classList.remove('selected');
        suffix.textContent = CURRENCY;
    }
    updateCalculationsPreview();
}

function updateCalculationsPreview() {
    // Service fee calculation for sample 15,000 AMD
    const sampleSubtotal = 15000;
    const isServiceEnabled = document.getElementById('serviceFeeToggle').checked;
    const serviceFeeType = document.querySelector('input[name="service_fee_type"]:checked')?.value || 'percent';
    const feeVal = parseFloat(document.getElementById('serviceFeeValue').value) || 0;
    const minOrder = parseFloat(document.getElementById('serviceFeeMinOrder').value) || 0;
    const previewBox = document.getElementById('serviceFeePreviewBox');

    if (!isServiceEnabled) {
        previewBox.innerHTML = '<span style="color: var(--text-muted);">{{ __("Անջատված է") }}</span>';
    } else if (minOrder > 0 && sampleSubtotal < minOrder) {
        previewBox.innerHTML = `<span style="color: #f59e0b;">0 ${CURRENCY} (Նվազագույն շեմը՝ ${minOrder.toLocaleString()} ${CURRENCY})</span>`;
    } else {
        let calc = 0;
        if (serviceFeeType === 'percent') {
            calc = Math.round((sampleSubtotal * feeVal) / 100);
            previewBox.innerHTML = `<span style="color: #10b981;">+${calc.toLocaleString()} ${CURRENCY} (${feeVal}%)</span> &rarr; Ընդամենը՝ ${(sampleSubtotal + calc).toLocaleString()} ${CURRENCY}`;
        } else {
            calc = feeVal;
            previewBox.innerHTML = `<span style="color: #10b981;">+${calc.toLocaleString()} ${CURRENCY} (Ֆիքսված)</span> &rarr; Ընդամենը՝ ${(sampleSubtotal + calc).toLocaleString()} ${CURRENCY}`;
        }
    }

    // Delivery preview
    const isDeliveryEnabled = document.getElementById('deliveryToggle').checked;
    const deliveryFee = parseFloat(document.getElementById('deliveryFeeInput').value) || 0;
    const deliveryMin = parseFloat(document.getElementById('deliveryMinAmount').value) || 0;
    const freeFrom = parseFloat(document.getElementById('deliveryFreeFrom').value) || 0;

    const previewText = document.getElementById('deliveryPreviewText');
    const previewSubtext = document.getElementById('deliveryPreviewSubtext');

    if (!isDeliveryEnabled) {
        previewText.textContent = "{{ __('Առաքման ծառայությունը ներկայումս անջատված է') }}";
        previewSubtext.textContent = "{{ __('Հաճախորդները չեն կարողանա ընտրել առաքում զամբյուղում') }}";
    } else {
        let minText = deliveryMin > 0 ? `Նվազագույն պատվեր՝ ${deliveryMin.toLocaleString()} ${CURRENCY}: ` : '';
        if (freeFrom > 0) {
            previewText.textContent = `${minText}Առաքման վճար՝ ${deliveryFee.toLocaleString()} ${CURRENCY}, իսկ ${freeFrom.toLocaleString()} ${CURRENCY}-ից սկսած՝ ԱՆՎՃԱՐ 🎉`;
            previewSubtext.textContent = "{{ __('Հաճախորդի զամբյուղում կերևա առաջընթացի սանդղակ (Progress Bar)') }}";
        } else {
            previewText.textContent = `${minText}Առաքման ֆիքսված վճար՝ ${deliveryFee.toLocaleString()} ${CURRENCY}`;
            previewSubtext.textContent = "{{ __('Առաքումը միշտ վճարովի է') }}";
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateCalculationsPreview();
});
</script>
@endsection
