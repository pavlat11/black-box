<?php
/* Black Box Luxury – faktura (PDF), QR platba a e-maily pro objednávky poukazů */

require_once __DIR__ . '/qrcode.php';
require_once __DIR__ . '/tfpdf/tfpdf.php';
require_once __DIR__ . '/tfpdf/font/unifont/ttfonts.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/* ---------- pomocné ---------- */

function bb_money(float $n): string
{
    return number_format($n, 0, ',', "\u{00A0}") . "\u{00A0}Kč";
}

function bb_date(int $ts): string
{
    return date('j. n. Y', $ts);
}

function bb_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** '19-123456789/0800' -> ['prefix','number','bank'] nebo null */
function bb_parse_account(string $acc): ?array
{
    $acc = preg_replace('/\s+/', '', $acc);
    if (!preg_match('~^(?:(\d{1,6})-)?(\d{2,10})/(\d{4})$~', $acc, $m)) {
        return null;
    }
    return ['prefix' => $m[1] ?: '', 'number' => $m[2], 'bank' => $m[3]];
}

function bb_iban(string $acc): ?string
{
    $p = bb_parse_account($acc);
    if (!$p) {
        return null;
    }
    $bban = $p['bank'] . str_pad($p['prefix'], 6, '0', STR_PAD_LEFT) . str_pad($p['number'], 10, '0', STR_PAD_LEFT);
    $num = $bban . '1235' . '00';               // C=12, Z=35
    $mod = 0;
    foreach (str_split($num, 7) as $chunk) {
        $mod = (int) (($mod . $chunk) % 97);
    }
    $check = str_pad((string) (98 - $mod), 2, '0', STR_PAD_LEFT);
    return 'CZ' . $check . $bban;
}

function bb_iban_pretty(string $iban): string
{
    return trim(chunk_split($iban, 4, ' '));
}

/** Řetězec pro českou QR Platbu (SPAYD) */
function bb_spayd(string $iban, float $amount, string $vs, string $msg): string
{
    $msg = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $msg) ?: '');
    $msg = trim(preg_replace('/[^A-Z0-9 .,\-\/]/', '', $msg));
    $msg = substr($msg, 0, 60);
    return 'SPD*1.0*ACC:' . $iban . '*AM:' . number_format($amount, 2, '.', '') . '*CC:CZK*X-VS:' . $vs . '*MSG:' . $msg;
}

function bb_qr(string $data): QRCode
{
    return QRCode::getMinimumQRCode($data, QR_ERROR_CORRECT_LEVEL_M);
}

/** QR jako PNG (pro e-mail). Vyžaduje GD, jinak vrací null. */
function bb_qr_png(string $data, int $scale = 8): ?string
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $qr = bb_qr($data);
    $n = $qr->getModuleCount();
    $q = 4;                                         // tichá zóna
    $size = ($n + 2 * $q) * $scale;
    $im = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($im, 255, 255, 255);
    $dark = imagecolorallocate($im, 10, 8, 32);
    imagefill($im, 0, 0, $white);
    for ($r = 0; $r < $n; $r++) {
        for ($c = 0; $c < $n; $c++) {
            if ($qr->isDark($r, $c)) {
                imagefilledrectangle($im, ($c + $q) * $scale, ($r + $q) * $scale, ($c + $q + 1) * $scale - 1, ($r + $q + 1) * $scale - 1, $dark);
            }
        }
    }
    ob_start();
    imagepng($im, null, 9);
    imagedestroy($im);
    return ob_get_clean();
}

/** Pořadové číslo faktury: prefix + rok + 4 číslice (zamyká soubor, aby se čísla neopakovala) */
function bb_next_invoice_no(array $cfg, string $dataDir): string
{
    $year = date('Y');
    $file = $dataDir . '/cislovani-faktur.json';
    $fh = fopen($file, 'c+');
    if (!$fh) {
        throw new RuntimeException('Nelze zapisovat do složky data – zkontrolujte práva (chmod 755/777).');
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $st = $raw ? (json_decode($raw, true) ?: []) : [];
    $seq = (int) ($st[$year] ?? 0) + 1;
    $st[$year] = $seq;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($st));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return ($cfg['invoice_prefix'] ?? '') . $year . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/* ---------- PDF faktura ---------- */

class BBInvoicePdf extends tFPDF
{
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        $op = $style == 'F' ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }

    function Label($x, $y, $text)
    {
        $this->SetFont('Manrope', 'B', 7);
        $this->SetTextColor(124, 92, 230);
        $this->SetXY($x, $y);
        $this->Cell(0, 4, mb_strtoupper($text, 'UTF-8'));
    }
}

/**
 * $o = order: no, vs, issued(ts), due(ts), product[name,detail,price], for, msg,
 *             customer[name,addr,company,email,phone], account, iban
 */
