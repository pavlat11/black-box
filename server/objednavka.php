<?php
/* Black Box Luxury – příjem objednávky dárkového poukazu (volá ho formulář na webu) */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');

function out(array $a, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    out(['ok' => false, 'error' => 'Použijte formulář na webu.'], 405);
}

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';
$DATA = $BASE . '/data';
if (!is_dir($DATA . '/faktury')) {
    @mkdir($DATA . '/faktury', 0755, true);
}

/* ---- vstup ---- */
$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) {
    $in = $_POST;
}
$g = function (string $k, int $max = 200) use ($in): string {
    $v = isset($in[$k]) && is_scalar($in[$k]) ? (string) $in[$k] : '';
    $v = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '');
    return mb_substr($v, 0, $max);
};

if ($g('web') !== '') {                       // past na roboty (skryté pole)
    out(['ok' => true, 'mode' => 'invoice']);
}

/*
 * Platí se vždy fakturou (převodem). Osobní odběr už není.
 * Poukaz je univerzální (na doručitele) – bez jména a věnování.
 * Doručení poukazu: 'email' (PDF po zaplacení) nebo 'posta' (tištěný poukaz, číslo přidělí správa).
 */
$quick = $g('way') === 'objednavka';            // rychlá objednávka (Rezervace → Objednávka)
$sendV = $quick ? in_array($g('voucher'), ['1', 'true', 'ano'], true) : true;
$delivery = $g('delivery') === 'posta' ? 'posta' : 'email';
if (!$sendV) {
    $delivery = '';
}
$kind = $g('kind') === 'gold' ? 'gold' : 'standard';
$name = $g('name', 120);
$phone = $g('phone', 40);
$email = $g('email', 160);
$street = $g('street', 160);
$zip = preg_replace('/\s+/', '', $g('zip', 10)) ?? '';
$city = $g('city', 80);
$compRaw = $g('company', 160);
if ($street === '' && $g('addr', 250) !== '') {   // starší formulář posílal adresu v jednom poli
    $street = $g('addr', 250);
}
$addr = trim($street . ($zip !== '' || $city !== '' ? ', ' . trim(($zip !== '' ? substr($zip, 0, 3) . ' ' . substr($zip, 3) : '') . ' ' . $city) : ''), ' ,');

$bad = [];
if (!$quick && mb_strlen($name) < 2) $bad[] = 'name';
if (!preg_match('/^[+0-9 ()\-\/]{6,}$/', $phone)) $bad[] = 'phone';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $bad[] = 'email';
if ($delivery === 'posta') {
    if (mb_strlen($name) < 2) $bad[] = 'name';
    if (mb_strlen($street) < 3) $bad[] = 'street';
    if (!preg_match('/^\d{5}$/', $zip)) $bad[] = 'zip';
    if (mb_strlen($city) < 2) $bad[] = 'city';
}
if ($bad) {
    out(['ok' => false, 'error' => in_array('zip', $bad, true) && count($bad) === 1 ? 'PSČ má 5 číslic.' : 'Zkontrolujte prosím vyplněné údaje.', 'fields' => array_values(array_unique($bad))], 422);
}

/* ---- objednávka ---- */
$ico = '';
$company = $compRaw;
if (preg_match('/\b(\d{8})\b/', $compRaw, $m)) {
    $ico = $m[1];
    $company = trim(preg_replace('/\s*[,;\-–]?\s*(IČO?:?\s*)?' . $ico . '\s*[,;]?/iu', ' ', $compRaw));
}
$product = bb_products($cfg)[$kind];
if (!$sendV) {   // bez poukazu – na faktuře se neobjeví „Dárkový poukaz“
    $product['name'] = $product['name_visit'] ?? ('Black Box – ' . ($kind === 'gold' ? 'Gold Time' : 'Standard'));
}
$o = [
    'kind' => $kind,
    'product' => $product,
    'quick' => $quick,
    'send_voucher' => $sendV,
    'delivery' => $delivery,
    'for' => '',
    'msg' => '',
    'customer' => ['name' => $name, 'phone' => $phone, 'email' => $email, 'addr' => $addr, 'company' => $company, 'ico' => $ico],
];
if ($delivery === 'posta') {
    $o['ship'] = ['name' => $name, 'street' => $street, 'zip' => $zip, 'city' => $city];
}

function log_order(string $dir, array $row): void
{
    $f = $dir . '/objednavky.csv';
    $new = !file_exists($f);
    $fh = @fopen($f, 'a');
    if (!$fh) return;
    if ($new) {
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['Datum', 'Způsob', 'Faktura/VS', 'Poukaz', 'Cena', 'Doručení poukazu', '', 'Jméno', 'Telefon', 'E-mail', 'Adresa', 'Firma', 'IČO', 'Stav'], ';');
    }
    fputcsv($fh, $row, ';');
    fclose($fh);
}

try {
    /* ---- faktura ---- */
    $iban = bb_iban((string) $cfg['bank_account']);
    if (!$iban) {
        // číslo účtu není vyplněné → fakturu nevystavíme automaticky, jen pošleme objednávku majiteli
        $m = bb_mailer($cfg);
        $m->addAddress($cfg['invoice_copy_to']);
        $m->clearReplyTos();
        $m->addReplyTo($email, $name);
        $m->Subject = ($sendV ? 'Objednávka poukazu' : 'Objednávka') . ' – FAKTURU POŠLETE RUČNĚ – ' . ($name !== '' ? $name : $email);
        $m->isHTML(true);
        $m->Body = '<p style="font-family:Arial;color:#b00020"><b>Faktura nebyla vystavena automaticky – v objednavky/config.php chybí číslo účtu.</b></p>' . bb_email_internal($o, 'faktura');
        $m->AltBody = strip_tags($m->Body);
        $m->send();
        try {
            bb_save_order($DATA, $o + ['id' => 'FR-' . date('ymd-His') . '-' . random_int(10, 99), 'way' => 'faktura', 'status' => 'bez_faktury', 'created' => time()]);
        } catch (Throwable $e) {   // e-maily už odešly – chybu jen zapíšeme
            @file_put_contents($DATA . '/chyby.log', date('c') . ' ulozeni ' . $e->getMessage() . "\n", FILE_APPEND);
        }
        log_order($DATA, [date('Y-m-d H:i'), $quick ? 'objednávka (faktura)' : 'faktura', '', $o['product']['name'], $o['product']['price'], bb_delivery_label($o), '', $name, $phone, $email, $addr, $company, $ico, 'čeká na ruční fakturu']);
        out(['ok' => true, 'mode' => 'manual']);
    }

    $o = bb_issue_invoice($cfg, $DATA, $o, $iban);
    $no = $o['no'];
    bb_send_invoice_customer($cfg, $DATA, $o);
    try {
        bb_send_invoice_copy($cfg, $DATA, $o, 'Nová faktura');
    } catch (Throwable $e) {
    }
    $o['sent_at'] = time();
    bb_save_order($DATA, $o);

    log_order($DATA, [date('Y-m-d H:i'), $quick ? 'objednávka (faktura)' : 'faktura', $no, $o['product']['name'], $o['product']['price'], bb_delivery_label($o), '', $name, $phone, $email, $addr, $company, $ico, 'faktura odeslána']);
    out(['ok' => true, 'mode' => 'invoice', 'invoice' => $no]);
} catch (Throwable $e) {
    @file_put_contents($DATA . '/chyby.log', date('c') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
    out(['ok' => false, 'error' => 'Objednávku se nepodařilo odeslat.'], 500);
}
