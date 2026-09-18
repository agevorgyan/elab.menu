<!-- Restaurant Info & WiFi Modal (Full Screen) -->
<div x-show="showInfoModal" 
     style="position: fixed; inset: 0; width: 100%; height: 100%; height: 100dvh; background: var(--bg-card); z-index: 200; display: flex; flex-direction: column; overflow: hidden;" 
     x-cloak
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform translate-y-4">
     
    <div style="background: var(--bg-card); width: 100%; max-width: 640px; margin: 0 auto; height: 100%; height: 100dvh; display: flex; flex-direction: column; box-sizing: border-box;">
         
        <!-- Sticky Top Header -->
        <div style="padding: max(1rem, env(safe-area-inset-top)) 1.25rem 0.85rem 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); background: var(--bg-card); position: sticky; top: 0; z-index: 10;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(var(--primary-rgb, 225, 29, 72), 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0; line-height: 1.2;">
                        {{ __('menu.restaurant_info') }}
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0.15rem 0 0 0;">
                        {{ $vendor->name }} {{ $location ? '• ' . $location->name : '' }}
                    </p>
                </div>
            </div>
            <button type="button" @click="showInfoModal = false" style="background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-main); width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; cursor: pointer; flex-shrink: 0;">
                ✕
            </button>
        </div>

        <!-- Scrollable Content -->
        <div style="flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 1.25rem 1.25rem calc(2.5rem + env(safe-area-inset-bottom)) 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
            <!-- Wi-Fi Card -->
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb, 225, 29, 72), 0.08), rgba(var(--primary-rgb, 225, 29, 72), 0.02)); border: 1.5px dashed var(--primary); border-radius: 16px; padding: 1.15rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <i class="fa-solid fa-wifi" style="font-size: 1.2rem; color: var(--primary);"></i>
                        <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">
                            {{ __('menu.wifi_network') }}
                        </span>
                    </div>
                    <span style="font-size: 0.75rem; background: var(--primary); color: #fff; padding: 0.2rem 0.6rem; border-radius: 9999px; font-weight: 700;">
                        Free Fast Wi-Fi
                    </span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.65rem 0.85rem; margin-bottom: 0.65rem;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">SSID (Ցանց)</div>
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ $vendor->name }} Guest</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.65rem 0.85rem;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ __('menu.wifi_password') }}</div>
                        <div style="font-weight: 800; font-family: monospace; font-size: 1rem; color: var(--primary); letter-spacing: 0.05em;">
                            guest{{ str_pad($vendor->id, 4, '0', STR_PAD_LEFT) }}
                        </div>
                    </div>
                    <button type="button" 
                            @click="copyWifiPassword('guest{{ str_pad($vendor->id, 4, '0', STR_PAD_LEFT) }}')"
                            style="background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.45rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.4rem;">
                        <i :class="wifiCopied ? 'fa-solid fa-check text-green-500' : 'fa-regular fa-copy'"></i>
                        <span x-text="wifiCopied ? '{{ __('menu.wifi_copied') }}' : '{{ __('menu.wifi_copy') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- Address & Contact Details -->
            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 16px; padding: 1rem; display: flex; flex-direction: column; gap: 0.85rem;">
                <!-- Address -->
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0; margin-top: 0.1rem;">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">{{ __('menu.address') }}</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-top: 0.1rem;">
                            {{ $location?->address ?? $vendor->operating_address ?? $vendor->legal_address ?? 'Yerevan, Armenia' }}
                        </div>
                    </div>
                </div>

                <!-- Phone -->
                @php
                    $contactPhone = $location?->phone ?? $vendor->phone ?? '+374 10 000000';
                    $rawPhone = preg_replace('/[^0-9+]/', '', $contactPhone);
                    $whatsappNum = $location?->whatsapp_number ? preg_replace('/[^0-9]/', '', $location->whatsapp_number) : preg_replace('/[^0-9]/', '', $contactPhone);
                @endphp
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0; margin-top: 0.1rem;">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">{{ __('menu.phone') }}</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-top: 0.1rem;">
                            {{ $contactPhone }}
                        </div>
                    </div>
                    <a href="tel:{{ $rawPhone }}" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--primary); padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-phone-flip"></i> {{ __('menu.call_phone') }}
                    </a>
                </div>

                <!-- WhatsApp Direct Chat -->
                @if($whatsappNum)
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: #25d366; flex-shrink: 0; margin-top: 0.1rem;">
                            <i class="fa-brands fa-whatsapp" style="font-size: 1.15rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">WhatsApp</div>
                            <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-top: 0.1rem;">
                                +{{ $whatsappNum }}
                            </div>
                        </div>
                        <a href="https://wa.me/{{ $whatsappNum }}" target="_blank" style="background: #25d366; color: #fff; padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.35rem;">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                @endif

                <!-- Working Hours -->
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0; margin-top: 0.1rem;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">{{ __('menu.working_hours') }}</div>
                        <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-main); margin-top: 0.1rem;">
                            10:00 - 23:00 (Ամեն օր / Daily)
                        </div>
                    </div>
                </div>

                <!-- Table seated info if available -->
                <template x-if="tableNumber || '{{ $table ?? '' }}'">
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed var(--border-color);">
                        <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--accent); flex-shrink: 0;">
                            <i class="fa-solid fa-chair"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">{{ __('menu.table') }}</div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main);" x-text="tableNumber || 'Table {{ $table ?? '' }}'"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Close Button -->
            <button type="button" 
                    @click="showInfoModal = false" 
                    style="width: 100%; padding: 0.85rem; background: var(--primary); color: #fff; border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem; cursor: pointer; margin-top: 0.25rem;">
                {{ __('menu.close') }}
            </button>
        </div>
    </div>
</div>