function bb_invoice_pdf(array $cfg, array $o): string
{
    $clean = fn($t) => trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\x{20}-\x{7E}\x{A0}-\x{17F}\x{2013}-\x{201E}\x{2022}\x{2026}]/u', '', (string) $t)));
    $o['for'] = $clean($o['for']);
    $o['msg'] = $clean($o['msg']);
    foreach ($o['customer'] as $k => $v) {
        $o['customer'][$k] = $clean($v);
    }
    $s = $cfg['seller'];
    $pdf = new BBInvoicePdf('P', 'mm', 'A4');
    $pdf->SetTitle($cfg['invoice_title'] . ' ' . $o['no'], true);
    $pdf->SetAuthor($s['name'], true);
    $pdf->SetCreator('black-box-luxury.cz', true);
    $pdf->AddFont('Manrope', '', 'Manrope-Regular.ttf', true);
    $pdf->AddFont('Manrope', 'B', 'Manrope-Bold.ttf', true);
    $pdf->AddFont('Manrope', 'I', 'Manrope-SemiBold.ttf', true);   // "I" = semibold
    $pdf->AddFont('Unbounded', '', 'Unbounded-Regular.ttf', true);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(18, 18, 18);
    $pdf->AddPage();
    $W = 210;
    $L = 18;
    $R = $W - 18;

    /* hlavička */
    $pdf->SetFillColor(6, 5, 26);
    $pdf->Rect(0, 0, $W, 50, 'F');
    $pdf->SetFillColor(138, 77, 255);
    $pdf->Rect(0, 50, $W, 1.2, 'F');
    $pdf->Image(__DIR__ . '/assets/logo-pdf.jpg', $L - 3, 5, 40, 40);

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Unbounded', '', 22);
    $pdf->SetXY(80, 12);
    $pdf->Cell($R - 80, 10, mb_strtoupper($cfg['invoice_title'], 'UTF-8'), 0, 2, 'R');
    $pdf->SetFont('Manrope', 'I', 11);
    $pdf->SetTextColor(185, 150, 255);
    $pdf->Cell($R - 80, 7, 'č. ' . $o['no'], 0, 2, 'R');
    $pdf->SetFont('Manrope', '', 8.5);
    $pdf->SetTextColor(190, 186, 220);
    $pdf->Cell($R - 80, 5, 'Dárkový poukaz · privátní relaxační prostor · Tábor', 0, 2, 'R');

    /* dodavatel / odběratel */
    $y = 60;
    $pdf->Label($L, $y, 'Dodavatel');
    $pdf->Label(112, $y, 'Odběratel');
    $pdf->SetTextColor(20, 18, 40);
    $pdf->SetFont('Manrope', 'B', 11);
    $pdf->SetXY($L, $y + 5);
    $pdf->Cell(88, 6, $s['name']);
    $pdf->SetXY(112, $y + 5);
    $pdf->MultiCell($R - 112, 6, $o['customer']['company'] ?: $o['customer']['name']);
    $yAfterName = $pdf->GetY();

    $pdf->SetFont('Manrope', '', 9);
    $pdf->SetTextColor(70, 66, 96);
    $lines = [$s['street'], $s['city'], 'IČ: ' . $s['ico']];
    if (!empty($s['dic'])) {
        $lines[] = 'DIČ: ' . $s['dic'];
    }
    if (!empty($cfg['vat_note'])) {
        $lines[] = $cfg['vat_note'];
    }
    if (!empty($s['premises'])) {
        $lines[] = $s['premises'];
    }
    $pdf->SetXY($L, $y + 12);
    foreach ($lines as $ln) {
        $pdf->SetX($L);
        $pdf->Cell(88, 4.8, $ln, 0, 2);
    }
    $yL = $pdf->GetY();

    $c = $o['customer'];
    $cl = [];
    if ($c['company']) {
        $cl[] = $c['name'];
    }
    $cl[] = $c['addr'];
    if ($c['ico']) {
        $cl[] = 'IČ: ' . $c['ico'];
    }
    $cl[] = $c['email'];
    $cl[] = $c['phone'];
    $pdf->SetXY(112, max($y + 12, $yAfterName + 1));
    foreach ($cl as $ln) {
        $pdf->SetX(112);
        $pdf->MultiCell($R - 112, 4.8, $ln, 0, 'L');
    }
    $y = max($yL, $pdf->GetY()) + 6;

    /* údaje o platbě – 4 boxy */
    $boxes = [
        ['Datum vystavení', bb_date($o['issued'])],
        ['Datum splatnosti', bb_date($o['due'])],
        ['Variabilní symbol', $o['vs']],
        ['Způsob úhrady', 'Převodem'],
    ];
    $bw = ($R - $L - 3 * 4) / 4;
    foreach ($boxes as $i => $b) {
        $bx = $L + $i * ($bw + 4);
        $pdf->SetFillColor(244, 241, 255);
        $pdf->RoundedRect($bx, $y, $bw, 16, 2.5, 'F');
        $pdf->Label($bx + 4, $y + 3, $b[0]);
        $pdf->SetFont('Manrope', 'B', 10.5);
        $pdf->SetTextColor(20, 18, 40);
        $pdf->SetXY($bx + 4, $y + 8);
        $pdf->Cell($bw - 8, 5, $b[1]);
    }
    $y += 23;

    /* položky */
    $pdf->SetDrawColor(225, 220, 245);
    $pdf->SetLineWidth(0.3);
    $pdf->Label($L, $y, 'Položka');
    $pdf->SetXY(120, $y);
    $pdf->Cell(25, 4, 'MNOŽSTVÍ', 0, 0, 'C');
    $pdf->SetXY(145, $y);
    $pdf->Cell($R - 145, 4, 'CENA', 0, 0, 'R');
    $y += 6;
    $pdf->Line($L, $y, $R, $y);
    $y += 4;
    $p = $o['product'];
    $pdf->SetTextColor(20, 18, 40);
    $pdf->SetFont('Manrope', 'B', 11);
    $pdf->SetXY($L, $y);
    $pdf->Cell(100, 6, $p['name']);
    $pdf->SetFont('Manrope', '', 10);
    $pdf->SetXY(120, $y);
    $pdf->Cell(25, 6, '1 ks', 0, 0, 'C');
    $pdf->SetFont('Manrope', 'B', 11);
    $pdf->SetXY(145, $y);
    $pdf->Cell($R - 145, 6, bb_money($p['price']), 0, 0, 'R');
    $pdf->SetFont('Manrope', '', 9);
    $pdf->SetTextColor(90, 86, 116);
    $pdf->SetXY($L, $y + 6.5);
    $pdf->Cell(100, 5, $p['detail']);
    $yy = $y + 11.5;
    if ($o['for'] !== '') {
        $pdf->SetXY($L, $yy);
        $pdf->Cell(100, 5, 'Poukaz pro: ' . $o['for']);
        $yy += 5;
    }
    if ($o['msg'] !== '') {
        $pdf->SetXY($L, $yy);
        $pdf->MultiCell(100, 5, 'Věnování: „' . $o['msg'] . '“');
        $yy = $pdf->GetY();
    }
    $y = $yy + 4;
    $pdf->Line($L, $y, $R, $y);
    $y += 6;

    /* celkem */
    $pdf->SetFillColor(6, 5, 26);
    $pdf->RoundedRect(110, $y, $R - 110, 20, 3, 'F');
    $pdf->SetFillColor(138, 77, 255);
    $pdf->Rect(110 + 3, $y + 19, $R - 110 - 6, 1, 'F');
    $pdf->SetTextColor(185, 150, 255);
    $pdf->SetFont('Manrope', 'B', 8);
    $pdf->SetXY(116, $y + 4);
    $pdf->Cell(40, 4, 'K ÚHRADĚ');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Unbounded', '', 17);
    $pdf->SetXY(116, $y + 8.5);
    $pdf->Cell($R - 116 - 6, 9, bb_money($p['price']), 0, 0, 'R');
    $y += 27;

    /* platba + QR */
    $pdf->SetFillColor(244, 241, 255);
    $pdf->RoundedRect($L, $y, $R - $L, 50, 3, 'F');
    $pdf->Label($L + 7, $y + 6, 'Platební údaje');
    $rows = [
        ['Číslo účtu', $o['account'] . (!empty($cfg['bank_name']) ? '  (' . $cfg['bank_name'] . ')' : '')],
        ['IBAN', bb_iban_pretty($o['iban'])],
        ['Variabilní symbol', $o['vs']],
        ['Částka', bb_money($p['price'])],
        ['Splatnost', bb_date($o['due'])],
    ];
    $ry = $y + 13;
    foreach ($rows as $r) {
        $pdf->SetXY($L + 7, $ry);
        $pdf->SetFont('Manrope', '', 9.5);
        $pdf->SetTextColor(90, 86, 116);
        $pdf->Cell(36, 6, $r[0]);
        $pdf->SetFont('Manrope', 'B', 10.5);
        $pdf->SetTextColor(20, 18, 40);
        $pdf->Cell(80, 6, $r[1]);
        $ry += 7;
    }
    /* QR */
    $qr = bb_qr($o['spayd']);
    $n = $qr->getModuleCount();
    $qs = 40;
    $qx = $R - 7 - $qs;
    $qy = $y + 6;
    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect($qx - 2, $qy - 2, $qs + 4, $qs + 4, 2, 'F');
    $m = $qs / $n;
    $pdf->SetFillColor(10, 8, 32);
    for ($r = 0; $r < $n; $r++) {
        for ($cc = 0; $cc < $n; $cc++) {
            if ($qr->isDark($r, $cc)) {
                $pdf->Rect($qx + $cc * $m, $qy + $r * $m, $m + 0.02, $m + 0.02, 'F');
            }
        }
    }
    $pdf->SetFont('Manrope', 'B', 7);
    $pdf->SetTextColor(124, 92, 230);
    $pdf->SetXY($qx - 2, $qy + $qs + 2.5);
    $pdf->Cell($qs + 4, 3, 'QR PLATBA', 0, 0, 'C');
    $y += 58;

    /* co bude dál */
    $pdf->Label($L, $y, 'Co bude dál');
    $steps = [
        'Zaplaťte prosím fakturu převodem – nejrychleji přes QR kód v bankovní aplikaci.',
        'Po připsání platby vám e-mailem pošleme potvrzení o platbě a dárkový poukaz.',
        'Termín návštěvy si pak domluvíte telefonicky na ' . $cfg['phone'] . '.',
    ];
    $sy = $y + 6;
    foreach ($steps as $i => $t) {
        $pdf->SetFillColor(138, 77, 255);
        $pdf->RoundedRect($L, $sy, 6, 6, 3, 'F');
        $pdf->SetFont('Manrope', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($L, $sy + 0.6);
        $pdf->Cell(6, 5, (string) ($i + 1), 0, 0, 'C');
        $pdf->SetFont('Manrope', '', 9.5);
        $pdf->SetTextColor(50, 46, 76);
        $pdf->SetXY($L + 9, $sy + 0.5);
        $pdf->Cell(160, 5, $t);
        $sy += 8;
    }

    /* patička */
    $fy = 297 - 17;
    $pdf->SetDrawColor(225, 220, 245);
    $pdf->Line($L, $fy, $R, $fy);
    $pdf->SetFont('Manrope', '', 7.5);
    $pdf->SetTextColor(120, 116, 146);
    $foot = $s['name'] . ' · ' . $s['street'] . ', ' . $s['city'] . ' · IČ ' . $s['ico'] . (!empty($s['dic']) ? ' · DIČ ' . $s['dic'] : '');
    $pdf->SetXY($L, $fy + 3);
    $pdf->Cell($R - $L, 4, $foot, 0, 2, 'C');
    if (!empty($s['register'])) {
        $pdf->Cell($R - $L, 4, $s['register'], 0, 2, 'C');
    }
    $pdf->SetFont('Manrope', 'B', 7.5);
    $pdf->SetTextColor(124, 92, 230);
    $pdf->Cell($R - $L, 4, $cfg['phone'] . '  ·  ' . ($cfg['contact_email'] ?? $cfg['reply_to']) . '  ·  ' . preg_replace('~^https?://~', '', $cfg['web']), 0, 2, 'C');

    return $pdf->Output('S');
}

