<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * cPanel Setup & Migration Helper for menu.elab.am
 *
 * Usage:
 * 1. Configure your MySQL credentials in .env
 * 2. Open https://menu.elab.am/cpanel-setup.php?key=elab2026 in your browser
 * 3. Once setup is completed, DELETE this file for security!
 */
$securityKey = 'elab2026';

if (! isset($_GET['key']) || $_GET['key'] !== $securityKey) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Unauthorized</title></head><body style="font-family:sans-serif;padding:40px;text-align:center;">';
    echo '<h1 style="color:#e11d48;">403 Unauthorized</h1>';
    echo '<p>Մուտքն արգելված է։ Խնդրում ենք հարցման վերջում ավելացնել գաղտնաբառը՝ <code>?key='.htmlspecialchars($securityKey).'</code></p>';
    echo '</body></html>';
    exit;
}

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>cPanel Auto Setup - menu.elab.am</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; margin-top: 0; font-size: 24px; }
        .box { background: #090d16; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin: 15px 0; font-family: monospace; white-space: pre-wrap; font-size: 13px; color: #a5f3fc; }
        .success { color: #4ade80; font-weight: bold; }
        .warning { color: #fbbf24; background: rgba(251,191,36,0.1); padding: 12px; border-radius: 6px; margin-top: 20px; }
        .btn { display: inline-block; background: #38bdf8; color: #0f172a; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 cPanel Կարգավորում (menu.elab.am)</h1>
    <p>Գործարկվում են անհրաժեշտ հրամանները տվյալների բազան և համակարգը կարգավորելու համար...</p>

    <?php
        $migrationSuccess = false;
$seedSuccess = false;

// Always clear cached configs first so changes in .env take effect immediately
try {
    Artisan::call('optimize:clear');
} catch (Throwable $e) {
    // Ignore
}
?>

    <h3>1. Միգրացիաներ (Database Migrations)</h3>
    <div class="box"><?php
    try {
        Artisan::call('migrate', ['--force' => true]);
        $out = Artisan::output();
        echo htmlspecialchars($out);
        $migrationSuccess = true;
    } catch (Throwable $e) {
        echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage())."\n\n";
        echo "💡 ԻՆՉՊԵՍ ՈՒՂՂԵԼ:\n";
        echo "1. cPanel -> MySQL Databases բաժնում իջեք ներքև մինչև 'Add User To Database':\n";
        echo "2. User դաշտում ընտրեք օգտատիրոջը, Database դաշտում՝ բազան, սեղմեք 'Add':\n";
        echo "3. Նշեք 'ALL PRIVILEGES' (բոլոր արտոնությունները) և սեղմեք 'Make Changes':\n";
        echo '4. Եթե խնդիրը շարունակվի, .env ֆայլում DB_HOST=127.0.0.1-ը փոխեք DB_HOST=localhost-ի:';
    }
?></div>

    <h3>2. Սկզբնական Տվյալներ (Database Seeders)</h3>
    <div class="box"><?php
    if ($migrationSuccess) {
        try {
            Artisan::call('db:seed', ['--force' => true]);
            echo htmlspecialchars(Artisan::output());
            $seedSuccess = true;
        } catch (Throwable $e) {
            echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage());
        }
    } else {
        echo 'Միգրացիաների սխալի պատճառով սկզբնական տվյալների գեներացիան դադարեցվել է:';
    }
?></div>

    <h3>3. Ֆայլերի Կապ (Storage Symlink)</h3>
    <div class="box"><?php
    try {
        if (! file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
            echo htmlspecialchars(Artisan::output());
        } else {
            echo 'Ֆայլերի կապը (public/storage) արդեն իսկ ստեղծված է (OK):';
        }
    } catch (Throwable $e) {
        echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage());
    }
?></div>

    <h3>4. Քեշի Օպտիմիզացիա (Cache Optimization)</h3>
    <div class="box"><?php
    if ($migrationSuccess && $seedSuccess) {
        try {
            Artisan::call('optimize');
            echo htmlspecialchars(Artisan::output());
        } catch (Throwable $e) {
            echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage());
        }
    } else {
        echo 'Քեշավորումը չի գործարկվել, որպեսզի սխալ կարգավորումները չքեշավորվեն: Շտկեք բազայի հասանելիությունը և թարմացրեք էջը:';
    }
?></div>

    <div class="warning">
        ⚠️ <strong>ՈՒՇԱԴՐՈՒԹՅՈՒՆ:</strong> Աշխատանքն ավարտելուց հետո խնդրում ենք ջնջել <code>public/cpanel-setup.php</code> ֆայլը File Manager-ից անվտանգության նկատառումներով։
    </div>

    <p style="text-align: center;">
        <a href="/" class="btn">Անցնել Գլխավոր Էջ &rarr;</a>
    </p>
</div>
</body>
</html>
