<?php
/* Black Box Luxury – data pro web: aktuální ceník a oznámení (mění se ve správě) */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-Robots-Tag: noindex');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

$out = bb_prices($cfg);
$out['oznameni'] = bb_notice_public();
echo json_encode($out, JSON_UNESCAPED_UNICODE);