/* ---------- HTML e-maily ---------- */

function bb_email_layout(string $preheader, string $inner, array $cfg, bool $gold): string
{
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $web = bb_h($cfg['web']);
    $webShort = bb_h(preg_replace('~^https?://~', '', $cfg['web']));
    $tel = bb_h($cfg['phone']);
    $telHref = 'tel:' . preg_replace('/\s+/', '', $cfg['phone']);
    $mail = bb_h($cfg['contact_email'] ?? $cfg['reply_to']);
    $s = $cfg['seller'];
    return '<!doctype html>
<html lang="cs" xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="x-apple-disable-message-reformatting"><meta name="color-scheme" content="dark light"><meta name="supported-color-schemes" content="dark light"><title>Black Box Luxury</title>
<style>
body{margin:0;padding:0;background:#06051a}
a{color:' . $acc . '}
@media (max-width:620px){.wrap{width:100%!important}.px{padding-left:22px!important;padding-right:22px!important}.h1{font-size:26px!important;line-height:32px!important}.col{display:block!important;width:100%!important}.qrcell{padding:18px 0 0!important;text-align:left!important}}
</style></head>
<body style="margin:0;padding:0;background:#06051a;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#06051a;">' . bb_h($preheader) . '&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#06051a" style="background:#06051a;background-image:radial-gradient(ellipse at 50% 0%,#24125e 0%,#06051a 62%);">
<tr><td align="center" style="padding:28px 12px 40px;">
<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
<tr><td align="center" style="padding:6px 0 22px;"><img src="cid:bblogo" width="132" height="132" alt="Black Box – relaxační prostor" style="display:block;width:132px;height:132px;border:0;border-radius:22px;"></td></tr>
<tr><td bgcolor="#0e0b2c" style="background:#0e0b2c;border:1px solid #2a2160;border-radius:22px;overflow:hidden;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td height="3" style="height:3px;line-height:3px;font-size:0;background:' . ($gold ? '#f0cf85' : '#8a4dff') . ';background-image:linear-gradient(90deg,#6f3bff,' . $acc . ',#6f3bff);">&nbsp;</td></tr></table>
' . $inner . '
</td></tr>
<tr><td class="px" align="center" style="padding:30px 30px 0;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;line-height:21px;color:#8f89b8;">
<a href="' . $telHref . '" style="color:#e9e6ff;text-decoration:none;font-weight:700;">' . $tel . '</a> &nbsp;·&nbsp; <a href="mailto:' . $mail . '" style="color:#e9e6ff;text-decoration:none;">' . $mail . '</a> &nbsp;·&nbsp; <a href="' . $web . '" style="color:#e9e6ff;text-decoration:none;">' . $webShort . '</a><br>
Black Box · Purkyňova 2961, 390 02 Tábor (Od Slunce)<br>
<span style="color:#5f5a86;">' . bb_h($s['name']) . ' · ' . bb_h($s['street']) . ', ' . bb_h($s['city']) . ' · IČ ' . bb_h($s['ico']) . '</span>
</td></tr>
</table>
</td></tr></table>
</body></html>';
}

function bb_email_summary(array $o, bool $gold): string
{
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $rows = '';
    if ($o['for'] !== '') {
        $rows .= '<div style="margin-top:6px;">Pro: <b style="color:#ffffff;">' . bb_h($o['for']) . '</b></div>';
    }
    if ($o['msg'] !== '') {
        $rows .= '<div style="margin-top:4px;font-style:italic;">„' . bb_h($o['msg']) . '“</div>';
    }
    return '<tr><td class="px" style="padding:0 40px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#15113a;border:1px solid ' . ($gold ? '#5c4a22' : '#352a78') . ';border-radius:16px;">
<tr><td style="padding:20px 22px;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:21px;color:#b4adda;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="top" style="font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:21px;color:#b4adda;">
<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Dárkový poukaz</div>
<div style="font-size:18px;line-height:26px;color:#ffffff;font-weight:700;margin-top:4px;">' . bb_h($o['product']['name']) . '</div>
<div>' . bb_h($o['product']['detail']) . '</div>' . $rows . '
</td>
<td valign="top" align="right" style="font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:20px;line-height:26px;font-weight:700;color:' . $acc . ';white-space:nowrap;padding-left:12px;">' . bb_h(bb_money($o['product']['price'])) . '</td>
</tr></table>
</td></tr></table>
</td></tr>';
}

function bb_email_steps(array $steps, bool $gold): string
{
    $acc = $gold ? '#f0cf85' : '#8a4dff';
    $numColor = $gold ? '#1a1406' : '#ffffff';
    $h = '<tr><td class="px" style="padding:30px 40px 6px;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8f89b8;font-weight:700;">Co bude dál</td></tr>
<tr><td class="px" style="padding:6px 40px 36px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
    foreach ($steps as $i => $t) {
        $h .= '<tr><td width="40" valign="top" style="padding:8px 0;"><div style="width:28px;height:28px;line-height:28px;border-radius:14px;background:' . $acc . ';color:' . $numColor . ';text-align:center;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;font-weight:700;">' . ($i + 1) . '</div></td>
<td valign="top" style="padding:11px 0 8px;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:15px;line-height:22px;color:#e9e6ff;">' . $t . '</td></tr>';
    }
    return $h . '</table></td></tr>';
}

function bb_email_invoice(array $cfg, array $o, bool $hasQrImg): string
{
    $gold = $o['kind'] === 'gold';
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $telHref = 'tel:' . preg_replace('/\s+/', '', $cfg['phone']);
    $f = 'font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;';
    $pay = [
        ['Číslo účtu', bb_h($o['account'])],
        ['Variabilní symbol', '<b style="color:#ffffff;">' . bb_h($o['vs']) . '</b>'],
        ['Částka', '<b style="color:#ffffff;">' . bb_h(bb_money($o['product']['price'])) . '</b>'],
        ['Splatnost', bb_h(bb_date($o['due']))],
    ];
    $payRows = '';
    foreach ($pay as $p) {
        $payRows .= '<tr><td style="' . $f . 'font-size:13px;line-height:20px;color:#8f89b8;padding:4px 14px 4px 0;white-space:nowrap;">' . $p[0] . '</td><td style="' . $f . 'font-size:15px;line-height:20px;color:#e9e6ff;padding:4px 0;">' . $p[1] . '</td></tr>';
    }
    $qr = $hasQrImg ? '<td class="col qrcell" width="150" valign="top" align="right" style="padding-left:16px;"><img src="cid:bbqr" width="138" height="138" alt="QR platba" style="display:inline-block;width:138px;height:138px;border:0;border-radius:10px;background:#ffffff;"><div style="' . $f . 'font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:' . $acc . ';font-weight:700;padding-top:6px;text-align:center;width:138px;">QR platba</div></td>' : '';

    $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:38px 40px 8px;' . $f . 'font-size:11px;letter-spacing:3px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Faktura č. ' . bb_h($o['no']) . '</td></tr>
<tr><td class="px h1" style="padding:0 40px 14px;font-family:Unbounded,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:30px;line-height:38px;color:#ffffff;font-weight:400;">Děkujeme za objednávku.</td></tr>
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">Vaše objednávka dárkového poukazu je přijata. Fakturu najdete <b style="color:#ffffff;">v příloze tohoto e-mailu</b>. Jakmile platba dorazí, ozveme se s potvrzením.</td></tr>
' . bb_email_summary($o, $gold) . '
<tr><td class="px" style="padding:26px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td class="col" valign="top">
<div style="' . $f . 'font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8f89b8;font-weight:700;padding-bottom:6px;">Platba převodem</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0">' . $payRows . '</table>
</td>' . $qr . '
</tr></table>
</td></tr>
' . bb_email_steps([
        'Zaplaťte fakturu převodem – nejrychleji naskenováním QR kódu v bankovní aplikaci.',
        'Po připsání platby vám pošleme <b style="color:#ffffff;">potvrzení o platbě</b> a dárkový poukaz.',
        'Pak už si jen zavoláte a <b style="color:#ffffff;">domluvíte termín</b> na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a>.',
    ], $gold) . '
