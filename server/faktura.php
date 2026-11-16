<?php
/* Black Box – stažení faktury zákazníkem. Přístup jen s tokenem uloženým u objednávky. */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';
$DATA = $BASE . '/data';

$no = (string) ($_GET['c'] ?? '');
$t = (string) ($_GET['t'] ?? '');
$o = preg_match('/^[A-Za-z0-9_.\-]{1,40}$/', $no) ? bb_load_order($DATA, $no) : null;

if (!$o || $t === '' || empty($o['token']) || !hash_equals((string) $o['token'], $t)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Faktura nebyla nalezena.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="faktura-' . $no . '.pdf"');
echo bb_invoice_bytes($cfg, $DATA, $o);
