<?php
/* Black Box Luxury – aktuální ceník pro web (čte ho stránka, mění se ve správě) */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('X-Robots-Tag: noindex');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

echo json_encode(bb_prices($cfg), JSON_UNESCAPED_UNICODE);
