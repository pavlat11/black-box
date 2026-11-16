<?php
/* Denní úloha: připomínka den před splatností a storno nezaplacené faktury v den splatnosti.
   Spouští se z cronu hostingu:  php /cesta/k/objednavky/cron.php  (jen z příkazové řádky) */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jen z příkazové řádky.');
}
date_default_timezone_set('Europe/Prague');
$BASE = __DIR__;
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';
$stat = bb_sweep_orders($cfg, $BASE . '/data');
echo date('c') . ' ' . json_encode($stat) . "\n";