</table>';
    return bb_email_layout('Faktura č. ' . $o['no'] . ' za dárkový poukaz Black Box – ' . bb_money($o['product']['price']) . '.', $inner, $cfg, $gold);
}

function bb_email_pickup(array $cfg, array $o): string
{
    $gold = $o['kind'] === 'gold';
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $telHref = 'tel:' . preg_replace('/\s+/', '', $cfg['phone']);
    $f = 'font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;';
    $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:38px 40px 8px;' . $f . 'font-size:11px;letter-spacing:3px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Objednávka přijata</td></tr>
<tr><td class="px h1" style="padding:0 40px 14px;font-family:Unbounded,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:30px;line-height:38px;color:#ffffff;font-weight:400;">Děkujeme za objednávku.</td></tr>
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">Poukaz pro vás připravíme. Brzy se ozveme a domluvíme, kdy si ho vyzvednete.</td></tr>
' . bb_email_summary($o, $gold) . '
' . bb_email_steps([
        'Ozveme se vám a domluvíme čas vyzvednutí.',
        'Na místě v Táboře zaplatíte a převezmete poukaz.',
        'Termín návštěvy si obdarovaný domluví na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a>.',
    ], $gold) . '
</table>';
    return bb_email_layout('Objednávka dárkového poukazu Black Box je přijata.', $inner, $cfg, $gold);
}

