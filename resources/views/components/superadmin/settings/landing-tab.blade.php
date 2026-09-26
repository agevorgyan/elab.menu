@props(['settings'])

<div x-show="activeTab === 'landing'" x-cloak>
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0;">
                        🚀 Լենդինգ Էջի Բովանդակության Կառավարում (CMS)
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0 0;">
                        Փոփոխեք լենդինգի բոլոր բաժինների վերնագրերը, նկարագրությունները, կոճակները և ինտերակտիվ տեքստերը։
                    </p>
                </div>
                <button type="submit" class="btn-save" style="padding: 0.65rem 1.4rem; font-size: 0.88rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Պահպանել Լենդինգը
                </button>
            </div>

            <!-- 1.1 Hero Section CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: #f59e0b;"></i>
                    <span>1. Գլխավոր Բաժին (Hero Section)</span>
                </div>

                <!-- Hero Badge -->
                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-certificate"></i> Վերևի Բեյջ (Հայերեն)
                        </label>
                        <input type="text" name="hero_badge_hy" class="form-input" value="{{ old('hero_badge_hy', $settings['hero_badge_hy'] ?? '') }}" placeholder="Ռեստորանային Տեխնոլոգիաների Նոր Սերունդ • AI Մատուցողով">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe"></i> Top Badge (English)
                        </label>
                        <input type="text" name="hero_badge_en" class="form-input" value="{{ old('hero_badge_en', $settings['hero_badge_en'] ?? '') }}" placeholder="Next-Gen Restaurant Platform • Powered by AI">
                    </div>
                </div>

                <!-- Hero Title -->
                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i> Գլխավոր Վերնագիր H1 (Հայերեն)
                        </label>
                        <input type="text" name="hero_title_hy" class="form-input" value="{{ old('hero_title_hy', $settings['hero_title_hy'] ?? '') }}" placeholder="Ավելացրեք ռեստորանի շրջանառությունը +30%-ով խելացի QR մենյուի & AI-ի շնորհիվ">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe"></i> Main Hero Title H1 (English)
                        </label>
                        <input type="text" name="hero_title_en" class="form-input" value="{{ old('hero_title_en', $settings['hero_title_en'] ?? '') }}" placeholder="Boost Restaurant Revenue by +30% with Smart QR Menu & AI Waiter">
                    </div>
                </div>

                <!-- Hero Subtitle -->
                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-align-left"></i> Ենթավերնագիր / Նկարագրություն (Հայերեն)
                        </label>
                        <textarea name="hero_subtitle_hy" rows="3" class="form-textarea" placeholder="Ինտերակտիվ թվային մենյու, սեղանից արագ պատվերներ, մատուցողի կանչ և AI խելացի հանձնարարականներ...">{{ old('hero_subtitle_hy', $settings['hero_subtitle_hy'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe"></i> Hero Subtitle Description (English)
                        </label>
                        <textarea name="hero_subtitle_en" rows="3" class="form-textarea" placeholder="Interactive digital menu, fast table orders, waiter paging, and smart AI recommendations...">{{ old('hero_subtitle_en', $settings['hero_subtitle_en'] ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Hero Action Buttons -->
                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-play"></i> Գլխավոր CTA Կոճակի Տեքստ (Հայերեն)
                        </label>
                        <input type="text" name="hero_cta_primary_hy" class="form-input" value="{{ old('hero_cta_primary_hy', $settings['hero_cta_primary_hy'] ?? '') }}" placeholder="Սկսել 14 Օր Անվճար">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe"></i> Primary CTA Button (English)
                        </label>
                        <input type="text" name="hero_cta_primary_en" class="form-input" value="{{ old('hero_cta_primary_en', $settings['hero_cta_primary_en'] ?? '') }}" placeholder="Start 14-Day Free Trial">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-mobile-screen"></i> Երկրորդական Կոճակ (Դեմո) (Հայերեն)
                        </label>
                        <input type="text" name="hero_cta_secondary_hy" class="form-input" value="{{ old('hero_cta_secondary_hy', $settings['hero_cta_secondary_hy'] ?? '') }}" placeholder="Տեսնել Դեմո Մենյուն">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-globe"></i> Secondary Button (Demo) (English)
                        </label>
                        <input type="text" name="hero_cta_secondary_en" class="form-input" value="{{ old('hero_cta_secondary_en', $settings['hero_cta_secondary_en'] ?? '') }}" placeholder="View Live Demo">
                    </div>
                </div>

                <!-- Trust Badges -->
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Վստահության Բեյջ 1 (HY / EN)</label>
                        <input type="text" name="hero_trust_badge1_hy" class="form-input" value="{{ old('hero_trust_badge1_hy', $settings['hero_trust_badge1_hy'] ?? '') }}" placeholder="Բանկային քարտ չի պահանջվում">
                        <input type="text" name="hero_trust_badge1_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('hero_trust_badge1_en', $settings['hero_trust_badge1_en'] ?? '') }}" placeholder="No credit card required">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Վստահության Բեյջ 2 (HY / EN)</label>
                        <input type="text" name="hero_trust_badge2_hy" class="form-input" value="{{ old('hero_trust_badge2_hy', $settings['hero_trust_badge2_hy'] ?? '') }}" placeholder="Գործարկում 5 րոպեում">
                        <input type="text" name="hero_trust_badge2_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('hero_trust_badge2_en', $settings['hero_trust_badge2_en'] ?? '') }}" placeholder="Ready in 5 minutes">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Վստահության Բեյջ 3 (HY / EN)</label>
                        <div class="form-grid">
                            <input type="text" name="hero_trust_badge3_hy" class="form-input" value="{{ old('hero_trust_badge3_hy', $settings['hero_trust_badge3_hy'] ?? '') }}" placeholder="24/7 աջակցություն & օգնություն">
                            <input type="text" name="hero_trust_badge3_en" class="form-input" value="{{ old('hero_trust_badge3_en', $settings['hero_trust_badge3_en'] ?? '') }}" placeholder="24/7 priority support">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1.2 Comparison Section CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-code-compare" style="color: #6366f1;"></i>
                    <span>2. Համեմատություն (Թղթային ընդդեմ Թվային QR)</span>
                </div>

                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Բաժնի Բեյջ (HY / EN)</label>
                        <input type="text" name="vs_badge_hy" class="form-input" value="{{ old('vs_badge_hy', $settings['vs_badge_hy'] ?? '') }}" placeholder="Ինչո՞ւ Թվային">
                        <input type="text" name="vs_badge_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('vs_badge_en', $settings['vs_badge_en'] ?? '') }}" placeholder="Why Digital">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Բաժնի Վերնագիր (HY / EN)</label>
                        <input type="text" name="vs_title_hy" class="form-input" value="{{ old('vs_title_hy', $settings['vs_title_hy'] ?? '') }}" placeholder="Թղթային Մենյու ընդդեմ elab QR Մենյուի">
                        <input type="text" name="vs_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('vs_title_en', $settings['vs_title_en'] ?? '') }}" placeholder="Paper Menu vs elab Digital QR Menu">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Բաժնի Ենթավերնագիր (HY / EN)</label>
                        <div class="form-grid">
                            <input type="text" name="vs_subtitle_hy" class="form-input" value="{{ old('vs_subtitle_hy', $settings['vs_subtitle_hy'] ?? '') }}" placeholder="Ինչո՞ւ են առաջատար ռեստորանները հրաժարվում թղթային մենյուներից">
                            <input type="text" name="vs_subtitle_en" class="form-input" value="{{ old('vs_subtitle_en', $settings['vs_subtitle_en'] ?? '') }}" placeholder="Why top restaurants worldwide are replacing traditional paper menus">
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Թղթային Մենյուի Քարտ (Վերնագիր / Բեյջ)</label>
                        <input type="text" name="vs_paper_title_hy" class="form-input" value="{{ old('vs_paper_title_hy', $settings['vs_paper_title_hy'] ?? '') }}" placeholder="Ավանդական Թղթային Մենյու">
                        <input type="text" name="vs_paper_badge_hy" class="form-input" style="margin-top: 0.35rem;" value="{{ old('vs_paper_badge_hy', $settings['vs_paper_badge_hy'] ?? '') }}" placeholder="Հնացած & Թանկ">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Թվային QR Մենյուի Քարտ (Վերնագիր / Բեյջ)</label>
                        <input type="text" name="vs_qr_title_hy" class="form-input" value="{{ old('vs_qr_title_hy', $settings['vs_qr_title_hy'] ?? '') }}" placeholder="elab Թվային QR Մենյու + AI">
                        <input type="text" name="vs_qr_badge_hy" class="form-input" style="margin-top: 0.35rem;" value="{{ old('vs_qr_badge_hy', $settings['vs_qr_badge_hy'] ?? '') }}" placeholder="Ժամանակակից & Շահավետ">
                    </div>
                </div>
            </div>

            <!-- 1.3 AI Waiter Section CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-robot" style="color: #a855f7;"></i>
                    <span>3. AI Մատուցող & Սոմելիե (AI Waiter Section)</span>
                </div>

                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Բաժնի Բեյջ (HY / EN)</label>
                        <input type="text" name="ai_section_badge_hy" class="form-input" value="{{ old('ai_section_badge_hy', $settings['ai_section_badge_hy'] ?? '') }}" placeholder="Գլխավոր Մրցակցային Առավելություն">
                        <input type="text" name="ai_section_badge_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('ai_section_badge_en', $settings['ai_section_badge_en'] ?? '') }}" placeholder="Key Competitive Advantage">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Բաժնի Վերնագիր (HY / EN)</label>
                        <input type="text" name="ai_section_title_hy" class="form-input" value="{{ old('ai_section_title_hy', $settings['ai_section_title_hy'] ?? '') }}" placeholder="AI Մատուցող և Խելացի Սոմելիե">
                        <input type="text" name="ai_section_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('ai_section_title_en', $settings['ai_section_title_en'] ?? '') }}" placeholder="AI Waiter & Smart Sommelier">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Բաժնի Ենթավերնագիր (HY / EN)</label>
                        <div class="form-grid">
                            <textarea name="ai_section_subtitle_hy" rows="2" class="form-textarea" placeholder="Ձեր լավագույն աշխատակիցը, ով երբեք չի հոգնում, գիտի բոլոր ուտեստները...">{{ old('ai_section_subtitle_hy', $settings['ai_section_subtitle_hy'] ?? '') }}</textarea>
                            <textarea name="ai_section_subtitle_en" rows="2" class="form-textarea" placeholder="Your best team member who never tires, knows every ingredient...">{{ old('ai_section_subtitle_en', $settings['ai_section_subtitle_en'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- AI Feature Cards -->
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">AI Հնարավորություն 1: Խելացի Զուգակցումներ</label>
                        <input type="text" name="ai_feature1_title_hy" class="form-input" value="{{ old('ai_feature1_title_hy', $settings['ai_feature1_title_hy'] ?? '') }}" placeholder="Խելացի Զուգակցումներ (Smart Pairings)">
                        <textarea name="ai_feature1_desc_hy" rows="2" class="form-textarea" style="margin-top: 0.35rem;" placeholder="Համակարգն ավտոմատ առաջարկում է իդեալական ըմպելիք կամ խավարտ...">{{ old('ai_feature1_desc_hy', $settings['ai_feature1_desc_hy'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">AI Հնարավորություն 2: Ալերգեններ & Դիետա</label>
                        <input type="text" name="ai_feature2_title_hy" class="form-input" value="{{ old('ai_feature2_title_hy', $settings['ai_feature2_title_hy'] ?? '') }}" placeholder="Ալերգենների և Դիետայի Խորհրդատու">
                        <textarea name="ai_feature2_desc_hy" rows="2" class="form-textarea" style="margin-top: 0.35rem;" placeholder="Հաճախորդը կարող է ճշտել կալորիաները, գլյուտենը կամ բաղադրիչները...">{{ old('ai_feature2_desc_hy', $settings['ai_feature2_desc_hy'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 1.4 Calculator & Features CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-calculator" style="color: #10b981;"></i>
                    <span>4. Եկամտի Հաշվիչ & Bento Հնարավորություններ</span>
                </div>

                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Հաշվիչի Վերնագիր (HY / EN)</label>
                        <input type="text" name="calc_title_hy" class="form-input" value="{{ old('calc_title_hy', $settings['calc_title_hy'] ?? '') }}" placeholder="Որքա՞ն Լրացուցիչ Եկամուտ Կբերի elab QR Մենյուն">
                        <input type="text" name="calc_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('calc_title_en', $settings['calc_title_en'] ?? '') }}" placeholder="How Much Extra Revenue Will elab QR Menu Generate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Հաշվիչի Կոճակի Տեքստ (HY / EN)</label>
                        <input type="text" name="calc_cta_hy" class="form-input" value="{{ old('calc_cta_hy', $settings['calc_cta_hy'] ?? '') }}" placeholder="Սկսել Ստանալ Այս Արդյունքը">
                        <input type="text" name="calc_cta_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('calc_cta_en', $settings['calc_cta_en'] ?? '') }}" placeholder="Start Getting These Results">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Հնարավորությունների Բաժնի Վերնագիր (HY / EN)</label>
                        <div class="form-grid">
                            <input type="text" name="features_title_hy" class="form-input" value="{{ old('features_title_hy', $settings['features_title_hy'] ?? '') }}" placeholder="Ամեն Ինչ, Ինչ Պետք է Ձեր Բիզնեսին">
                            <input type="text" name="features_title_en" class="form-input" value="{{ old('features_title_en', $settings['features_title_en'] ?? '') }}" placeholder="Everything Your Restaurant Business Needs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1.5 Pricing & FAQ CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-tags" style="color: #06b6d4;"></i>
                    <span>5. Փաթեթներ & Հաճախ Տրվող Հարցեր (Pricing & FAQ)</span>
                </div>

                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Փաթեթների Բաժնի Վերնագիր (HY / EN)</label>
                        <input type="text" name="pricing_title_hy" class="form-input" value="{{ old('pricing_title_hy', $settings['pricing_title_hy'] ?? '') }}" placeholder="Թափանցիկ Սակագներ Առանց Թաքնված Վճարների">
                        <input type="text" name="pricing_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('pricing_title_en', $settings['pricing_title_en'] ?? '') }}" placeholder="Transparent Pricing with No Hidden Fees">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ՀՏՀ (FAQ) Բաժնի Վերնագիր (HY / EN)</label>
                        <input type="text" name="faq_title_hy" class="form-input" value="{{ old('faq_title_hy', $settings['faq_title_hy'] ?? '') }}" placeholder="Հաճախ Տրվող Հարցեր">
                        <input type="text" name="faq_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('faq_title_en', $settings['faq_title_en'] ?? '') }}" placeholder="Frequently Asked Questions">
                    </div>
                </div>
            </div>

            <!-- 1.6 Final CTA Banner & Demo Restaurant CMS -->
            <div class="settings-card">
                <div class="settings-section-title">
                    <i class="fa-solid fa-bullhorn" style="color: #ec4899;"></i>
                    <span>6. Վերջնական CTA Բաններ & Դեմո Ռեստորան</span>
                </div>

                <div class="form-grid" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Վերջնական Բանների Վերնագիր (HY / EN)</label>
                        <input type="text" name="cta_banner_title_hy" class="form-input" value="{{ old('cta_banner_title_hy', $settings['cta_banner_title_hy'] ?? '') }}" placeholder="Պատրա՞ստ եք ռեստորանը տեղափոխել նոր մակարդակ">
                        <input type="text" name="cta_banner_title_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('cta_banner_title_en', $settings['cta_banner_title_en'] ?? '') }}" placeholder="Ready to Transform Your Dining Experience?">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Վերջնական Կոճակի Տեքստ (HY / EN)</label>
                        <input type="text" name="cta_banner_btn_text_hy" class="form-input" value="{{ old('cta_banner_btn_text_hy', $settings['cta_banner_btn_text_hy'] ?? '') }}" placeholder="Սկսել 14 Օր Անվճար">
                        <input type="text" name="cta_banner_btn_text_en" class="form-input" style="margin-top: 0.35rem;" value="{{ old('cta_banner_btn_text_en', $settings['cta_banner_btn_text_en'] ?? '') }}" placeholder="Start 14 Days Free">
                    </div>
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
                        <span class="input-hint">Ցուցադրվում է լենդինգի CTA-ներում և գրանցման ժամանակ</span>
                    </div>
                </div>

                <div class="preview-box">
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-box-open" style="color: #f59e0b;"></i> Փաթեթների Գները (Pricing)
                        </strong>
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            Գները և ֆունկցիաները ավտոմատ վերցվում են <a href="{{ route('superadmin.plans.index') }}" style="color: var(--primary); text-decoration: underline;">«Փաթեթներ» (Plans)</a> բաժնից։
                        </span>
                    </div>
                    <a href="{{ route('superadmin.plans.index') }}" class="btn" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.82rem;">
                        Կառավարել Փաթեթները
                    </a>
                </div>
            </div>
        </div>