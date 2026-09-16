<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Էլ․ փոստի հաստատում - QRMenu SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a 60%, #090d16);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .verify-card {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.15);
            border: 2px solid #f59e0b;
            color: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            margin: 0 auto 1.5rem;
        }
        h1 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.75rem; }
        p { font-size: 0.9rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1.5rem; }
        .btn-action {
            width: 100%;
            padding: 0.85rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.75rem;
        }
        .btn-demo { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
        .btn-secondary { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="icon-circle">
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>

        <h1>Հաստատեք Ձեր Էլ․ փոստի հասցեն</h1>
        <p>
            Հարգելի <strong>{{ Auth::user()?->name }}</strong>, շնորհակալություն գրանցվելու համար։ Էլ․ փոստի հաստատման հղումն ուղարկվել է <strong>{{ Auth::user()?->email }}</strong> հասցեին։
        </p>

        <form action="{{ route('verification.demo') }}" method="POST">
            @csrf
            <button type="submit" class="btn-action btn-demo">
                ⚡ Դեմո Ակնթարթային Հաստատում (Demo One-Click Verify)
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-action btn-secondary">
                <i class="fa-solid fa-right-from-bracket"></i> Դուրս գալ
            </button>
        </form>
    </div>
</body>
</html>
