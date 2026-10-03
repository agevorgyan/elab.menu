<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteName = \App\Models\SystemSetting::getSiteName();
        $siteFavicon = \App\Models\SystemSetting::getFavicon();
        $siteLogo = \App\Models\SystemSetting::getLogoDark();
        $contactEmail = \App\Models\SystemSetting::get('contact_email') ?: 'support@elab.am';
    @endphp
    <title>Օգտագործման Պայմաններ | Terms of Service — {{ $siteName }}</title>
    <link rel="icon" type="image/png" href="{{ $siteFavicon }}">
    <link rel="apple-touch-icon" href="{{ $siteFavicon }}">

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

    <style>
        :root {
            --bg-main: #070913;
            --bg-card: rgba(18, 25, 44, 0.75);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-amber: #f59e0b;
            --accent-gradient: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            --shadow-card: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 3rem 1rem;
            line-height: 1.65;
            position: relative;
            overflow-x: hidden;
        }
        .ambient-glow {
            position: absolute;
            top: 0;
            left: 30%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, rgba(239, 68, 68, 0.04) 50%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        .container {
            max-width: 840px;
            margin: 0 auto;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: clamp(1.75rem, 4vw, 3rem);
            box-shadow: var(--shadow-card);
            position: relative;
            z-index: 1;
        }
        .brand-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            flex-wrap: wrap;
            gap: 1rem;
        }
        .brand-logo-img {
            max-height: 38px;
            max-width: 170px;
            object-fit: contain;
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            line-height: 1.25;
        }
        .updated-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-amber);
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1.5rem;
        }
        h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent-amber);
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        ul {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-left: 1.5rem;
            margin-bottom: 1.25rem;
        }
        li {
            margin-bottom: 0.5rem;
        }
        li strong {
            color: var(--text-main);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--accent-gradient);
            color: #ffffff;
            text-decoration: none;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            margin-top: 2.25rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
        }
    </style>
</head>
<body>
    <div class="ambient-glow"></div>
    <div class="container">
        <div class="brand-header">
            <a href="{{ route('landing') }}" style="display: flex; align-items: center; text-decoration: none;">
                @if($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="brand-logo-img">
                @else
                    <span style="font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--text-main);">{{ $siteName }}</span>
                @endif
            </a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="javascript:history.back()" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if(app()->getLocale() === 'en')
            <h1>📜 Terms of Service</h1>
            <div class="updated-pill">
                <i class="fa-solid fa-clock"></i> Last updated: {{ date('F d, Y') }}
            </div>

            <h2>1. Description of Service</h2>
            <p>The {{ $siteName }} platform provides digital menu browsing, table-side ordering, and venue workflow automation. By accessing and using the digital menu, you agree to comply with and be bound by these Terms of Service.</p>

            <h2>2. Orders & Venue Responsibility</h2>
            <ul>
                <li>The customer undertakes to provide accurate and truthful information (Name, Phone number, Email, Table number) to ensure correct fulfillment.</li>
                <li>The servicing partner establishment (Restaurant/Cafe) carries full and exclusive responsibility for food preparation quality, ingredients, and allergen notices.</li>
            </ul>

            <h2>3. Pricing & Payments</h2>
            <p>All prices presented on the digital menu include statutory taxes (unless explicitly stated otherwise). Payments are processed on premise (Dine-in / Cash / POS) or through direct confirmation with the establishment via WhatsApp/Online payment methods.</p>

            <h2>4. Intellectual Property</h2>
            <p>The digital menu design, trademarks, software code, and interface layouts remain the intellectual property of {{ $siteName }} and its partner venues.</p>

            <h2>5. Contact Information</h2>
            <p>If you have any questions regarding these Terms of Service, please contact our support team at <strong style="color: var(--accent-amber);">{{ $contactEmail }}</strong></p>

            <a href="javascript:history.back()" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Menu
            </a>
        @else
            <h1>📜 Օգտագործման Պայմաններ</h1>
            <div class="updated-pill">
                <i class="fa-solid fa-clock"></i> Վերջին թարմացում՝ {{ date('d.m.Y') }}
            </div>

            <h2>1. Ծառայության Նկարագրությունը</h2>
            <p>{{ $siteName }} հարթակը տրամադրում է թվային մենյուների դիտման, պատվերների ձևակերպման և ռեստորանային սպասարկման ավտոմատացման համակարգ։ Օգտվելով թվային մենյուից՝ Դուք համաձայնում եք սույն Օգտագործման Պայմանների հետ։</p>

            <h2>2. Պատվերների Ձևակերպումը և Պատասխանատվությունը</h2>
            <ul>
                <li>Հաճախորդը պարտավորվում է տրամադրել ճշգրիտ տվյալներ (Անուն, Հեռախոսահամար, Էլ․ փոստ, Սեղանի համար) պատվերը պատշաճ կատարելու համար։</li>
                <li>Պատվիրված ուտեստների և ըմպելիքների պատրաստման որակի, բաղադրության և ալերգենների պատասխանատվությունը կրում է սպասարկող հաստատությունը (Ռեստորանը/Սրճարանը)։</li>
            </ul>

            <h2>3. Գնագոյացում և Վճարումներ</h2>
            <p>Մենյուում նշված բոլոր գները ներառում են օրենքով սահմանված հարկերը (եթե այլ բան նախատեսված չէ)։ Վճարումը կատարվում է տեղում (Dine-in / Cash / POS) կամ WhatsApp / Online պատվերի դեպքում հաստատության հետ համաձայնեցված եղանակով։</p>

            <h2>4. Մտավոր Սեփականություն</h2>
            <p>Մենյուի դիզայնը, լոգոները, նկարները և ծրագրային կոդը հանդիսանում են {{ $siteName }}-ի և համապատասխան Գործընկերոջ մտավոր սեփականությունը։</p>

            <h2>5. Կոնտակտային Տվյալներ</h2>
            <p>Օգտագործման պայմանների հետ կապված հարցերի դեպքում կարող եք կապ հաստատել աջակցման թիմի հետ՝ <strong style="color: var(--accent-amber);">{{ $contactEmail }}</strong></p>

            <a href="javascript:history.back()" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Վերադառնալ Մենյու
            </a>
        @endif
    </div>
</body>
</html>
