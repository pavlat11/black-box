<?php
/* Black Box Luxury – záznam návštěvy webu (vlastní statistika bez cookies, IP se neukládá) */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

// přihlášený do správy = vlastní návštěva, nepočítat
if (!empty($_COOKIE['bbsprava'])) {
    session_name('bbsprava');
    @session_start(['read_and_close' => true]);
    if (!empty($_SESSION['bb_admin'])) {
        try { if ($db = bb_db()) bb_stat_purge_me($db); } catch (Throwable $e) {}
        http_response_code(204);
        exit;
    }
}

$raw = file_get_contents('php://input', false, null, 0, 4096) ?: '';
$in = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_GET;
}
// odkaz ?nepocitat / ?pocitat – vyřazení tohoto prohlížeče (spravuje se ve správě)
if (in_array($in['e'] ?? '', ['optout', 'optin'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if ($in['e'] === 'optout') {
            bb_stat_optout('odkaz ?nepocitat');
            if ($db = bb_db()) bb_stat_purge_me($db);
        } else {
            bb_stat_optin();
        }
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false]);
    }
    exit;
}
try {
    if ($db = bb_db()) {
        bb_stat_record($db, $in);
    }
} catch (Throwable $e) {
    // statistika nesmí nikdy rozbít web
}
http_response_code(204);
