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
    <title>Գաղտնիության Քաղաքականություն | Privacy Policy — {{ $siteName }}</title>
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
            <a href="javascript:history.back()" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;">
                <i class="fa-solid fa-arrow-left"></i> Վերադառնալ
            </a>
        </div>

        <h1>🔒 Գաղտնիության Քաղաքականություն</h1>
        <div class="updated-pill">
            <i class="fa-solid fa-clock"></i> Վերջին թարմացում՝ {{ date('d.m.Y') }}
        </div>

        <h2>1. Ընդհանուր Դրույթներ</h2>
        <p>Սույն Գաղտնիության Քաղաքականությունը սահմանում է, թե ինչպես է {{ $siteName }} հարթակը և գործընկեր հաստատությունները (Ռեստորաններ, Սրճարաններ, Հյուրանոցներ) հավաքագրում, օգտագործում, պահպանում և պաշտպանում Հաճախորդների անձնական տվյալները թվային մենյուից պատվեր կատարելիս։</p>

        <h2>2. Հավաքագրվող Տվյալները</h2>
        <p>Պատվերի գրանցման ժամանակ կարող են հավաքագրվել հետևյալ տվյալները․</p>
        <ul>
            <li><strong>Անուն և Ազգանուն</strong> — Պատվերը սպասարկելու և հաճախորդին նույնականացնելու համար։</li>
            <li><strong>Հեռախոսահամար</strong> — Պատվերի կարգավիճակի (Dine-in / Takeaway / WhatsApp) վերաբերյալ կապ հաստատելու համար։</li>
            <li><strong>Էլեկտրոնային փոստի հասցե (Email)</strong> — Պատվերի անդորրագիր ուղարկելու և անհատական առաջարկներ տրամադրելու համար։</li>
            <li><strong>Սեղանի համար կամ հասցե</strong> — Հաստատությունում սպասարկումն ապահովելու համար։</li>
        </ul>

        <h2>3. Տվյալների Օգտագործման Նպատակները և Մարքեթինգ</h2>
        <p>Ձեր տվյալները մշակվում են խիստ գաղտնիության պահպանմամբ։ Օգտագործման նպատակներն են․</p>
        <ul>
            <li>Պատվերի ընդունում, պատրաստում և սպասարկում։</li>
            <li>Հաճախորդների սպասարկման որակի բարձրացում։</li>
            <li><strong>Մարքեթինգային ծանուցումներ․</strong> Համաձայնության դեպքում Ձեր էլ․ փոստին կամ հեռախոսահամարին կարող են ուղարկվել հատուկ զեղչերի, նոր մենյուի և ակցիաների վերաբերյալ ծանուցումներ։ Դուք ցանկացած պահի կարող եք հրաժարվել մարքեթինգային ծանուցումներից։</li>
        </ul>

        <h2>4. Տվյալների Պաշտպանությունը և Գաղտնիությունը</h2>
        <p>Մենք կիրառում ենք ժամանակակից կոդավորման (SSL/TLS) և անվտանգության տեխնիկական միջոցներ Ձեր տվյալներն ապօրինի մուտքից կամ արտահոսքից պաշտպանելու համար։ Ձեր տվյալները ԵՐԲԵՔ չեն վաճառվում կամ փոխանցվում երրորդ անձանց։</p>

        <h2>5. Կոնտակտային Տվյալներ</h2>
        <p>Գաղտնիության քաղաքականության վերաբերյալ հարցերի կամ տվյալների հեռացման պահանջի դեպքում կարող եք կապ հաստատել աջակցման թիմի հետ՝ <strong style="color: var(--accent-amber);">{{ $contactEmail }}</strong></p>

        <a href="javascript:history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Վերադառնալ Մենյու
        </a>
    </div>
</body>
</html>
