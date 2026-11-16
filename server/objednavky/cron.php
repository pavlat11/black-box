<?php
/* Denní úloha: připomínka den před splatností a storno nezaplacené faktury po splatnosti.
   Spouští se z cronu hostingu:  php /cesta/k/objednavky/cron.php  (jen z příkazové řádky).
   Když hosting umí jen volání adresy, použijte ukoly.php?k=… (adresa je ve správě → E-maily). */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jen z příkazové řádky.');
}
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');
$BASE = __DIR__;
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';
echo date('c') . ' ' . json_encode(bb_cron_run($cfg, $BASE . '/data', 'cron')) . "\n";
