@props(['settings'])

<div x-show="activeTab === 'branding'" x-cloak>
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
        </div>