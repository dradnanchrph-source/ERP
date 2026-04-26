<?php
define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// ── Error handling ─────────────────────────────────────────────
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($severity, $msg, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    throw new ErrorException($msg, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    http_response_code(500);
    $msg  = htmlspecialchars($e->getMessage());
    $file = htmlspecialchars(str_replace(ROOT, '', $e->getFile()));
    $line = $e->getLine();
    error_log("ERP Error: $msg in $file:$line");
    // In production show friendly error; enable next line for debugging:
    // echo "<pre>$msg\n$file:$line\n" . $e->getTraceAsString() . "</pre>"; exit;
    echo "<!DOCTYPE html><html><head><title>Error</title>
    <style>body{font:15px sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#1e293b;border-radius:12px;padding:32px;max-width:520px;width:90%;border-left:4px solid #f87171}
    h2{color:#f87171;margin:0 0 12px}pre{color:#fbbf24;font-size:.85rem;white-space:pre-wrap;margin:12px 0}
    a{color:#818cf8}</style></head>
    <body><div class='box'>
    <h2>⚠ Application Error</h2>
    <pre>$msg\n\nFile: $file  Line: $line</pre>
    <a href='/'>← Back to home</a>
    </div></body></html>";
    exit;
});

// ── Create writable dirs ────────────────────────────────────────
foreach (['writable','writable/logs','writable/session','writable/cache','writable/tmp','public/uploads'] as $d) {
    if (!is_dir(ROOT . "/$d")) @mkdir(ROOT . "/$d", 0755, true);
}

// ── Session ─────────────────────────────────────────────────────
session_save_path(ROOT . '/writable/session');
session_name('erp_sess');
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// ── Load core ───────────────────────────────────────────────────
require_once APP . '/Core/DB.php';
require_once APP . '/Core/Auth.php';
require_once APP . '/Core/Controller.php';
require_once APP . '/Core/Router.php';
require_once APP . '/Helpers/helpers.php';

// ── Config & DB ─────────────────────────────────────────────────
$cfg = require APP . '/config.php';
date_default_timezone_set($cfg['timezone'] ?? 'Asia/Karachi');

// Connect DB (lazy — only when first query runs)
DB::config($cfg);

// ── Dispatch ────────────────────────────────────────────────────
$url = trim($_GET['url'] ?? '', '/');
Router::dispatch($url);
