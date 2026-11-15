<?php
/* Black Box Luxury – záznam návštěvy webu (vlastní statistika bez cookies, IP se neukládá) */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

$raw = file_get_contents('php://input', false, null, 0, 4096) ?: '';
$in = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_GET;
}
try {
    if ($db = bb_db()) {
        bb_stat_record($db, $in);
    }
} catch (Throwable $e) {
    // statistika nesmí nikdy rozbít web
}
http_response_code(204);
