@extends('layouts.app')

@section('title', __('Ռեստորանի Կարգավորումներ') . ' - ' . $vendor->name)

@section('content')
<div style="max-width: 1050px; margin: 0 auto; width: 100%; box-sizing: border-box;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="min-width: 0; flex: 1;">
            <h1 style="font-family: 'Outfit', sans-serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="background: rgba(245, 158, 11, 0.15); color: var(--primary); width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <span>{{ __('Կարգավորումներ') }}</span>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0 0; word-break: break-word;">
                {{ __('Կառավարեք մասնաճյուղի հանրային տվյալները, իրավաբանական տեղեկությունները, սպասարկման վճարը և առաքման պարամետրերը') }}
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('admin.profile') }}" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-user-shield" style="color: {{ Auth::user()->hasTwoFactorEnabled() ? '#10b981' : '#f59e0b' }};"></i> {{ __('Անվտանգություն & 2FA') }}
            </a>
            <a href="{{ route('client.menu', ['vendor_slug' => $vendor->slug]) }}" target="_blank" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> {{ __('Դիտել մենյուն') }}
            </a>
            <button type="submit" form="vendorSettingsForm" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; border-radius: 12px; font-weight: 700; padding: 0.65rem 1.4rem; font-size: 0.92rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3);">
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
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-store"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Մասնաճյուղի և Մենյուի Տեղեկություն') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Այս տվյալները հասանելի են հաճախորդներին մենյույի «Տեղեկություն» բաժնում') }}
                            </p>
                        </div>
                    </div>

                    <span style="font-size: 0.78rem; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-eye"></i> {{ __('Երևում է մենյուում') }}
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
                    <!-- Ֆիրմային անվանում -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Ֆիրմային անվանում') }} <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="name" value="{{ old('name', $location?->name ?? $vendor->name) }}" required class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin: 0;">
                                WhatsApp
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 0.45rem; cursor: pointer; user-select: none;">
                                <input type="checkbox" name="allow_whatsapp_orders" value="1" {{ old('allow_whatsapp_orders', ($location?->allow_whatsapp_orders ?? $vendor->allow_whatsapp_orders ?? true)) ? 'checked' : '' }} style="width: 17px; height: 17px; accent-color: #22c55e; cursor: pointer;">
                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-main);">{{ __('Կոճակն Ակտիվ է') }}</span>
                            </label>
                        </div>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $location?->whatsapp_number ?? $vendor->phone) }}" class="form-control" placeholder="+374 91 123456" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: #25d366; font-size: 1.1rem;">
                                <i class="fa-brands fa-whatsapp"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Այս համարին կուղարկվեն WhatsApp պատվերները (անջատելու դեպքում կոճակը չի երևա զամբյուղում)') }}
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

                    <!-- Desktop Screen Max Width -->
                    <div class="form-group" style="grid-column: 1 / -1; border-top: 1px dashed var(--border-color); padding-top: 1.25rem; margin-top: 0.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-desktop" style="color: var(--primary);"></i> {{ __('Էկրանի Լայնություն Դեսքթոփում (Desktop Frame Max-Width)') }}
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem;">
                            @php
                                $dWidth = $vendor->getDesktopMaxWidth();
                            @endphp
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 1.5px solid {{ $dWidth === '480px' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; padding: 0.6rem 0.8rem; cursor: pointer;">
                                <input type="radio" name="desktop_max_width" value="480px" {{ $dWidth === '480px' ? 'checked' : '' }}>
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">480px (Կոմպակտ)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 1.5px solid {{ $dWidth === '600px' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; padding: 0.6rem 0.8rem; cursor: pointer;">
                                <input type="radio" name="desktop_max_width" value="600px" {{ $dWidth === '600px' ? 'checked' : '' }}>
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">600px (Լավագույն)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 1.5px solid {{ $dWidth === '680px' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; padding: 0.6rem 0.8rem; cursor: pointer;">
                                <input type="radio" name="desktop_max_width" value="680px" {{ $dWidth === '680px' ? 'checked' : '' }}>
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">680px (Մեծ Mobile)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 1.5px solid {{ $dWidth === '768px' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; padding: 0.6rem 0.8rem; cursor: pointer;">
                                <input type="radio" name="desktop_max_width" value="768px" {{ $dWidth === '768px' ? 'checked' : '' }}>
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">768px (Պլանշետ)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-body); border: 1.5px solid {{ $dWidth === '100%' ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 10px; padding: 0.6rem 0.8rem; cursor: pointer;">
                                <input type="radio" name="desktop_max_width" value="100%" {{ $dWidth === '100%' ? 'checked' : '' }}>
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">100% (Full Width)</span>
                            </label>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Համակարգիչներով (Desktop) բացելիս մենյուն չի լղոզվի էկրանով մեկ, այլ կցուցադրվի ընտրված չափի էլեգանտ շրջանակով (կենտրոնացված)։ Լռելյայն՝ 600px:') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. CUSTOM DOMAIN & BRANDING URL (ՍԵՓԱԿԱՆ ԴՈՄԵՆ) -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(6, 182, 212, 0.15); color: #06b6d4; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-globe"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Սեփական Դոմեն (Custom Domain & White-Label)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Կցեք ձեր սեփական դոմենը, որպեսզի մենյուն բացվի ձեր դոմենով (օր.՝ menu.restaurant.am), իսկ ադմինիստրատորները մուտք գործեն ձերդոմեն/admin') }}
                            </p>
                        </div>
                    </div>

                    @if($vendor->hasCustomDomain())
                        <span style="font-size: 0.78rem; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-solid fa-circle-check"></i> {{ __('Ակտիվ Կցված է') }}
                        </span>
                    @else
                        <span style="font-size: 0.78rem; font-weight: 700; background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-solid fa-circle-minus"></i> {{ __('Անջատված է') }}
                        </span>
                    @endif
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end; flex-wrap: wrap;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Դոմենի հասցե (Custom Domain FQDN)') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" name="custom_domain" id="customDomainInput" value="{{ old('custom_domain', $vendor->custom_domain) }}" class="form-control" placeholder="օրինակ՝ menu.restaurant.am կամ restaurant.com" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <span style="position: absolute; left: 1rem; color: #06b6d4; font-size: 1rem;">
                                    <i class="fa-solid fa-link"></i>
                                </span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <button type="button" id="btnCheckDomainDns" class="btn btn-secondary" style="padding: 0.75rem 1rem; font-size: 0.88rem; font-weight: 700; border-radius: 12px; display: inline-flex; align-items: center; gap: 0.5rem; border-color: rgba(6, 182, 212, 0.4); color: #06b6d4; white-space: nowrap;">
                                <i class="fa-solid fa-bolt"></i> {{ __('Ստուգել DNS') }}
                            </button>
                        </div>
                    </div>

                    <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem; display: block;">
                        {{ __('Մուտքագրեք դոմենն առանց http:// կամ https://-ի (օր.՝ menu.restaurant.am): Դատարկ թողնելու դեպքում սեփական դոմենը կանջատվի:') }}
                    </span>

                    <!-- Live Check Result Alert Box -->
                    <div id="dnsCheckResultBox" style="display: none; margin-top: 1rem; padding: 0.85rem 1.1rem; border-radius: 12px; font-size: 0.86rem; font-weight: 600;"></div>

                    <!-- Action Links if domain exists -->
                    @if($vendor->hasCustomDomain())
                        <div style="margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                            <a href="{{ $vendor->getStorefrontUrl() }}" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.45rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square" style="color: #06b6d4;"></i> {{ __('Բացել Մենյուն (Storefront)') }}
                            </a>
                            <a href="{{ $vendor->getAdminUrl() }}" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.45rem;">
                                <i class="fa-solid fa-shield-halved" style="color: #f59e0b;"></i> {{ __('Բացել Ադմինկան (Admin Panel)') }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- DNS & cPanel Setup Instructions Box -->
                <div style="background: rgba(30, 41, 59, 0.4); border: 1px dashed rgba(6, 182, 212, 0.35); border-radius: 16px; padding: 1.25rem;">
                    <div style="font-size: 0.92rem; font-weight: 800; color: #38bdf8; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-info"></i> {{ __('Ինչպե՞ս կցել ձեր դոմենը (DNS և cPanel քայլերը)') }}
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; font-size: 0.84rem; color: var(--text-muted);">
                        <!-- Step 1: DNS -->
                        <div style="background: rgba(15, 23, 42, 0.5); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                                1. DNS Կարգավորում (Cloudflare, Reg.am, etc.)
                            </div>
                            <p style="margin: 0 0 0.5rem 0; line-height: 1.4;">
                                Ավելացրեք <strong>A Record</strong> ձեր դոմենի DNS-ում՝
                            </p>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.3); padding: 0.4rem 0.65rem; border-radius: 6px; font-family: monospace; color: #a5f3fc; font-size: 0.82rem;">
                                <span>A &rarr; {{ $_SERVER['SERVER_ADDR'] ?? gethostbyname('menu.elab.am') }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $_SERVER['SERVER_ADDR'] ?? gethostbyname('menu.elab.am') }}'); alert('IP-ն պատճենվեց');" style="background: none; border: none; color: #38bdf8; cursor: pointer; padding: 2px 4px;" title="Պատճենել IP-ն">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <div style="margin-top: 0.4rem; font-size: 0.78rem;">
                                կամ <strong>CNAME</strong> դեպի <code>menu.elab.am</code>
                            </div>
                        </div>

                        <!-- Step 2: cPanel -->
                        <div style="background: rgba(15, 23, 42, 0.5); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem;">
                                2. cPanel &rarr; Domains
                            </div>
                            <p style="margin: 0 0 0.5rem 0; line-height: 1.4;">
                                cPanel-ում ստեղծեք նոր Domain/Alias և <strong>Document Root</strong>-ը նշեք նույնը (օր.՝ <code>menu.elab.am/public</code> կամ <code>public_html/menu.elab.am/public</code>)։
                            </p>
                            <div style="font-size: 0.78rem; color: #a5f3fc;">
                                <i class="fa-solid fa-shield"></i> cPanel-ի <strong>SSL/TLS Status</strong>-ում գործարկեք <strong>AutoSSL</strong>՝ անվճար HTTPS սերտիֆիկատի համար։
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FEATURED DISH OF THE DAY BANNER CARD (ՕՐՎԱ ՈՒՏԵՍՏ) -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-fire-flame-curved"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span>{{ __('Օրվա Ուտեստի Բաներ') }}</span>
                                <span style="font-size: 0.72rem; font-weight: 700; background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; padding: 0.15rem 0.55rem; border-radius: 6px; letter-spacing: 0.03em;">PROMO BANNER</span>
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Գովազդեք օրվա հատուկ ուտեստը մենյուի ամենասկզբում՝ մեծ և գրավիչ բաներով') }}
                            </p>
                        </div>
                    </div>

                    <!-- Enable/Disable Switch -->
                    <label style="display: inline-flex; align-items: center; gap: 0.75rem; cursor: pointer; background: var(--bg-body); padding: 0.5rem 1rem; border-radius: 14px; border: 1px solid var(--border-color);">
                        <input type="checkbox" name="featured_dish_enabled" value="1" id="featuredDishToggle" {{ old('featured_dish_enabled', $vendor->featured_dish_enabled) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer;">
                        <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-main);">
                            {{ __('Ակտիվացնել մենյուում') }}
                        </span>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
                    <!-- Ընտրել Ուտեստը -->
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Ընտրել Օրվա Ուտեստը') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <select name="featured_product_id" id="featuredProductSelect" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                                <option value="">-- {{ __('Ընտրեք ուտեստը ցանկից') }} --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}"
                                            data-name="{{ $prod->name }}"
                                            data-image="{{ $prod->image }}"
                                            data-price="{{ number_format($prod->price, 0) }}"
                                            data-desc="{{ $prod->description }}"
                                            {{ old('featured_product_id', $vendor->featured_product_id) == $prod->id ? 'selected' : '' }}>
                                        🍽️ {{ $prod->name }} — {{ number_format($prod->price, 0) }} {{ $vendor->currency ?? 'AMD' }} ({{ $prod->category?->name ?? 'Առանց բաժնի' }})
                                    </option>
                                @endforeach
                            </select>
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-utensils"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Այս ուտեստը կցուցադրվի մեծ բաներով անմիջապես կատեգորիաների ներքևում') }}
                        </span>
                    </div>

                    <!-- Կրծքանշանի տեքստ (Badge) -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Կրծքանշանի Տեքստ (Badge)') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="featured_dish_badge" id="featuredDishBadgeInput" value="{{ old('featured_dish_badge', $vendor->featured_dish_badge ?? '⭐ ՕՐՎԱ ԱՌԱՋԱՐԿ') }}" placeholder="Օրինակ՝ ⭐ ՕՐՎԱ ԱՌԱՋԱՐԿ" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-tag"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Լռելյայն՝ «⭐ ՕՐՎԱ ԱՌԱՋԱՐԿ» կամ «CHEF\'S SPECIAL»') }}
                        </span>
                    </div>

                    <!-- Գովազդային կարճ նկարագրություն -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Գովազդային Ենթավերնագիր') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="featured_dish_subtitle" id="featuredDishSubtitleInput" value="{{ old('featured_dish_subtitle', $vendor->featured_dish_subtitle) }}" placeholder="Օրինակ՝ Շեֆ խոհարարի հատուկ առաջարկը միայն այսօր" class="form-control" style="width: 100%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-comment-dots"></i>
                            </span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                            {{ __('Եթե դատարկ թողնեք, կօգտագործվի տվյալ ուտեստի հիմնական նկարագրությունը') }}
                        </span>
                    </div>
                </div>

                <!-- Live Preview in Admin -->
                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed var(--border-color);">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.45rem;">
                        <i class="fa-solid fa-eye" style="color: var(--primary);"></i>
                        <span>{{ __('Նախադիտում (ինչպես կերևա հաճախորդներին մենյուում)') }}</span>
                    </div>

                    <div id="featuredDishPreviewCard" style="max-width: 480px; border-radius: 16px; overflow: hidden; background: var(--bg-body); border: 2px solid var(--primary); box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                        <div style="position: relative; height: 140px; background: #000; overflow: hidden;">
                            <img id="previewDishImg" src="{{ $vendor->featuredProduct?->image ?: asset('images/default-dish.png') }}" onerror="this.onerror=null;this.src='{{ asset('images/default-dish.png') }}';" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);"></div>
                            <span id="previewDishBadge" style="position: absolute; top: 0.75rem; left: 0.75rem; background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; font-size: 0.7rem; font-weight: 800; padding: 0.25rem 0.6rem; border-radius: 9999px; text-transform: uppercase;">
                                {{ $vendor->featured_dish_badge ?: '⭐ ՕՐՎԱ ԱՌԱՋԱՐԿ' }}
                            </span>
                        </div>
                        <div style="padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.25rem;">
                                <h4 id="previewDishName" style="margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit';">
                                    {{ $vendor->featuredProduct?->name ?? 'Ընտրեք ուտեստը' }}
                                </h4>
                                <span id="previewDishPrice" style="font-size: 1.05rem; font-weight: 800; color: var(--primary);">
                                    {{ $vendor->featuredProduct ? number_format($vendor->featuredProduct->price, 0) . ' ' . ($vendor->currency ?? 'AMD') : '' }}
                                </span>
                            </div>
                            <p id="previewDishDesc" style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                                {{ $vendor->featured_dish_subtitle ?: ($vendor->featuredProduct?->description ?: 'Շեֆ խոհարարի հատուկ ընտրանի') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. AI CONTROL CENTER BANNER CARD -->
            <div class="card settings-card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(236, 72, 153, 0.05)); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #8b5cf6, #ec4899, #f59e0b);"></div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 1.1rem; min-width: 0; flex: 1;">
                        <span style="width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #8b5cf6, #ec4899); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35);">
                            <i class="fa-solid fa-robot"></i>
                        </span>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                    {{ __('AI Կառավարման Կենտրոն & AI Մատուցող') }}
                                </h3>
                                <span style="font-size: 0.72rem; font-weight: 700; background: linear-gradient(135deg, #8b5cf6, #d946ef); color: #fff; padding: 0.15rem 0.5rem; border-radius: 6px;">
                                    NEW DEDICATED HUB
                                </span>
                            </div>
                            <p style="margin: 0.35rem 0 0; font-size: 0.88rem; color: var(--text-muted); line-height: 1.45;">
                                {{ __('AI Մատուցողի, ինչպես նաև AI պրովայդերների (Gemini, OpenAI, Claude, DeepSeek, Groq), API բանալիների և մոդելների կարգավորումներն առանձնացվել են հատուկ AI բաժնում:') }}
                            </p>

                            <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.75rem; flex-wrap: wrap;">
                                <span style="font-size: 0.78rem; font-weight: 700; color: #8b5cf6; background: rgba(139, 92, 246, 0.12); padding: 0.2rem 0.55rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <i class="fa-solid fa-microchip"></i> {{ strtoupper($vendor->getAiProvider()) }} ({{ $vendor->getAiModel() }})
                                </span>
                                @if($vendor->ai_waiter_enabled)
                                    <span style="font-size: 0.78rem; font-weight: 700; color: #10b981; background: rgba(16, 185, 129, 0.12); padding: 0.2rem 0.55rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <i class="fa-solid fa-circle-check"></i> {{ __('AI Մատուցող՝ Ակտիվ') }}
                                    </span>
                                @else
                                    <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; background: rgba(100, 116, 139, 0.12); padding: 0.2rem 0.55rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <i class="fa-solid fa-pause"></i> {{ __('AI Մատուցող՝ Անջատված') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('admin.settings.ai') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 0.92rem; padding: 0.75rem 1.4rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none; color: #fff; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.35); white-space: nowrap;">
                            <i class="fa-solid fa-gear"></i> {{ __('Բացել AI Կարգավորումները') }}
                            <i class="fa-solid fa-arrow-right" style="font-size: 0.8rem;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 4. LEGAL & CONTACT INFORMATION CARD (SUPERADMIN) -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(99, 102, 241, 0.15); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Իրավաբանական և Կոնտակտային Տվյալներ') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Տվյալները հասանելի են հարթակի գլխավոր ադմինիստրատորին (SuperAdmin)') }}
                            </p>
                        </div>
                    </div>

                    <span style="font-size: 0.78rem; font-weight: 700; background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 0.3rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-shield-halved"></i> {{ __('Երևում է սուպերադմինում') }}
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
                    <!-- Իրավաբանական անվանում -->
                    <div class="form-group">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Իրավաբանական անվանում') }}
                        </label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}" class="form-control" placeholder="Օրինակ՝ «Բիստրո Գրուպ» ՍՊԸ" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="tax_id" value="{{ old('tax_id', $vendor->tax_id) }}" class="form-control" placeholder="02589412" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="operating_address" value="{{ old('operating_address', $vendor->operating_address ?? ($location?->address ?? $vendor->legal_address)) }}" class="form-control" placeholder="Փաստացի գործունեության հասցե" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="director_name" value="{{ old('director_name', $vendor->director_name) }}" class="form-control" placeholder="Տնօրենի Անուն" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="director_phone" value="{{ old('director_phone', $vendor->director_phone) }}" class="form-control" placeholder="+374 91 000000" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $vendor->contact_person_name) }}" class="form-control" placeholder="Մենեջերի Անուն" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
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
                            <input type="text" name="contact_person_phone" value="{{ old('contact_person_phone', $vendor->contact_person_phone) }}" class="form-control" placeholder="+374 93 000000" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.6rem; font-size: 0.95rem; font-weight: 600;">
                            <span style="position: absolute; left: 1rem; color: var(--text-muted); font-size: 1rem;">
                                <i class="fa-solid fa-mobile-screen"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. SERVICE FEE CARD -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-bell-concierge"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Սպասարկման Վճար (Ռեստորանում)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
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
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
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
                                <input type="number" step="any" min="0" name="service_fee_value" id="serviceFeeValue" value="{{ old('service_fee_value', $vendor->service_fee_value ?? 10) }}" required class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
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
                                <input type="number" step="any" min="0" name="service_fee_min_order" id="serviceFeeMinOrder" value="{{ old('service_fee_min_order', $vendor->service_fee_min_order) }}" placeholder="0" class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
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

            <!-- 6. DELIVERY SETTINGS CARD -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-motorcycle"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Առաքման Ծառայության Կարգավորումներ') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
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
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
                        <!-- Delivery Fee -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Առաքման Վճար') }} <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="delivery_fee" id="deliveryFeeInput" value="{{ old('delivery_fee', $vendor->delivery_fee ?? 0) }}" required class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
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
                                <input type="number" step="any" min="0" name="delivery_min_amount" id="deliveryMinAmount" value="{{ old('delivery_min_amount', $vendor->delivery_min_amount ?? 0) }}" required class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
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
                                <input type="number" step="any" min="0" name="delivery_free_from" id="deliveryFreeFrom" value="{{ old('delivery_free_from', $vendor->delivery_free_from) }}" placeholder="Առանց անվճարի" class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
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
                            <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
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

            <!-- 7. TAKEAWAY SETTINGS CARD -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Տեղում Վերցնելու (Takeaway) Ծառայության Կարգավորումներ') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Հնարավորություն տվեք հաճախորդներին նախապես պատվիրել և վերցնել տեղում') }}
                            </p>
                        </div>
                    </div>

                    <!-- Enable Switch -->
                    <label class="modern-switch-wrapper" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; user-select: none;">
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);" id="takeawayStatusLabel">
                            {{ old('takeaway_enabled', $vendor->takeaway_enabled) ? __('Ակտիվ է') : __('Անջատված է') }}
                        </span>
                        <input type="checkbox" name="takeaway_enabled" value="1" id="takeawayToggle" {{ old('takeaway_enabled', $vendor->takeaway_enabled) ? 'checked' : '' }} onchange="toggleTakeawayFields()" style="width: 20px; height: 20px; accent-color: #8b5cf6; cursor: pointer;">
                    </label>
                </div>

                <div id="takeawayContainer" style="{{ old('takeaway_enabled', $vendor->takeaway_enabled) ? '' : 'opacity: 0.55; pointer-events: none;' }}; transition: opacity 0.2s ease;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 1.25rem;">
                        <!-- Minimum Order for Takeaway -->
                        <div class="form-group">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Նվազագույն պատվերի գումար') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" step="any" min="0" name="takeaway_min_amount" id="takeawayMinAmount" value="{{ old('takeaway_min_amount', $vendor->takeaway_min_amount ?? 0) }}" class="form-control" style="width: 100%; box-sizing: border-box; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px; padding: 0.75rem 1rem; font-size: 1rem; font-weight: 700;" oninput="updateCalculationsPreview()">
                                <span style="position: absolute; right: 1rem; font-weight: 800; color: var(--text-muted); font-size: 0.85rem;">
                                    {{ $vendor->currency }}
                                </span>
                            </div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem; display: block;">
                                {{ __('Տեղում վերցնելու նվազագույն շեմ (0 = առանց սահմանափակման)') }}
                            </span>
                        </div>
                    </div>

                    <!-- Storefront Takeaway Banner Preview -->
                    <div style="margin-top: 1.25rem; background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.25); border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(139, 92, 246, 0.2); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </span>
                            <div>
                                <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main);" id="takeawayPreviewText">
                                    {{ __('Տեղում վերցնել (Takeaway) տարբերակը հասանելի է') }}
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);" id="takeawayPreviewSubtext">
                                    {{ __('Հաճախորդները կարող են նախապես պատվիրել առանց սեղանի QR-ի') }}
                                </div>
                            </div>
                        </div>
                        <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6; font-weight: 800; border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                            <i class="fa-solid fa-store"></i> {{ __('Takeaway Ռեժիմ') }}
                        </span>
                    </div>
                </div>
            </div>

            @php
                $paymentSettings = $vendor->getPaymentSettings();
                $crmSettings = $vendor->getCrmSettings();
                $printerSettings = $vendor->getThermalPrinterSettings();
            @endphp

            <!-- 5. ONLINE PAYMENTS & GATEWAYS -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-credit-card"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('Վճարային Համակարգեր (Local & International Payments)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Կառավարեք հաճախորդների կողմից առցանց կամ տեղում վճարելու եղանակները') }}
                            </p>
                        </div>
                    </div>

                    <label class="switch" style="position: relative; display: inline-block; width: 48px; height: 26px;">
                        <input type="checkbox" name="payment_settings[online_enabled]" value="1" {{ !empty($paymentSettings['online_enabled']) ? 'checked' : '' }} id="onlinePaymentsToggle" onchange="toggleOnlinePaymentsFields()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                    </label>
                </div>

                <!-- Payment Methods Choice (Cash / Terminal) -->
                <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                    <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.75rem;">
                        {{ __('Վճարման ընդհանուր մեթոդներ զամբյուղում') }}
                    </div>
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="payment_settings[cash_enabled]" value="1" {{ !empty($paymentSettings['cash_enabled']) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>💵 {{ __('Կանխիկ վճարում տեղում') }}</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: var(--text-main);">
                            <input type="checkbox" name="payment_settings[pos_terminal_enabled]" value="1" {{ !empty($paymentSettings['pos_terminal_enabled']) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>💳 {{ __('Տերմինալով (POS) վճարում տեղում') }}</span>
                        </label>
                    </div>
                </div>

                <div id="onlineGatewaysContainer" style="{{ empty($paymentSettings['online_enabled']) ? 'opacity: 0.55; pointer-events: none;' : '' }}; transition: all 0.25s ease;">
                    <div style="font-size: 0.92rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🇦🇲 {{ __('Հայկական Վճարային Համակարգեր') }}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                        <!-- Idram -->
                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main);">
                                    <span style="background: #ff6f00; color: #fff; width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900;">Id</span>
                                    <span>Idram</span>
                                </div>
                                <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 22px;">
                                    <input type="checkbox" name="payment_settings[gateways][idram][enabled]" value="1" {{ !empty($paymentSettings['gateways']['idram']['enabled']) ? 'checked' : '' }}>
                                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                <input type="text" name="payment_settings[gateways][idram][merchant_id]" value="{{ $paymentSettings['gateways']['idram']['merchant_id'] ?? '' }}" placeholder="Receiver / Merchant ID" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <input type="password" name="payment_settings[gateways][idram][secret_key]" value="{{ $paymentSettings['gateways']['idram']['secret_key'] ?? '' }}" placeholder="Secret Key" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer;">
                                    <input type="checkbox" name="payment_settings[gateways][idram][sandbox]" value="1" {{ !empty($paymentSettings['gateways']['idram']['sandbox']) ? 'checked' : '' }}>
                                    <span>{{ __('Sandbox / Թեստային ռեժիմ') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Telcell Wallet -->
                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main);">
                                    <span style="background: #e11d48; color: #fff; width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900;">TC</span>
                                    <span>Telcell Wallet</span>
                                </div>
                                <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 22px;">
                                    <input type="checkbox" name="payment_settings[gateways][telcell][enabled]" value="1" {{ !empty($paymentSettings['gateways']['telcell']['enabled']) ? 'checked' : '' }}>
                                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                <input type="text" name="payment_settings[gateways][telcell][shop_id]" value="{{ $paymentSettings['gateways']['telcell']['shop_id'] ?? '' }}" placeholder="Shop ID" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <input type="password" name="payment_settings[gateways][telcell][key]" value="{{ $paymentSettings['gateways']['telcell']['key'] ?? '' }}" placeholder="Security Key" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer;">
                                    <input type="checkbox" name="payment_settings[gateways][telcell][sandbox]" value="1" {{ !empty($paymentSettings['gateways']['telcell']['sandbox']) ? 'checked' : '' }}>
                                    <span>{{ __('Sandbox / Թեստային ռեժիմ') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- FastShift -->
                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main);">
                                    <span style="background: #2563eb; color: #fff; width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900;">FS</span>
                                    <span>FastShift</span>
                                </div>
                                <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 22px;">
                                    <input type="checkbox" name="payment_settings[gateways][fastshift][enabled]" value="1" {{ !empty($paymentSettings['gateways']['fastshift']['enabled']) ? 'checked' : '' }}>
                                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                <input type="text" name="payment_settings[gateways][fastshift][merchant_id]" value="{{ $paymentSettings['gateways']['fastshift']['merchant_id'] ?? '' }}" placeholder="Merchant ID" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <input type="password" name="payment_settings[gateways][fastshift][api_key]" value="{{ $paymentSettings['gateways']['fastshift']['api_key'] ?? '' }}" placeholder="API Key" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer;">
                                    <input type="checkbox" name="payment_settings[gateways][fastshift][sandbox]" value="1" {{ !empty($paymentSettings['gateways']['fastshift']['sandbox']) ? 'checked' : '' }}>
                                    <span>{{ __('Sandbox / Թեստային ռեժիմ') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- ArCa / Ameriabank vPOS -->
                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main);">
                                    <span style="background: #059669; color: #fff; width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900;">AC</span>
                                    <span>ArCa / Ameriabank vPOS</span>
                                </div>
                                <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 22px;">
                                    <input type="checkbox" name="payment_settings[gateways][arca][enabled]" value="1" {{ !empty($paymentSettings['gateways']['arca']['enabled']) ? 'checked' : '' }}>
                                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                <input type="text" name="payment_settings[gateways][arca][merchant_id]" value="{{ $paymentSettings['gateways']['arca']['merchant_id'] ?? '' }}" placeholder="Merchant / Client ID" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <input type="text" name="payment_settings[gateways][arca][terminal_id]" value="{{ $paymentSettings['gateways']['arca']['terminal_id'] ?? '' }}" placeholder="Terminal ID" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer;">
                                    <input type="checkbox" name="payment_settings[gateways][arca][sandbox]" value="1" {{ !empty($paymentSettings['gateways']['arca']['sandbox']) ? 'checked' : '' }}>
                                    <span>{{ __('Sandbox / Թեստային ռեժիմ') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div style="font-size: 0.92rem; font-weight: 800; color: var(--text-main); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🌍 {{ __('Միջազգային Վճարային Համակարգեր') }}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 1.25rem;">
                        <!-- Stripe -->
                        <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main);">
                                    <span style="background: #6366f1; color: #fff; width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900;">S</span>
                                    <span>Stripe (Visa, Mastercard, Apple Pay)</span>
                                </div>
                                <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 22px;">
                                    <input type="checkbox" name="payment_settings[gateways][stripe][enabled]" value="1" {{ !empty($paymentSettings['gateways']['stripe']['enabled']) ? 'checked' : '' }}>
                                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                                </label>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                <input type="text" name="payment_settings[gateways][stripe][publishable_key]" value="{{ $paymentSettings['gateways']['stripe']['publishable_key'] ?? '' }}" placeholder="Publishable Key (pk_test_...)" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <input type="password" name="payment_settings[gateways][stripe][secret_key]" value="{{ $paymentSettings['gateways']['stripe']['secret_key'] ?? '' }}" placeholder="Secret Key (sk_test_...)" class="form-control" style="font-size: 0.82rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--text-muted); cursor: pointer;">
                                    <input type="checkbox" name="payment_settings[gateways][stripe][sandbox]" value="1" {{ !empty($paymentSettings['gateways']['stripe']['sandbox']) ? 'checked' : '' }}>
                                    <span>{{ __('Test Mode (pk_test / sk_test)') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. ESC/POS THERMAL PRINTERS -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-print"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('ESC/POS Ջերմային Տպիչներ (Kitchen & Bar Printers)') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Պատվերի կտրոնների ավտոմատ տպում խոհանոցի կամ բարի տպիչի վրա Web Bluetooth, RawBT կամ PrintNode միջոցով') }}
                            </p>
                        </div>
                    </div>

                    <label class="switch" style="position: relative; display: inline-block; width: 48px; height: 26px;">
                        <input type="checkbox" name="thermal_printer_settings[auto_print_live_orders]" value="1" {{ !empty($printerSettings['auto_print_live_orders']) ? 'checked' : '' }}>
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Թղթի Լայնություն') }}
                        </label>
                        <select name="thermal_printer_settings[paper_width]" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-weight: 600;">
                            <option value="80mm" {{ ($printerSettings['paper_width'] ?? '80mm') === '80mm' ? 'selected' : '' }}>80 մմ (Ստանդարտ ռեստորանային չափ)</option>
                            <option value="58mm" {{ ($printerSettings['paper_width'] ?? '') === '58mm' ? 'selected' : '' }}>58 մմ (Կոմպակտ չափ)</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Տպվող Օրինակների Քանակ') }}
                        </label>
                        <input type="number" min="1" max="5" name="thermal_printer_settings[copies]" value="{{ $printerSettings['copies'] ?? 1 }}" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-weight: 600;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Կտրոնի Վերնագիր') }}
                        </label>
                        <input type="text" name="thermal_printer_settings[header_title]" value="{{ $printerSettings['header_title'] ?? $vendor->name }}" placeholder="Ռեստորանի անուն" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-weight: 600;">
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="thermal_printer_settings[print_customer_info]" value="1" {{ !empty($printerSettings['print_customer_info']) ? 'checked' : '' }} style="accent-color: var(--primary);">
                        <span>{{ __('Ներառել հաճախորդի տվյալները (հեռախոս, հասցե)') }}</span>
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="thermal_printer_settings[print_prices]" value="1" {{ !empty($printerSettings['print_prices']) ? 'checked' : '' }} style="accent-color: var(--primary);">
                        <span>{{ __('Տպել գները և ընդհանուր գումարը') }}</span>
                    </label>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                        {{ __('Կտրոնի Ստորոտ (Footer Text)') }}
                    </label>
                    <input type="text" name="thermal_printer_settings[footer_text]" value="{{ $printerSettings['footer_text'] ?? 'Շնորհակալություն այցելության համար!' }}" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px;">
                </div>
            </div>

            <!-- 7. CRM AUTOMATION & BIRTHDAY DISCOUNTS -->
            <div class="card settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.9rem;">
                        <span style="width: 42px; height: 42px; border-radius: 12px; background: rgba(236, 72, 153, 0.15); color: #ec4899; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-cake-candles"></i>
                        </span>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                {{ __('CRM Ավտոմատացում & Ծննդյան Օրվա Զեղչեր') }}
                            </h3>
                            <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Ծննդյան օրվա ավտոմատ զեղչեր պատվերների ժամանակ և SMS ծանուցումներ (Mobipace, SMS.am, Twilio)') }}
                            </p>
                        </div>
                    </div>

                    <label class="switch" style="position: relative; display: inline-block; width: 48px; height: 26px;">
                        <input type="checkbox" name="crm_settings[birthday_discount_enabled]" value="1" {{ !empty($crmSettings['birthday_discount_enabled']) ? 'checked' : '' }} id="crmBirthdayToggle" onchange="toggleCrmFields()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px;"></span>
                    </label>
                </div>

                <div id="crmContainer" style="{{ empty($crmSettings['birthday_discount_enabled']) ? 'opacity: 0.55; pointer-events: none;' : '' }}; transition: all 0.25s ease;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Ծննդյան Զեղչի Չափ (%)') }}
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="number" min="0" max="100" step="1" name="crm_settings[birthday_discount_percent]" value="{{ $crmSettings['birthday_discount_percent'] ?? 15 }}" class="form-control" style="width: 100%; padding: 0.65rem 1rem 0.65rem 2.2rem; border-radius: 12px; font-weight: 700; color: #10b981;">
                                <span style="position: absolute; left: 0.9rem; color: var(--text-muted); font-weight: 700;">%</span>
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('Գործողության Ժամկետ (օր)') }}
                            </label>
                            <input type="number" min="0" max="30" name="crm_settings[birthday_validity_days]" value="{{ $crmSettings['birthday_validity_days'] ?? 3 }}" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-weight: 600;">
                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; display: block;">{{ __('Զեղչը կգործի ծննդյան օրվանից ± նշված օրերին') }}</span>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('SMS Պրովայդեր') }}
                            </label>
                            <select name="crm_settings[sms_provider]" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-weight: 600;">
                                <option value="mobipace" {{ ($crmSettings['sms_provider'] ?? '') === 'mobipace' ? 'selected' : '' }}>Mobipace SMS (Հայաստան)</option>
                                <option value="smsam" {{ ($crmSettings['sms_provider'] ?? '') === 'smsam' ? 'selected' : '' }}>SMS.am (Հայաստան)</option>
                                <option value="twilio" {{ ($crmSettings['sms_provider'] ?? '') === 'twilio' ? 'selected' : '' }}>Twilio (International)</option>
                                <option value="log" {{ ($crmSettings['sms_provider'] ?? '') === 'log' ? 'selected' : '' }}>Log / Թեստային (առանց ուղարկման)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('SMS API Key / Token') }}
                            </label>
                            <input type="password" name="crm_settings[sms_api_key]" value="{{ $crmSettings['sms_api_key'] ?? '' }}" placeholder="API Key" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                {{ __('SMS Sender ID (Անվանում)') }}
                            </label>
                            <input type="text" name="crm_settings[sms_sender_id]" value="{{ $crmSettings['sms_sender_id'] ?? 'QRMENU' }}" placeholder="Օր․ QRMENU կամ Ռեստորան" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px;">
                        </div>

                        <div style="display: flex; align-items: flex-end; padding-bottom: 0.5rem;">
                            <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                                <input type="checkbox" name="crm_settings[birthday_sms_enabled]" value="1" {{ !empty($crmSettings['birthday_sms_enabled']) ? 'checked' : '' }} style="accent-color: var(--primary);">
                                <span>{{ __('Ակտիվացնել ավտոմատ SMS շնորհավորանքները') }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                            {{ __('Շնորհավորական SMS-ի Տեքստը') }}
                        </label>
                        <textarea name="crm_settings[birthday_sms_template]" rows="2" class="form-control" style="width: 100%; padding: 0.65rem 1rem; border-radius: 12px; font-size: 0.88rem;">{{ $crmSettings['birthday_sms_template'] ?? 'Շնորհավոր Ձեր ծննդյան օրը {NAME}։ Ձեզ սպասում է {DISCOUNT}% զեղչ {VENDOR}-ում։' }}</textarea>
                        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; display: block;">{{ __('Հասանելի փոխարինիչներ՝ {NAME} - հաճախորդի անուն, {DISCOUNT} - զեղչի %, {VENDOR} - ռեստորանի անվանում') }}</span>
                    </div>
                </div>
            <!-- 8. USER PROFILE & 2FA SECURITY CARD -->
            <div class="card settings-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(245, 158, 11, 0.05) 100%); border: 1.5px solid {{ Auth::user()->hasTwoFactorEnabled() ? '#10b981' : 'var(--border-color)' }}; border-radius: 20px; padding: clamp(1.2rem, 3vw, 1.85rem); box-shadow: var(--shadow-card);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="width: 48px; height: 48px; border-radius: 14px; background: {{ Auth::user()->hasTwoFactorEnabled() ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ Auth::user()->hasTwoFactorEnabled() ? '#10b981' : '#f59e0b' }}; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                                    {{ __('Անձնական Անվտանգություն & 2FA (Պրոֆիլ)') }}
                                </h3>
                                @if(Auth::user()->hasTwoFactorEnabled())
                                    <span class="badge badge-emerald" style="font-weight: 700;">
                                        <i class="fa-solid fa-circle-check"></i> {{ __('Ակտիվ') }} ({{ Auth::user()->two_factor_type === 'authenticator' ? 'Google Authenticator' : 'Email Code' }})
                                    </span>
                                @else
                                    <span class="badge badge-amber" style="font-weight: 700;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> {{ __('Անջատված') }}
                                    </span>
                                @endif
                            </div>
                            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--text-muted); word-break: break-word;">
                                {{ __('Երկփուլային նույնականացումը (2FA) պաշտպանում է /login մուտքը, գաղտնաբառի և էլ․ փոստի փոփոխությունը։') }}
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.profile') }}" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; border-radius: 12px; font-weight: 700; padding: 0.65rem 1.3rem;">
                        <i class="fa-solid fa-key"></i> {{ __('Կառավարել 2FA-ն & Գաղտնաբառը') }}
                    </a>
                </div>
            </div>

            <!-- Sticky Bottom Save Bar -->
            <div class="sticky-save-bar" style="position: sticky; bottom: 1.5rem; z-index: 30; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 0.85rem 1.25rem; box-shadow: 0 12px 30px rgba(0,0,0,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                <div style="display: flex; align-items: center; gap: 0.6rem; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fa-solid fa-cloud-arrow-up" style="color: var(--primary);"></i>
                    <span>{{ __('Բոլոր փոփոխությունները կպահպանվեն ընթացիկ մասնաճյուղի համար') }}</span>
                </div>
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.6rem; border-radius: 14px; font-weight: 700; padding: 0.75rem 2rem; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);">
                    <i class="fa-solid fa-check"></i> {{ __('Պահպանել Բոլոր Կարգավորումները') }}
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.settings-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.settings-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
}
.form-control:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important;
    outline: none;
}
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

function toggleTakeawayFields() {
    const isChecked = document.getElementById('takeawayToggle').checked;
    const container = document.getElementById('takeawayContainer');
    const label = document.getElementById('takeawayStatusLabel');
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

function toggleOnlinePaymentsFields() {
    const isChecked = document.getElementById('onlinePaymentsToggle').checked;
    const container = document.getElementById('onlineGatewaysContainer');
    if (container) {
        if (isChecked) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        } else {
            container.style.opacity = '0.55';
            container.style.pointerEvents = 'none';
        }
    }
}

function toggleCrmFields() {
    const isChecked = document.getElementById('crmBirthdayToggle').checked;
    const container = document.getElementById('crmContainer');
    if (container) {
        if (isChecked) {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        } else {
            container.style.opacity = '0.55';
            container.style.pointerEvents = 'none';
        }
    }
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

    // Takeaway preview
    const isTakeawayEnabled = document.getElementById('takeawayToggle')?.checked;
    const takeawayMin = parseFloat(document.getElementById('takeawayMinAmount')?.value) || 0;
    const takeawayPreviewText = document.getElementById('takeawayPreviewText');
    const takeawayPreviewSubtext = document.getElementById('takeawayPreviewSubtext');

    if (takeawayPreviewText && takeawayPreviewSubtext) {
        if (!isTakeawayEnabled) {
            takeawayPreviewText.textContent = "{{ __('Տեղում վերցնելու (Takeaway) ծառայությունը ներկայումս անջատված է') }}";
            takeawayPreviewSubtext.textContent = "{{ __('Հաճախորդները չեն կարողանա ընտրել Takeaway տարբերակը') }}";
        } else {
            let minText = takeawayMin > 0 ? `Նվազագույն պատվեր՝ ${takeawayMin.toLocaleString()} ${CURRENCY}: ` : '';
            takeawayPreviewText.textContent = `${minText}Տեղում վերցնելու (Takeaway) պատվերները ակտիվ են`;
            takeawayPreviewSubtext.textContent = "{{ __('Հաճախորդները կարող են պատվիրել առանց ռեստորանում/սեղանի մոտ գտնվելու') }}";
        }
    }

    // Featured Dish Preview
    updateFeaturedDishPreview();
}

function updateFeaturedDishPreview() {
    const select = document.getElementById('featuredProductSelect');
    const badgeInput = document.getElementById('featuredDishBadgeInput');
    const subtitleInput = document.getElementById('featuredDishSubtitleInput');
    const toggle = document.getElementById('featuredDishToggle');
    const previewCard = document.getElementById('featuredDishPreviewCard');

    if (!select || !previewCard) return;

    if (toggle && !toggle.checked) {
        previewCard.style.opacity = '0.4';
        previewCard.style.filter = 'grayscale(0.6)';
    } else {
        previewCard.style.opacity = '1';
        previewCard.style.filter = 'none';
    }

    const opt = select.options[select.selectedIndex];
    const nameEl = document.getElementById('previewDishName');
    const priceEl = document.getElementById('previewDishPrice');
    const imgEl = document.getElementById('previewDishImg');
    const descEl = document.getElementById('previewDishDesc');
    const badgeEl = document.getElementById('previewDishBadge');

    if (opt && opt.value) {
        if (nameEl) nameEl.textContent = opt.dataset.name || opt.text;
        if (priceEl) priceEl.textContent = (opt.dataset.price || '') + ' ' + '{{ $vendor->currency ?? "AMD" }}';
        if (imgEl && opt.dataset.image) imgEl.src = opt.dataset.image;
        if (descEl) descEl.textContent = subtitleInput.value.trim() || opt.dataset.desc || 'Շեֆ խոհարարի հատուկ ընտրանի';
    } else {
        if (nameEl) nameEl.textContent = 'Ընտրեք ուտեստը';
        if (priceEl) priceEl.textContent = '';
        if (descEl) descEl.textContent = 'Ուտեստ ընտրված չէ';
    }

    if (badgeEl) {
        badgeEl.textContent = badgeInput.value.trim() || '⭐ ՕՐՎԱ ԱՌԱՋԱՐԿ';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateCalculationsPreview();

    const featSelect = document.getElementById('featuredProductSelect');
    if (featSelect) featSelect.addEventListener('change', updateFeaturedDishPreview);
    const featBadge = document.getElementById('featuredDishBadgeInput');
    if (featBadge) featBadge.addEventListener('input', updateFeaturedDishPreview);
    const featSub = document.getElementById('featuredDishSubtitleInput');
    if (featSub) featSub.addEventListener('input', updateFeaturedDishPreview);
    const featToggle = document.getElementById('featuredDishToggle');
    if (featToggle) featToggle.addEventListener('change', updateFeaturedDishPreview);

    const aiWaiterToggle = document.getElementById('aiWaiterToggle');
    const aiWaiterOptionsBlock = document.getElementById('aiWaiterOptionsBlock');
    if (aiWaiterToggle && aiWaiterOptionsBlock) {
        aiWaiterToggle.addEventListener('change', () => {
            aiWaiterOptionsBlock.style.display = aiWaiterToggle.checked ? 'block' : 'none';
        });
    }

    const btnCheckDomainDns = document.getElementById('btnCheckDomainDns');
    const customDomainInput = document.getElementById('customDomainInput');
    const dnsCheckResultBox = document.getElementById('dnsCheckResultBox');

    if (btnCheckDomainDns && customDomainInput && dnsCheckResultBox) {
        btnCheckDomainDns.addEventListener('click', async () => {
            const domain = customDomainInput.value.trim();
            if (!domain) {
                dnsCheckResultBox.style.display = 'block';
                dnsCheckResultBox.style.background = 'rgba(239, 68, 68, 0.15)';
                dnsCheckResultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                dnsCheckResultBox.style.color = '#ef4444';
                dnsCheckResultBox.textContent = 'Խնդրում ենք մուտքագրել դոմենի հասցեն:';
                return;
            }

            btnCheckDomainDns.disabled = true;
            btnCheckDomainDns.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ստուգում...';

            try {
                const res = await fetch('{{ route("admin.settings.domain.check") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ domain }),
                });

                const data = await res.json();
                dnsCheckResultBox.style.display = 'block';

                if (data.is_pointing) {
                    dnsCheckResultBox.style.background = 'rgba(16, 185, 129, 0.15)';
                    dnsCheckResultBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                    dnsCheckResultBox.style.color = '#10b981';
                } else {
                    dnsCheckResultBox.style.background = 'rgba(245, 158, 11, 0.15)';
                    dnsCheckResultBox.style.border = '1px solid rgba(245, 158, 11, 0.3)';
                    dnsCheckResultBox.style.color = '#f59e0b';
                }
                dnsCheckResultBox.textContent = data.message;
            } catch (err) {
                dnsCheckResultBox.style.display = 'block';
                dnsCheckResultBox.style.background = 'rgba(239, 68, 68, 0.15)';
                dnsCheckResultBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                dnsCheckResultBox.style.color = '#ef4444';
                dnsCheckResultBox.textContent = 'Ստուգման ժամանակ սխալ տեղի ունեցավ: Կրկին փորձեք:';
            } finally {
                btnCheckDomainDns.disabled = false;
                btnCheckDomainDns.innerHTML = '<i class="fa-solid fa-bolt"></i> {{ __("Ստուգել DNS") }}';
            }
        });
    }
});
</script>
@endsection
