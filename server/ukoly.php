<?php
/* Black Box – denní úloha přes adresu (pro hostingy, kde cron umí jen volat URL).
   Volání: ukoly.php?k=KLÍČ  – celou adresu najdete ve správě → E-maily. */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

if (!hash_equals(bb_cron_key($cfg), (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('Neplatný klíč.');
}
echo 'OK ' . json_encode(bb_cron_run($cfg, $BASE . '/data', 'url'));
