<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * cPanel Setup, Diagnostics & Repair Tool for menu.elab.am
 *
 * Usage:
 * 1. Open https://menu.elab.am/cpanel-setup.php?key=elab2026 in your browser
 * 2. Check PHP Extensions, Upload Limits, and Auto-Fix Storage & Symlink
 * 3. Inspect recent Laravel errors if any
 * 4. Once done, delete this file for security!
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
    <title>cPanel Diagnostics & Repair - menu.elab.am</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0b1329; color: #f8fafc; padding: 24px 16px; margin: 0; }
        .container { max-width: 860px; margin: 0 auto; background: #17213c; border-radius: 14px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.06); }
        h1 { color: #38bdf8; margin-top: 0; font-size: 24px; display: flex; align-items: center; gap: 8px; }
        h3 { color: #94a3b8; font-size: 15px; margin-top: 24px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .box { background: #080d1a; border: 1px solid #1e293b; border-radius: 8px; padding: 14px; margin: 8px 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace; white-space: pre-wrap; font-size: 13px; color: #cbd5e1; line-height: 1.5; word-break: break-all; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 12px; }
        .badge-ok { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .badge-err { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge-warn { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .card { background: #0c1427; border: 1px solid #1e2d4d; border-radius: 8px; padding: 12px 16px; }
        .warning-box { background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.3); color: #fef08a; padding: 14px; border-radius: 8px; margin-top: 20px; font-size: 13px; line-height: 1.5; }
        .btn { display: inline-block; background: #38bdf8; color: #071329; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 15px; }
        .btn-outline { background: transparent; border: 1px solid #38bdf8; color: #38bdf8; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 cPanel Դիագնոստիկա և Կարգավորում</h1>
    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">menu.elab.am համակարգի, նկարների վերբեռնման (File Upload) և կարգավորումների ստուգում:</p>

    <!-- 1. PHP ENVIRONMENT & EXTENSIONS -->
    <h3>1. PHP Մոդուլներ և Լուսանկարների Վերբեռնում (PHP Extensions)</h3>
    <div class="grid-2">
        <?php
        $extensions = [
            'fileinfo' => 'MIME Type & Image Validation (Կարևոր է նկարների համար)',
            'gd' => 'PHP GD (Նկարների մշակում)',
            'pdo_mysql' => 'MySQL Database Driver',
            'mbstring' => 'Multibyte String (Հայերեն տեքստեր)',
            'openssl' => 'SSL / Encryption & AI API Requests',
            'curl' => 'cURL (AI մոդելների և արտաքին հարցումների համար)',
        ];

foreach ($extensions as $ext => $desc) {
    $isLoaded = extension_loaded($ext);
    echo '<div class="card">';
    echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">';
    echo '<strong>'.$ext.'</strong>';
    echo $isLoaded
        ? '<span class="status-badge badge-ok">Ակտիվ է (OK)</span>'
        : '<span class="status-badge badge-err">ԱՆՋԱՏՎԱԾ Է (Error)</span>';
    echo '</div>';
    echo '<small style="color:#64748b;font-size:11px;">'.$desc.'</small>';
    echo '</div>';
}
?>
    </div>

    <!-- 2. PHP UPLOAD LIMITS -->
    <h3>2. PHP Վերբեռնման Սահմանաչափեր (Upload Limits)</h3>
    <div class="grid-2">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span>upload_max_filesize:</span>
                <strong><?php echo ini_get('upload_max_filesize'); ?></strong>
            </div>
            <small style="color:#64748b;font-size:11px;">Խորհուրդ է տրվում առնվազն 16M կամ 32M</small>
        </div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span>post_max_size:</span>
                <strong><?php echo ini_get('post_max_size'); ?></strong>
            </div>
            <small style="color:#64748b;font-size:11px;">Պետք է լինի upload_max_filesize-ից մեծ կամ հավասար</small>
        </div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span>memory_limit:</span>
                <strong><?php echo ini_get('memory_limit'); ?></strong>
            </div>
            <small style="color:#64748b;font-size:11px;">Օպերատիվ հիշողության սահմանաչափ (օր. 256M)</small>
        </div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span>max_execution_time:</span>
                <strong><?php echo ini_get('max_execution_time'); ?> վրկ.</strong>
            </div>
            <small style="color:#64748b;font-size:11px;">Սկրիպտի առավելագույն աշխատաժամանակ</small>
        </div>
    </div>

    <!-- 3. STORAGE DIRECTORIES & PERMISSIONS AUTO-FIX -->
    <h3>3. Storage Թղթապանակներ և Իրավունքներ (Permissions Auto-Fix)</h3>
    <div class="box"><?php
$dirsToEnsure = [
    storage_path('app/public/products'),
    storage_path('app/public/branding'),
    storage_path('framework/views'),
    storage_path('framework/sessions'),
    storage_path('framework/cache'),
    storage_path('logs'),
    base_path('bootstrap/cache'),
];

foreach ($dirsToEnsure as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
        echo 'Ստեղծվեց թղթապանակ: '.str_replace(base_path().'/', '', $dir)." (0775)\n";
    } else {
        @chmod($dir, 0775);
        echo 'Թղթապանակն առկա է: '.str_replace(base_path().'/', '', $dir)." (OK)\n";
    }
}
?></div>

    <!-- 4. PUBLIC/STORAGE SYMLINK REPAIR -->
    <h3>4. Ֆայլերի Հանրային Կապ (Storage Symlink Repair)</h3>
    <div class="box"><?php
    $publicStorage = public_path('storage');
$targetStorage = storage_path('app/public');

if (is_link($publicStorage)) {
    $linkTarget = @readlink($publicStorage);
    // Check if link points to local mac path or non-existent path
    if (! file_exists($publicStorage) || str_contains($linkTarget, '/Users/')) {
        @unlink($publicStorage);
        echo "Հայտնաբերվել և հեռացվել է հին/կոտրված սիմվոլիկ կապը ({$linkTarget})\n";
    }
} elseif (is_dir($publicStorage) && ! is_link($publicStorage)) {
    echo "public/storage-ը սովորական թղթապանակ է (ոչ սիմլինկ): Ֆայլերը կպահպանվեն:\n";
}

if (! file_exists($publicStorage) && ! is_link($publicStorage)) {
    try {
        Artisan::call('storage:link');
        echo Artisan::output();
    } catch (Throwable $e) {
        // Fallback to relative symlink
        @symlink('../storage/app/public', $publicStorage);
        echo 'Ստեղծվեց symlink fallback-ի միջոցով: '.htmlspecialchars($e->getMessage())."\n";
    }
} else {
    echo "public/storage հանրային հասանելիությունն ակտիվ է (OK):\n";
}
?></div>

    <!-- 5. DATABASE MIGRATIONS -->
    <h3>5. Տվյալների Բազա (Migrations)</h3>
    <div class="box"><?php
    try {
        Artisan::call('migrate', ['--force' => true]);
        echo htmlspecialchars(Artisan::output());
    } catch (Throwable $e) {
        echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage());
    }
?></div>

    <!-- 6. OPTIMIZE CLEAR -->
    <h3>6. Քեշի Մաքրում (Cache Clear)</h3>
    <div class="box"><?php
    try {
        Artisan::call('optimize:clear');
        echo htmlspecialchars(Artisan::output());
    } catch (Throwable $e) {
        echo 'ՍԽԱԼ: '.htmlspecialchars($e->getMessage());
    }
?></div>

    <!-- 7. RECENT LOGS VIEWER -->
    <h3>7. Վերջին Սխալների Մատյան (storage/logs/laravel.log)</h3>
    <div class="box" style="max-height: 260px; overflow-y: auto;"><?php
    $logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    if (! empty($lastLines)) {
        echo htmlspecialchars(implode('', $lastLines));
    } else {
        echo 'Մատյանը դատարկ է (սխալներ չկան):';
    }
} else {
    echo 'storage/logs/laravel.log ֆայլը դեռ չի ստեղծվել (սխալներ չկան):';
}
?></div>

    <div class="warning-box">
        💡 <strong>ԻՆՉ ԱՆԵԼ, ԵԹԵ 500 ERROR-Ը ԿՐԿՆՎԻ.</strong><br>
        1. Եթե վերևում <code>fileinfo</code> մոդուլի դիմաց գրված է <span style="color:#f87171;">ԱՆՋԱՏՎԱԾ Է</span>, cPanel-ում բացեք <strong>Select PHP Version</strong> &rarr; <strong>Extensions</strong> և միացրեք <code>fileinfo</code> նշավանդակը:<br>
        2. cPanel-ում բացեք <strong>MultiPHP INI Editor</strong> և ստուգեք, որ <code>upload_max_filesize</code> և <code>post_max_size</code> լինեն առնվազն <strong>32M</strong>:<br>
        3. Աշխատանքն ավարտելուց հետո խնդրում ենք ջնջել <code>public/cpanel-setup.php</code> ֆայլը File Manager-ից:
    </div>

    <p style="text-align: center; margin-top: 24px;">
        <a href="/admin/menu" class="btn">Վերադառնալ Մենյուի Խմբագրմանը &rarr;</a>
    </p>
</div>
</body>
</html>
