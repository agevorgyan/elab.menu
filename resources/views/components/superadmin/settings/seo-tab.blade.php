@props(['settings'])

<div x-show="activeTab === 'seo'" x-cloak>
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
        </div>