/** Interní e-mail pro majitele (prostý přehled) */
function bb_email_internal(array $o, string $way): string
{
    $c = $o['customer'];
    $rows = [
        'Způsob' => $way === 'faktura' ? 'Faktura (převod)' : 'Osobní odběr na místě',
        'Poukaz' => $o['product']['name'] . ' – ' . bb_money($o['product']['price']),
        'Pro koho' => $o['for'] ?: '—',
        'Věnování' => $o['msg'] ?: '—',
        'Jméno' => $c['name'],
        'Telefon' => $c['phone'],
        'E-mail' => $c['email'],
    ];
    if ($way === 'faktura') {
        $rows = ['Faktura / VS' => $o['no'] ?? '—'] + $rows;
        $rows['Adresa'] = $c['addr'];
        $rows['Firma / IČO'] = trim(($c['company'] ?: '') . ' ' . ($c['ico'] ?: '')) ?: '—';
        $rows['Splatnost'] = isset($o['due']) ? bb_date($o['due']) : '—';
    }
    $h = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#111"><h2 style="margin:0 0 12px">Nová objednávka poukazu</h2><table cellpadding="6" style="border-collapse:collapse">';
    foreach ($rows as $k => $v) {
        $h .= '<tr><td style="color:#666;border-bottom:1px solid #eee">' . bb_h($k) . '</td><td style="border-bottom:1px solid #eee"><b>' . bb_h((string) $v) . '</b></td></tr>';
    }
    return $h . '</table><p style="color:#666">Odesláno z webu ' . date('j. n. Y H:i') . '.</p></div>';
}

