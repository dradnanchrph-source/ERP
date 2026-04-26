<?php
/**
 * ERP Configuration
 * Edit database credentials below
 */
return [
    // ── Database ─────────────────────────────────────────────────
    'db_host'     => '127.0.0.1',
    'db_name'     => 'airpharma_claud',
    'db_user'     => 'airpharma_claud',
    'db_pass'     => 'AirPharma2026',
    'db_charset'  => 'utf8mb4',

    // ── App ──────────────────────────────────────────────────────
    'app_name'    => 'AIRMan ERP',
    'app_url'     => 'https://smbenterprises.pk',
    'app_version' => '3.1.0',
    'timezone'    => 'Asia/Karachi',
    'currency'    => 'Rs.',
    'date_format' => 'd M Y',

    // ── Security ─────────────────────────────────────────────────
    'session_lifetime' => 7200,
    'app_key'          => 'base64:' . base64_encode(random_bytes(32)),
];
