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
    out(['ok' => true, 'mode' => 'pickup']);
}

$quick = $g('way') === 'objednavka';            // rychlá objednávka z patičky webu (vždy faktura)
$way = ($quick || $g('way') === 'faktura') ? 'faktura' : 'odber';
$sendV = $quick ? in_array($g('voucher'), ['1', 'true', 'ano'], true) : true;
$kind = $g('kind') === 'gold' ? 'gold' : 'standard';
$name = $g('name', 120);
$phone = $g('phone', 40);
$email = $g('email', 160);
$addr = $g('addr', 250);
$compRaw = $g('company', 160);
$for = $g('for', 80);
$msg = $g('msg', 120);

if ($quick && $for === '' && $name !== '') {
    $for = $name;                               // jméno zadané k poukazu
}
$bad = [];
if (!$quick && mb_strlen($name) < 2) $bad[] = 'name';
if (!preg_match('/^[+0-9 ()\-\/]{6,}$/', $phone)) $bad[] = 'phone';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $bad[] = 'email';
if (!$quick && $way === 'faktura' && mb_strlen($addr) < 5) $bad[] = 'addr';
if ($sendV && mb_strlen($for) < 2) $bad[] = 'for';
if (!$sendV) {
    $for = '';
    $msg = '';
}
if ($bad) {
    out(['ok' => false, 'error' => 'Zkontrolujte prosím vyplněné údaje.', 'fields' => $bad], 422);
}

/* ---- objednávka ---- */
$ico = '';
$company = $compRaw;
if (preg_match('/\b(\d{8})\b/', $compRaw, $m)) {
    $ico = $m[1];
    $company = trim(preg_replace('/\s*[,;\-–]?\s*(IČO?:?\s*)?' . $ico . '\s*[,;]?/iu', ' ', $compRaw));
}
$product = $cfg['products'][$kind];
if (!$sendV) {   // bez poukazu – na faktuře se neobjeví „Dárkový poukaz“
    $product['name'] = $product['name_visit'] ?? ('Black Box – ' . ($kind === 'gold' ? 'Gold Time' : 'Standard'));
}
$o = [
    'kind' => $kind,
    'product' => $product,
    'quick' => $quick,
    'send_voucher' => $sendV,
    'for' => $for,
    'msg' => $msg,
    'customer' => ['name' => $name, 'phone' => $phone, 'email' => $email, 'addr' => $addr, 'company' => $company, 'ico' => $ico],
];
$logo = $BASE . '/lib/assets/logo-email.png';

function log_order(string $dir, array $row): void
{
    $f = $dir . '/objednavky.csv';
    $new = !file_exists($f);
    $fh = @fopen($f, 'a');
    if (!$fh) return;
    if ($new) {
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['Datum', 'Způsob', 'Faktura/VS', 'Poukaz', 'Cena', 'Pro koho', 'Věnování', 'Jméno', 'Telefon', 'E-mail', 'Adresa', 'Firma', 'IČO', 'Stav'], ';');
    }
    fputcsv($fh, $row, ';');
    fclose($fh);
}

try {
    if ($way === 'odber') {
        // majiteli
        $m = bb_mailer($cfg);
        $m->addAddress($cfg['pickup_to']);
        $m->clearReplyTos();
        $m->addReplyTo($email, $name);
        $m->Subject = 'Objednávka poukazu – ' . $o['product']['name'] . ' – osobní odběr – ' . $name;
        $m->isHTML(true);
        $m->Body = bb_email_internal($o, 'odber');
        $m->AltBody = strip_tags(str_replace(['</tr>', '</td>'], ["\n", ' '], $m->Body));
        $m->send();
        // zákazníkovi
        try {
            $m = bb_mailer($cfg);
            $m->addAddress($email, $name);
            $m->Subject = 'Objednávka dárkového poukazu Black Box';
            $m->isHTML(true);
            $m->addEmbeddedImage($logo, 'bblogo', 'black-box.png');
            $m->Body = bb_email_pickup($cfg, $o);
            $m->AltBody = "Děkujeme za objednávku dárkového poukazu ({$o['product']['name']}, " . bb_money($o['product']['price']) . ").\nBrzy se ozveme a domluvíme vyzvednutí v Táboře.\nTermín návštěvy: {$cfg['phone']}\n\nBlack Box Luxury";
            $m->send();
        } catch (Throwable $e) {
            // potvrzení zákazníkovi není kritické – objednávka už dorazila
        }
        try {
            bb_save_order($DATA, $o + ['id' => 'OD-' . date('ymd-His') . '-' . random_int(10, 99), 'way' => 'odber', 'status' => 'nova', 'created' => time()]);
        } catch (Throwable $e) {   // e-maily už odešly – chybu jen zapíšeme
            @file_put_contents($DATA . '/chyby.log', date('c') . ' ulozeni ' . $e->getMessage() . "\n", FILE_APPEND);
        }
        log_order($DATA, [date('Y-m-d H:i'), 'osobní odběr', '', $o['product']['name'], $o['product']['price'], $for, $msg, $name, $phone, $email, '', $company, $ico, 'přijato']);
        out(['ok' => true, 'mode' => 'pickup']);
    }

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
        log_order($DATA, [date('Y-m-d H:i'), $quick ? 'objednávka (faktura)' : 'faktura', '', $o['product']['name'], $o['product']['price'], $for, $msg, $name, $phone, $email, $addr, $company, $ico, 'čeká na ruční fakturu']);
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

    log_order($DATA, [date('Y-m-d H:i'), $quick ? 'objednávka (faktura)' : 'faktura', $no, $o['product']['name'], $o['product']['price'], $for, $msg, $name, $phone, $email, $addr, $company, $ico, 'faktura odeslána']);
    out(['ok' => true, 'mode' => 'invoice', 'invoice' => $no]);
} catch (Throwable $e) {
    @file_put_contents($DATA . '/chyby.log', date('c') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
    out(['ok' => false, 'error' => 'Objednávku se nepodařilo odeslat.'], 500);
}
