<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QRMenu SaaS Platform</title>
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
            padding: 1.5rem;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-header i { font-size: 2.5rem; color: #f59e0b; margin-bottom: 0.5rem; }
        .brand-header h1 { font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; background: linear-gradient(135deg, #f59e0b, #ef4444); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.5rem; }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #000;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
        .demo-accounts {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .demo-title { font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 0.75rem; text-align: center; }
        .demo-btn {
            width: 100%;
            padding: 0.6rem 0.85rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #cbd5e1;
            font-size: 0.8rem;
            cursor: pointer;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }
        .demo-btn:hover { background: rgba(245, 158, 11, 0.15); border-color: #f59e0b; color: #fff; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <i class="fa-solid fa-qrcode"></i>
            <h1>QR Menu SaaS</h1>
            <p style="font-size: 0.85rem; color: #94a3b8;">Restaurant & Hotel Digital Menu Platform</p>
        </div>

        @if($errors->any())
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label><i class="fa-solid fa-envelope"></i> Email Address</label>
                <input type="email" name="email" id="emailInput" class="form-input" required placeholder="admin@qrmenu.local" value="owner@bistro.am">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" name="password" id="passwordInput" class="form-input" required placeholder="••••••••" value="password">
            </div>

            <button type="submit" class="btn-submit">Sign In to Dashboard</button>
        </form>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem;">
            <a href="{{ route('register.show') }}" style="color: #f59e0b; font-weight: 700; text-decoration: none;">
                <i class="fa-solid fa-user-plus"></i> Գրանցվել որպես Նոր Vendor (Self-Register)
            </a>
        </div>

        <div class="demo-accounts">
            <div class="demo-title">⚡ Quick Demo One-Click Login</div>
            <button class="demo-btn" onclick="fillCreds('owner@bistro.am', 'password')">
                <span><i class="fa-solid fa-store text-amber-400"></i> Vendor Owner (Bistro Yerevan)</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button class="demo-btn" onclick="fillCreds('admin@qrmenu.local', 'password')">
                <span><i class="fa-solid fa-user-shield text-red-400"></i> Super Admin Panel</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button class="demo-btn" onclick="fillCreds('manager@bistro.am', 'password')">
                <span><i class="fa-solid fa-user-gear text-blue-400"></i> Cascades Branch Manager</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
        function fillCreds(email, pass) {
            document.getElementById('emailInput').value = email;
            document.getElementById('passwordInput').value = pass;
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
