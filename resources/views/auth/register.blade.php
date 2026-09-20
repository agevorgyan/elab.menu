<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Գրանցում - QRMenu SaaS Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, button, input, select, textarea { font-family: 'Inter', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a 60%, #090d16);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .register-card {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            width: 100%;
            max-width: 800px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-header i { font-size: 2.5rem; color: #f59e0b; margin-bottom: 0.5rem; }
        .brand-header h1 { font-family: 'Outfit', sans-serif; font-size: 1.85rem; font-weight: 800; background: linear-gradient(135deg, #f59e0b, #ef4444); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #f59e0b;
            margin-bottom: 1rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid rgba(245, 158, 11, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.35rem; }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.7rem 0.9rem;
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            outline: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #000;
            font-weight: 800;
            font-size: 1.05rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="brand-header">
            <i class="fa-solid fa-qrcode"></i>
            <h1>Vendor Ինքնուրույն Գրանցում</h1>
            <p style="font-size: 0.85rem; color: #94a3b8;">Միացեք QR Menu SaaS հարթակին րոպեների ընթացքում</p>
        </div>

        @if($errors->any())
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 0.85rem 1rem; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1.5rem;">
                <ul style="padding-left: 1.25rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf

            <!-- 1. Օբյեկտի Տվյալներ -->
            <div class="section-title">
                <i class="fa-solid fa-store"></i> 1. Օբյեկտի Տվյալներ
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Օբյեկտի Տեսակը *</label>
                    <select name="type" class="form-select" required>
                        <option value="restaurant" {{ old('type') == 'restaurant' ? 'selected' : '' }}>Ռեստորան (Restaurant)</option>
                        <option value="cafe" {{ old('type') == 'cafe' ? 'selected' : '' }}>Սրճարան (Cafe)</option>
                        <option value="hotel" {{ old('type') == 'hotel' ? 'selected' : '' }}>Հյուրանոց Լաունջ (Hotel & Lounge)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Անվանումը (Բրենդի անուն) *</label>
                    <input type="text" name="name" class="form-input" required placeholder="օր․ Verona Lounge & Cafe" value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label>Մասնաճյուղերի քանակ (Locations) *</label>
                    <input type="number" name="expected_locations_count" min="1" max="100" class="form-input" required value="{{ old('expected_locations_count', 1) }}">
                </div>

                <div class="form-group">
                    <label>Բաժանորդագրության տեսակ (14 օր անվճար) *</label>
                    <select name="subscription_plan" class="form-select" required>
                        @if(isset($plans) && $plans->count())
                            @foreach($plans as $p)
                                <option value="{{ $p->slug }}" {{ old('subscription_plan', 'pro') == $p->slug ? 'selected' : '' }}>
                                    {{ $p->name }} — {{ $p->formatted_price }} (14 օր անվճար)
                                </option>
                            @endforeach
                        @else
                            <option value="basic" {{ old('subscription_plan') == 'basic' ? 'selected' : '' }}>Basic — 9 900 AMD / ամիս (14 օր անվճար)</option>
                            <option value="pro" {{ old('subscription_plan', 'pro') == 'pro' ? 'selected' : '' }}>Pro — 19 900 AMD / ամիս (14 օր անվճար)</option>
                            <option value="business" {{ old('subscription_plan') == 'business' ? 'selected' : '' }}>Business — 34 900 AMD / ամիս (14 օր անվճար)</option>
                            <option value="custom" {{ old('subscription_plan') == 'custom' ? 'selected' : '' }}>Custom — Պայմանագրային</option>
                        @endif
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Գործունեության հասցե կամ հասցեներ *</label>
                    <textarea name="operating_address" rows="2" class="form-textarea" required placeholder="օր․ Մաշտոցի պողոտա 15, Երևան / Թամանյան 2, Երևան">{{ old('operating_address') }}</textarea>
                </div>
            </div>

            <!-- 2. Իրավաբանական Տվյալներ -->
            <div class="section-title">
                <i class="fa-solid fa-scale-balanced"></i> 2. Իրավաբանական Տվյալներ
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Իրավաբանական անվանումը (ՍՊԸ/ԱՁ) *</label>
                    <input type="text" name="legal_name" class="form-input" required placeholder="օր․ «Վերոնա Լաունջ» ՍՊԸ" value="{{ old('legal_name') }}">
                </div>

                <div class="form-group">
                    <label>ՀՎՀՀ (Tax ID) *</label>
                    <input type="text" name="tax_id" class="form-input" required placeholder="օր․ 02589412" value="{{ old('tax_id') }}">
                </div>

                <div class="form-group">
                    <label>Իրավաբանական հասցե *</label>
                    <input type="text" name="legal_address" class="form-input" required placeholder="օր․ ք․ Երևան, Բաղրամյան 24" value="{{ old('legal_address') }}">
                </div>

                <div class="form-group">
                    <label>Տնօրենի Անուն Ազգանուն *</label>
                    <input type="text" name="director_name" class="form-input" required placeholder="օր․ Արմեն Պետրոսյան" value="{{ old('director_name') }}">
                </div>
            </div>

            <!-- 3. Կոնտակտային Տվյալներ & Մուտք -->
            <div class="section-title">
                <i class="fa-solid fa-user-shield"></i> 3. Կոնտակտային Տվյալներ & Մուտքային Հաշիվ
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Կոնտակտային անձի Անուն Ազգանուն *</label>
                    <input type="text" name="contact_person_name" class="form-input" required placeholder="օր․ Անահիտ Սարգսյան" value="{{ old('contact_person_name') }}">
                </div>

                <div class="form-group">
                    <label>Հեռախոսահամար *</label>
                    <input type="text" name="phone" class="form-input" required placeholder="օր․ +37491000111" value="{{ old('phone') }}">
                </div>

                <div class="form-group full-width">
                    <label>Էլ․ փոստի հասցե (Email) *</label>
                    <input type="email" name="email" class="form-input" required placeholder="info@veronacafe.am" value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label>Գաղտնաբառ *</label>
                    <input type="password" name="password" class="form-input" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label>Կրկնել Գաղտնաբառը *</label>
                    <input type="password" name="password_confirmation" class="form-input" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-paper-plane"></i> Գրանցվել և Ստանալ Մուտք
            </button>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #94a3b8;">
                Արդեն ունե՞ք հաշիվ։ <a href="{{ route('login') }}" style="color: #f59e0b; font-weight: 700; text-decoration: none;">Մուտք գործել այստեղ</a>
            </div>
        </form>
    </div>
</body>
</html>
