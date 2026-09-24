<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Գրանցում — QRMenu SaaS Platform</title>
    
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;
            --border-card: #e2e8f0;
            --border-input: #cbd5e1;
            --border-focus: #f59e0b;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --accent-amber: #f59e0b;
            --accent-amber-dark: #d97706;
            --accent-gradient: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            --shadow-card: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
            --shadow-input-focus: 0 0 0 3px rgba(245, 158, 11, 0.18);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(245, 158, 11, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 90% 85%, rgba(59, 130, 246, 0.06) 0%, transparent 45%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            position: relative;
        }

        .register-container {
            width: 100%;
            max-width: 860px;
            position: relative;
            z-index: 10;
        }

        /* Top Navigation Bar */
        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            padding: 0 0.25rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.88rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .back-link:hover {
            color: var(--text-primary);
            transform: translateX(-3px);
        }

        .top-nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 999px;
            color: #b45309;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-pill-btn:hover {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
            transform: translateY(-1px);
        }

        /* Main Registration Card */
        .register-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 28px;
            padding: 2.75rem 2.5rem;
            box-shadow: var(--shadow-card);
            position: relative;
        }

        @media (max-width: 640px) {
            .register-card {
                padding: 1.75rem 1.25rem;
                border-radius: 20px;
            }
        }

        /* Brand & Header */
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.95rem;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            color: #047857;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.15rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .brand-title span {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-subtitle {
            font-size: 0.95rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* Demo Notice Banner */
        .demo-bar-banner {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 14px;
            padding: 0.85rem 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .demo-bar-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.86rem;
            color: #92400e;
            font-weight: 500;
        }

        .demo-bar-info i {
            color: #d97706;
            font-size: 1.1rem;
        }

        .btn-demo-inline {
            color: #ffffff;
            font-weight: 700;
            font-size: 0.82rem;
            text-decoration: none;
            padding: 0.4rem 0.85rem;
            background: #d97706;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .btn-demo-inline:hover {
            background: #b45309;
            transform: translateY(-1px);
        }

        /* Error Alert */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 1rem 1.25rem;
            border-radius: 14px;
            font-size: 0.88rem;
            margin-bottom: 2rem;
        }

        .alert-error ul {
            padding-left: 1.25rem;
            margin-top: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        /* Section Headings */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 2rem 0 1.25rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-header:first-of-type {
            margin-top: 0;
        }

        .section-number {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #b45309;
            font-size: 0.82rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.15rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 0.83rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.45rem;
        }

        .form-label .req {
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: #94a3b8;
            font-size: 0.92rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.78rem 1rem 0.78rem 2.65rem;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-textarea {
            padding-left: 1rem;
            resize: vertical;
            min-height: 80px;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.15rem;
            padding-right: 2.75rem;
            cursor: pointer;
        }

        .form-input.has-toggle {
            padding-right: 2.65rem;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--border-focus);
            background: #ffffff;
            box-shadow: var(--shadow-input-focus);
        }

        .form-input:focus + .input-icon, .form-select:focus + .input-icon {
            color: var(--accent-amber-dark);
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 0.95rem;
            cursor: pointer;
            padding: 0.35rem;
            border-radius: 6px;
            transition: color 0.2s ease;
        }

        .password-toggle-btn:hover {
            color: #475569;
        }

        /* Submit Button */
        .btn-submit-register {
            width: 100%;
            padding: 1rem;
            background: var(--accent-gradient);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.08rem;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }

        .btn-submit-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
            opacity: 0.96;
        }

        .terms-note {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 1rem;
            line-height: 1.5;
        }

        .terms-note a {
            color: var(--text-secondary);
            text-decoration: underline;
        }

        .terms-note a:hover {
            color: var(--text-primary);
        }

        /* Login Redirect Footer */
        .login-redirect-box {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .login-redirect-box a {
            color: #d97706;
            font-weight: 700;
            text-decoration: none;
            margin-left: 0.35rem;
            transition: color 0.2s ease;
        }

        .login-redirect-box a:hover {
            color: #b45309;
            text-decoration: underline;
        }

        .footer-note {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .footer-note a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-note a:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Top Navigation -->
        <div class="top-nav">
            <a href="/" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Գլխավոր էջ
            </a>
            <div class="top-nav-actions">
                <a href="{{ route('demo.login') }}" class="nav-pill-btn">
                    <i class="fa-solid fa-bolt"></i> Փորձարկել Դեմոն
                </a>
                <a href="{{ route('login') }}" class="nav-pill-btn" style="background: #ffffff; border-color: #cbd5e1; color: #334155;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Մուտք
                </a>
            </div>
        </div>

        <div class="register-card">
            <!-- Header -->
            <div class="brand-header">
                <div class="trial-badge">
                    <i class="fa-solid fa-gift"></i> 14 Օր Անվճար Փորձաշրջան • Բանկային քարտ չի պահանջվում
                </div>
                <h1 class="brand-title">
                    Vendor <span>Ինքնուրույն Գրանցում</span>
                </h1>
                <p class="brand-subtitle">
                    Ստեղծեք ձեր հաշիվը 2 րոպեում և սկսեք ընդունել առցանց պատվերներ թվային QR մենյուի միջոցով
                </p>
            </div>

            <!-- Demo Link Banner -->
            <div class="demo-bar-banner">
                <div class="demo-bar-info">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Ցանկանո՞ւմ եք նախ ծանոթանալ ծրագրին առանց գրանցվելու։</span>
                </div>
                <a href="{{ route('demo.login') }}" class="btn-demo-inline">
                    <span>Ուսումնասիրել Դեմո Տարբերակը</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            @if($errors->any())
                <div class="alert-error">
                    <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Խնդրում ենք ուղղել հետևյալ սխալները․</span>
                    </div>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.post') }}" method="POST">
                @csrf

                <!-- 1. Օբյեկտի Տվյալներ -->
                <div class="section-header">
                    <div class="section-number">1</div>
                    <div class="section-title">Ռեստորանի / Օբյեկտի Տվյալներ</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Օբյեկտի Տեսակը <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <select name="type" class="form-select" required>
                                <option value="restaurant" {{ old('type') == 'restaurant' ? 'selected' : '' }}>Ռեստորան (Restaurant)</option>
                                <option value="cafe" {{ old('type') == 'cafe' ? 'selected' : '' }}>Սրճարան (Cafe)</option>
                                <option value="hotel" {{ old('type') == 'hotel' ? 'selected' : '' }}>Հյուրանոց / Լաունջ (Hotel & Lounge)</option>
                            </select>
                            <i class="fa-solid fa-utensils input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Բրենդի Անվանումը <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="name" class="form-input" required placeholder="օր․ Verona Lounge & Cafe" value="{{ old('name') }}">
                            <i class="fa-solid fa-store input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Մասնաճյուղերի քանակ (Locations) <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="number" name="expected_locations_count" min="1" max="100" class="form-input" required value="{{ old('expected_locations_count', 1) }}">
                            <i class="fa-solid fa-network-wired input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Սակագնային Պլան (14 օր անվճար) <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <select name="subscription_plan" class="form-select" required>
                                @if(isset($plans) && $plans->count())
                                    @foreach($plans as $p)
                                        <option value="{{ $p->slug }}" {{ old('subscription_plan', request('plan', 'pro')) == $p->slug ? 'selected' : '' }}>
                                            {{ $p->name }} — {{ $p->formatted_price }} (14 օր անվճար)
                                        </option>
                                    @endforeach
                                @else
                                    <option value="basic" {{ old('subscription_plan', request('plan', 'pro')) == 'basic' ? 'selected' : '' }}>Basic — 9 900 AMD / ամիս (14 օր անվճար)</option>
                                    <option value="pro" {{ old('subscription_plan', request('plan', 'pro')) == 'pro' ? 'selected' : '' }}>Pro — 19 900 AMD / ամիս (14 օր անվճար)</option>
                                    <option value="business" {{ old('subscription_plan', request('plan', 'pro')) == 'business' ? 'selected' : '' }}>Business — 34 900 AMD / ամիս (14 օր անվճար)</option>
                                    <option value="custom" {{ old('subscription_plan', request('plan', 'pro')) == 'custom' ? 'selected' : '' }}>Custom — Պայմանագրային</option>
                                @endif
                            </select>
                            <i class="fa-solid fa-crown input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Գործունեության հասցե կամ հասցեներ <span class="req">*</span></label>
                        <textarea name="operating_address" rows="2" class="form-textarea" required placeholder="օր․ Մաշտոցի պողոտա 15, Երևան / Թամանյան 2, Երևան">{{ old('operating_address') }}</textarea>
                    </div>
                </div>

                <!-- 2. Իրավաբանական Տվյալներ -->
                <div class="section-header">
                    <div class="section-number">2</div>
                    <div class="section-title">Իրավաբանական Տվյալներ</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Իրավաբանական Անվանում (ՍՊԸ/ԱՁ) <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="legal_name" class="form-input" required placeholder="օր․ «Վերոնա Լաունջ» ՍՊԸ" value="{{ old('legal_name') }}">
                            <i class="fa-solid fa-building input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ՀՎՀՀ (Tax ID) <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="tax_id" class="form-input" required placeholder="օր․ 02589412" value="{{ old('tax_id') }}">
                            <i class="fa-solid fa-hashtag input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Իրավաբանական Հասցե <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="legal_address" class="form-input" required placeholder="օր․ ք․ Երևան, Բաղրամյան 24" value="{{ old('legal_address') }}">
                            <i class="fa-solid fa-map-location-dot input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Տնօրենի Անուն Ազգանուն <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="director_name" class="form-input" required placeholder="օր․ Արմեն Պետրոսյան" value="{{ old('director_name') }}">
                            <i class="fa-solid fa-user-tie input-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- 3. Կոնտակտ & Մուտքային Հաշիվ -->
                <div class="section-header">
                    <div class="section-number">3</div>
                    <div class="section-title">Կոնտակտային Տվյալներ & Մուտքային Հաշիվ</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Կոնտակտային Անձի Անուն <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="contact_person_name" class="form-input" required placeholder="օր․ Անահիտ Սարգսյան" value="{{ old('contact_person_name') }}">
                            <i class="fa-solid fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Հեռախոսահամար <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="phone" class="form-input" required placeholder="օր․ +37491000111" value="{{ old('phone') }}">
                            <i class="fa-solid fa-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Էլ․ Փոստ (Login Email) <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-input" required placeholder="info@veronacafe.am" value="{{ old('email') }}">
                            <i class="fa-solid fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Գաղտնաբառ <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="regPasswordInput" class="form-input has-toggle" required placeholder="••••••••">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <button type="button" class="password-toggle-btn" id="toggleRegPasswordBtn" title="Ցուցադրել/Թաքցնել գաղտնաբառը">
                                <i class="fa-regular fa-eye" id="toggleRegPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Կրկնել Գաղտնաբառը <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" name="password_confirmation" id="regPasswordConfirmInput" class="form-input has-toggle" required placeholder="••••••••">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <button type="button" class="password-toggle-btn" id="toggleRegPasswordConfirmBtn" title="Ցուցադրել/Թաքցնել գաղտնաբառը">
                                <i class="fa-regular fa-eye" id="toggleRegPasswordConfirmIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit-register">
                    <i class="fa-solid fa-rocket"></i>
                    <span>Գրանցվել և Սկսել 14-Օրյա Անվճար Փորձաշրջանը</span>
                </button>

                <p class="terms-note">
                    Գրանցվելով դուք համաձայնում եք մեր 
                    <a href="{{ route('legal.terms') }}">Օգտագործման Պայմաններին</a> և 
                    <a href="{{ route('legal.privacy') }}">Գաղտնիության Քաղաքականությանը</a>։
                </p>

                <div class="login-redirect-box">
                    Արդեն ունե՞ք գրանցված հաշիվ։ 
                    <a href="{{ route('login') }}">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Մուտք գործել այստեղ
                    </a>
                </div>
            </form>
        </div>

        <div class="footer-note">
            <span>&copy; {{ date('Y') }} QRMenu SaaS Platform</span>
            <span>•</span>
            <a href="{{ route('legal.privacy') }}">Գաղտնիություն</a>
            <span>•</span>
            <a href="{{ route('legal.terms') }}">Պայմաններ</a>
        </div>
    </div>

    <script>
        // Password Visibility Toggles
        function setupToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (btn && input && icon) {
                btn.addEventListener('click', function () {
                    const isPass = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPass ? 'text' : 'password');
                    icon.classList.toggle('fa-eye', !isPass);
                    icon.classList.toggle('fa-eye-slash', isPass);
                });
            }
        }

        setupToggle('toggleRegPasswordBtn', 'regPasswordInput', 'toggleRegPasswordIcon');
        setupToggle('toggleRegPasswordConfirmBtn', 'regPasswordConfirmInput', 'toggleRegPasswordConfirmIcon');
    </script>
</body>
</html>
