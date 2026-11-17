<?php
/* Black Box – denní úloha přes adresu (pro hostingy, kde cron umí jen volat URL).
   Volání: ukoly.php?k=KLÍČ (adresa), nebo z cronu hostingu s prvním parametrem k=KLÍČ.
   Klíč najdete ve správě → E-maily. */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';

$key = (string) ($_GET['k'] ?? '');
if (PHP_SAPI === 'cli') {                       // cron hostingu: parametr přijde jako argument „k=KLÍČ“
    foreach (array_slice($argv ?? [], 1) as $arg) {
        if (strncmp($arg, 'k=', 2) === 0) {
            $key = substr($arg, 2);
        }
    }
}
if (!hash_equals(bb_cron_key($cfg), $key)) {
    http_response_code(403);
    exit('Neplatný klíč.');
}
echo 'OK ' . json_encode(bb_cron_run($cfg, $BASE . '/data', 'url'));