/* ---------- odeslání ---------- */

function bb_mailer(array $cfg): PHPMailer
{
    $m = new PHPMailer(true);
    $m->CharSet = 'UTF-8';
    $m->Encoding = 'base64';
    $smtp = $cfg['smtp'] ?? [];
    if (!empty($smtp['pass'])) {
        $m->isSMTP();
        $m->Host = $smtp['host'];
        $m->Port = (int) $smtp['port'];
        $m->SMTPSecure = $smtp['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->SMTPAuth = true;
        $m->Username = $smtp['user'];
        $m->Password = $smtp['pass'];
        $m->Timeout = 20;
        $m->setFrom($smtp['user'], $cfg['from_name']);
    } else {
        $m->isMail();
        $m->setFrom($cfg['from_fallback'], $cfg['from_name']);
    }
    $m->addReplyTo($cfg['reply_to'], $cfg['from_name']);
    return $m;
}

/* ---------- uložené objednávky ---------- */

function bb_orders_dir(string $dataDir): string
{
    $d = $dataDir . '/objednavky';
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
    return $d;
}

function bb_safe_id(string $id): string
{
    return preg_replace('/[^A-Za-z0-9\-]/', '', $id);
}

function bb_save_order(string $dataDir, array $o): void
{
    $o['updated'] = time();
    file_put_contents(bb_orders_dir($dataDir) . '/' . bb_safe_id($o['id']) . '.json', json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function bb_load_order(string $dataDir, string $id): ?array
{
    $f = bb_orders_dir($dataDir) . '/' . bb_safe_id($id) . '.json';
    if (!is_file($f)) {
        return null;
    }
    $o = json_decode((string) file_get_contents($f), true);
    return is_array($o) ? $o : null;
}

function bb_list_orders(string $dataDir): array
{
    $list = [];
    foreach (glob(bb_orders_dir($dataDir) . '/*.json') ?: [] as $f) {
        $o = json_decode((string) file_get_contents($f), true);
        if (is_array($o)) {
            $list[] = $o;
        }
    }
    usort($list, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
    return $list;
}

function bb_voucher_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $c = '';
    for ($i = 0; $i < 6; $i++) {
        $c .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return 'BB-' . substr($c, 0, 3) . '-' . substr($c, 3);
}

/* ---------- PDF poukaz ---------- */

function bb_voucher_pdf(array $cfg, array $o): string
{
    $clean = fn($t) => trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\x{20}-\x{7E}\x{A0}-\x{17F}\x{2013}-\x{201E}\x{2022}\x{2026}]/u', '', (string) $t)));
    $gold = $o['kind'] === 'gold';
    $A = $gold ? [240, 207, 133] : [185, 150, 255];
    $A2 = $gold ? [201, 160, 74] : [138, 77, 255];

    $pdf = new BBInvoicePdf('L', 'mm', 'A5');
    $pdf->SetTitle('Dárkový poukaz Black Box ' . $o['voucher'], true);
    $pdf->SetAuthor($cfg['seller']['name'], true);
    $pdf->AddFont('Manrope', '', 'Manrope-Regular.ttf', true);
    $pdf->AddFont('Manrope', 'B', 'Manrope-Bold.ttf', true);
    $pdf->AddFont('Manrope', 'I', 'Manrope-SemiBold.ttf', true);
    $pdf->AddFont('Unbounded', '', 'Unbounded-Regular.ttf', true);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $W = 210;
    $H = 148;

    /* pozadí + hvězdy */
    $pdf->SetFillColor(6, 5, 26);
    $pdf->Rect(0, 0, $W, $H, 'F');
    mt_srand(crc32($o['voucher']));
    for ($i = 0; $i < 140; $i++) {
        $x = mt_rand(0, $W * 10) / 10;
        $y = mt_rand(0, $H * 10) / 10;
        $r = mt_rand(10, 45) / 100;
        $b = mt_rand(90, 235);
        $pdf->SetFillColor($b, $b, min(255, $b + 20));
        $pdf->RoundedRect($x, $y, $r, $r, $r / 2, 'F');
    }
    /* rámeček */
    $pdf->SetDrawColor(...$A2);
    $pdf->SetLineWidth(0.35);
    $pdf->RoundedRect(7, 7, $W - 14, $H - 14, 5);
    $pdf->SetDrawColor(...$A);
    $pdf->SetLineWidth(0.15);
    $pdf->RoundedRect(9, 9, $W - 18, $H - 18, 4);

    /* logo */
    $pdf->Image(__DIR__ . '/assets/logo-pdf.jpg', 16, 30, 62, 62);
    $pdf->SetFont('Manrope', 'B', 7.5);
    $pdf->SetTextColor(...$A);
    $pdf->SetXY(16, 96);
    $pdf->Cell(62, 4, 'TÁBOR · OD SLUNCE', 0, 0, 'C');

    /* text */
    $x = 90;
    $w = $W - $x - 18;
    $pdf->SetFont('Manrope', 'B', 8);
    $pdf->SetTextColor(...$A);
    $pdf->SetXY($x, 22);
    $pdf->Cell($w, 4, 'DÁRKOVÝ POUKAZ');
    $pdf->SetFont('Unbounded', '', 26);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($x, 28);
    $pdf->Cell($w, 13, $gold ? 'Gold Time' : 'Standard');
    $pdf->SetFont('Manrope', '', 9.5);
    $pdf->SetTextColor(190, 186, 220);
    $pdf->SetXY($x, 42);
    $pdf->MultiCell($w, 5, $o['product']['detail'] . '. Vířivka, hvězdné nebe, projekce a zvuk.');

    $y = $pdf->GetY() + 7;
    $pdf->SetFont('Manrope', 'B', 7.5);
    $pdf->SetTextColor(...$A);
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 4, 'PRO');
    $pdf->SetFont('Unbounded', '', 15);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($x, $y + 5);
    $pdf->MultiCell($w, 8, $clean($o['for']) ?: '—');
    $y = $pdf->GetY();
    if (($o['msg'] ?? '') !== '') {
        $pdf->SetFont('Manrope', 'I', 10.5);
        $pdf->SetTextColor(220, 214, 250);
        $pdf->SetXY($x, $y + 2);
        $pdf->MultiCell($w, 5.5, '„' . $clean($o['msg']) . '“');
    }

    /* kód + rezervace */
    $by = $H - 38;
    $pdf->SetFillColor(21, 17, 58);
    $pdf->RoundedRect($x, $by, 46, 16, 3, 'F');
    $pdf->SetFont('Manrope', 'B', 6.5);
    $pdf->SetTextColor(...$A);
    $pdf->SetXY($x + 4, $by + 3);
    $pdf->Cell(40, 3, 'KÓD POUKAZU');
    $pdf->SetFont('Manrope', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($x + 4, $by + 7.5);
    $pdf->Cell(40, 6, $o['voucher']);

    $pdf->SetFont('Manrope', 'B', 6.5);
    $pdf->SetTextColor(...$A);
    $pdf->SetXY($x + 52, $by + 3);
    $pdf->Cell(50, 3, 'REZERVACE TERMÍNU');
    $pdf->SetFont('Manrope', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($x + 52, $by + 7.5);
    $pdf->Cell(50, 6, $cfg['phone']);

    $pdf->SetFont('Manrope', '', 7.5);
    $pdf->SetTextColor(143, 137, 184);
    $pdf->SetXY($x, $H - 18);
    $pdf->Cell($w, 4, 'Purkyňova 2961, 390 02 Tábor  ·  ' . preg_replace('~^https?://~', '', $cfg['web']));
    return $pdf->Output('S');
}

/* ---------- e-mail: platba přijata ---------- */

function bb_email_paid(array $cfg, array $o): string
{
    $gold = $o['kind'] === 'gold';
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $telHref = 'tel:' . preg_replace('/\s+/', '', $cfg['phone']);
    $f = 'font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;';
    $ref = !empty($o['no']) ? 'za fakturu č. ' . bb_h($o['no']) . ' ' : '';
    $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:38px 40px 8px;' . $f . 'font-size:11px;letter-spacing:3px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Potvrzení o platbě</td></tr>
<tr><td class="px h1" style="padding:0 40px 14px;font-family:Unbounded,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:30px;line-height:38px;color:#ffffff;font-weight:400;">Platba dorazila. Děkujeme!</td></tr>
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">Potvrzujeme přijetí platby ' . $ref . 've výši <b style="color:#ffffff;">' . bb_h(bb_money($o['product']['price'])) . '</b> (' . bb_h(bb_date($o['paid_at'])) . '). Dárkový poukaz najdete <b style="color:#ffffff;">v příloze</b> – připravený k tisku i k poslání dál.</td></tr>
' . bb_email_summary($o, $gold) . '
<tr><td class="px" style="padding:22px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px dashed ' . $acc . ';border-radius:14px;"><tr>
<td style="padding:16px 20px;' . $f . 'font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8f89b8;font-weight:700;">Kód poukazu</td>
<td align="right" style="padding:16px 20px;' . $f . 'font-size:20px;letter-spacing:2px;color:#ffffff;font-weight:700;">' . bb_h($o['voucher']) . '</td>
</tr></table>
</td></tr>
' . bb_email_steps([
        'Poukaz z přílohy vytiskněte, nebo ho rovnou pošlete obdarovanému.',
        'Termín si domluvíte telefonicky na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a> – stačí říct kód poukazu.',
        'Pak už jen přijďte do Black Boxu v Táboře a užijte si večer jen pro vás dva.',
    ], $gold) . '
</table>';
    return bb_email_layout('Platba přijata – posíláme dárkový poukaz Black Box.', $inner, $cfg, $gold);
}

/* ---------- faktura: vystavení a odeslání ---------- */

function bb_issue_invoice(array $cfg, string $dataDir, array $o, string $iban): array
{
    $no = bb_next_invoice_no($cfg, $dataDir);
    $issued = time();
    $o = array_merge($o, [
        'no' => $no,
        'vs' => preg_replace('/\D/', '', $no),
        'issued' => $issued,
        'due' => strtotime('+' . (int) $cfg['due_days'] . ' days', $issued),
        'account' => preg_replace('/\s+/', '', (string) $cfg['bank_account']),
        'iban' => $iban,
    ]);
    $o['spayd'] = bb_spayd($iban, (float) $o['product']['price'], $o['vs'], 'Poukaz Black Box ' . $no);
    if (!is_dir($dataDir . '/faktury')) {
        @mkdir($dataDir . '/faktury', 0755, true);
    }
    $o['invoice_file'] = 'faktura-' . $no . '.pdf';
    file_put_contents($dataDir . '/faktury/' . $o['invoice_file'], bb_invoice_pdf($cfg, $o));
    $o['id'] = $no;
    $o['way'] = 'faktura';
    $o['status'] = 'ceka';
    $o['created'] = $o['created'] ?? $issued;
    bb_save_order($dataDir, $o);
    return $o;
}

function bb_invoice_bytes(array $cfg, string $dataDir, array $o): string
{
    $f = $dataDir . '/faktury/' . ($o['invoice_file'] ?? '');
    return is_file($f) ? (string) file_get_contents($f) : bb_invoice_pdf($cfg, $o);
}

function bb_send_invoice_customer(array $cfg, string $dataDir, array $o): void
{
    $pdf = bb_invoice_bytes($cfg, $dataDir, $o);
    $qrPng = bb_qr_png($o['spayd']);
    $m = bb_mailer($cfg);
    $m->addAddress($o['customer']['email'], $o['customer']['name']);
    $m->Subject = 'Faktura č. ' . $o['no'] . ' – dárkový poukaz Black Box';
    $m->isHTML(true);
    $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
    if ($qrPng) {
        $m->addStringEmbeddedImage($qrPng, 'bbqr', 'qr-platba.png', 'base64', 'image/png');
    }
    $m->Body = bb_email_invoice($cfg, $o, (bool) $qrPng);
    $m->AltBody = "Děkujeme za objednávku dárkového poukazu.\n\nFaktura č. {$o['no']} je v příloze.\n"
        . "Číslo účtu: {$o['account']}\nVariabilní symbol: {$o['vs']}\nČástka: " . bb_money($o['product']['price']) . "\nSplatnost: " . bb_date($o['due']) . "\n\n"
        . "Po připsání platby vám pošleme potvrzení o platbě a dárkový poukaz. Termín si pak domluvíte na {$cfg['phone']}.\n\nBlack Box Luxury";
    $m->addStringAttachment($pdf, $o['invoice_file'], 'base64', 'application/pdf');
    $m->send();
}

function bb_send_invoice_copy(array $cfg, string $dataDir, array $o, string $title): void
{
    $m = bb_mailer($cfg);
    $m->addAddress($cfg['invoice_copy_to']);
    $m->clearReplyTos();
    $m->addReplyTo($o['customer']['email'], $o['customer']['name']);
    $m->Subject = $title . ' ' . $o['no'] . ' – ' . $o['product']['name'] . ' – ' . $o['customer']['name'];
    $m->isHTML(true);
    $m->Body = bb_email_internal($o, 'faktura');
    $m->AltBody = strip_tags(str_replace(['</tr>', '</td>'], ["\n", ' '], $m->Body));
    $m->addStringAttachment(bb_invoice_bytes($cfg, $dataDir, $o), $o['invoice_file'], 'base64', 'application/pdf');
    $m->send();
}

/** Potvrzení o platbě + poukaz zákazníkovi, kopie majiteli */
function bb_send_paid(array $cfg, array $o): void
{
    $pdf = bb_voucher_pdf($cfg, $o);
    $file = 'poukaz-black-box-' . $o['voucher'] . '.pdf';
    $m = bb_mailer($cfg);
    $m->addAddress($o['customer']['email'], $o['customer']['name']);
    $m->Subject = 'Platba přijata – váš dárkový poukaz Black Box';
    $m->isHTML(true);
    $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
    $m->Body = bb_email_paid($cfg, $o);
    $m->AltBody = "Platba " . (!empty($o['no']) ? "za fakturu č. {$o['no']} " : '') . "ve výši " . bb_money($o['product']['price']) . " byla přijata. Děkujeme!\n\n"
        . "Dárkový poukaz je v příloze (kód {$o['voucher']}).\nTermín si domluvíte na {$cfg['phone']}.\n\nBlack Box Luxury";
    $m->addStringAttachment($pdf, $file, 'base64', 'application/pdf');
    $m->send();
    try {
        $m = bb_mailer($cfg);
        $m->addAddress($cfg['invoice_copy_to']);
        $m->Subject = 'Odesláno potvrzení o platbě ' . ($o['no'] ?? '') . ' – ' . $o['customer']['name'] . ' – poukaz ' . $o['voucher'];
        $m->isHTML(true);
        $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
        $m->Body = bb_email_paid($cfg, $o);
        $m->AltBody = 'Kopie potvrzení o platbě pro ' . $o['customer']['email'];
        $m->addStringAttachment($pdf, $file, 'base64', 'application/pdf');
        $m->send();
    } catch (Throwable $e) {
    }
}
