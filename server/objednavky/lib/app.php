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

/** Má objednávka dárkový poukaz? (objednávka z patičky může být i bez poukazu) */
function bb_has_voucher(array $o): bool
{
    return !array_key_exists('send_voucher', $o) || !empty($o['send_voucher']);
}

/** Jméno zákazníka pro e-maily/fakturu – když chybí, použije se e-mail */
function bb_who(array $o): string
{
    $c = $o['customer'];
    return (string) (($c['name'] ?? '') !== '' ? $c['name'] : ($c['email'] ?? ''));
}

/** Doručení poukazu: 'email' | 'posta' | '' (bez poukazu / starší objednávka) */
function bb_delivery(array $o): string
{
    if (!bb_has_voucher($o)) {
        return '';
    }
    $d = (string) ($o['delivery'] ?? '');
    if ($d === '' && ($o['way'] ?? '') === 'faktura') {
        $d = 'email';                       // starší objednávky s fakturou – poukaz chodil e-mailem
    }
    return $d;
}

function bb_delivery_label(array $o): string
{
    if (!bb_has_voucher($o)) {
        return 'bez poukazu';
    }
    switch (bb_delivery($o)) {
        case 'posta': return 'poštou';
        case 'email': return 'e-mailem';
        default:      return ($o['way'] ?? '') === 'odber' ? 'osobní odběr' : '—';
    }
}

/** Kód poukazu – jen velká písmena, číslice a pomlčka, bez mezer */
function bb_norm_code(string $c): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $c) ?? '');
}

/** Najde objednávku podle kódu poukazu (nebo null) */
function bb_find_voucher(string $dataDir, string $code, string $exceptId = ''): ?array
{
    $code = bb_norm_code($code);
    if ($code === '') {
        return null;
    }
    foreach (bb_list_orders($dataDir) as $o) {
        if (($o['id'] ?? '') !== $exceptId && bb_norm_code((string) ($o['voucher'] ?? '')) === $code) {
            return $o;
        }
    }
    return null;
}

/**
 * Stav poukazu pro správu:
 * 'storno' | 'ceka' (nezaplaceno) | 'k_odeslani' (zaplaceno, poštou, ještě neodesláno) | 'platny' | 'pouzity'
 */
function bb_voucher_state(array $o): string
{
    $st = (string) ($o['status'] ?? '');
    if ($st === 'storno') return 'storno';
    if (!empty($o['used_at'])) return 'pouzity';
    if (!in_array($st, ['zaplaceno', 'vyrizeno'], true)) return 'ceka';
    if (bb_delivery($o) === 'posta' && empty($o['voucher_sent_at'])) return 'k_odeslani';
    return 'platny';
}

function bb_money(float $n): string
{
    return number_format($n, 0, ',', "\u{00A0}") . "\u{00A0}Kč";
}

/* ---------- nastavení ze správy (ceník, oznámení) – tabulka bb_nastaveni, bez DB soubor data/<klic>.json ---------- */

function bb_setting_get(string $key): ?array
{
    try {
        if ($db = bb_db()) {
            $st = $db->prepare('SELECT hodnota FROM bb_nastaveni WHERE klic = ?');
            $st->execute([$key]);
            $raw = $st->fetchColumn();
            $v = $raw ? json_decode((string) $raw, true) : null;
            return is_array($v) ? $v : null;
        }
    } catch (Throwable $e) {
        return null;
    }
    $f = dirname(__DIR__) . '/data/' . preg_replace('/[^a-z0-9_-]/', '', $key) . '.json';
    $v = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($v) ? $v : null;
}

function bb_setting_set(string $key, array $value): void
{
    $json = json_encode($value, JSON_UNESCAPED_UNICODE);
    if ($db = bb_db()) {
        $db->prepare('INSERT INTO bb_nastaveni (klic, hodnota, upraveno) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE hodnota = VALUES(hodnota), upraveno = VALUES(upraveno)')->execute([$key, $json]);
        return;
    }
    $d = dirname(__DIR__) . '/data';
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
    if (file_put_contents($d . '/' . preg_replace('/[^a-z0-9_-]/', '', $key) . '.json', $json, LOCK_EX) === false) {
        throw new RuntimeException('Nelze uložit nastavení (databáze není nastavená a soubor nejde zapsat).');
    }
}

/** Aktuální ceny: uložené ze správy, jinak výchozí z config.php. Vrací ['standard' => 1990, 'gold' => 2490]. */
function bb_prices(array $cfg): array
{
    $p = [];
    foreach ($cfg['products'] as $k => $prod) {
        $p[$k] = (int) $prod['price'];
    }
    $saved = bb_setting_get('ceny');
    if (is_array($saved)) {
        foreach ($p as $k => $v) {
            if (isset($saved[$k]) && is_numeric($saved[$k]) && (int) $saved[$k] > 0) {
                $p[$k] = (int) $saved[$k];
            }
        }
    }
    return $p;
}

function bb_save_prices(array $prices): void
{
    bb_setting_set('ceny', $prices);
}

/** Oznámení na web: ['zapnuto' => bool, 'text' => '', 'do' => 'YYYY-MM-DD' | ''] */
function bb_notice(): array
{
    $n = bb_setting_get('oznameni') ?? [];
    return ['zapnuto' => !empty($n['zapnuto']), 'text' => (string) ($n['text'] ?? ''), 'do' => (string) ($n['do'] ?? '')];
}

/** Oznámení, které se má právě teď zobrazit na webu (nebo null) */
function bb_notice_public(): ?array
{
    $n = bb_notice();
    if (!$n['zapnuto'] || trim($n['text']) === '') {
        return null;
    }
    if ($n['do'] !== '' && strtotime($n['do'] . ' 23:59:59') < time()) {
        return null;
    }
    return ['text' => $n['text'], 'id' => substr(sha1($n['text']), 0, 10)];
}

/** Produkty z config.php s aktuálními cenami */
function bb_products(array $cfg): array
{
    $out = $cfg['products'];
    foreach (bb_prices($cfg) as $k => $v) {
        $out[$k]['price'] = $v;
    }
    return $out;
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

/** Pořadové číslo faktury: prefix + rok + 4 číslice (v databázi atomicky, jinak zamčený soubor) */
function bb_next_invoice_no(array $cfg, string $dataDir): string
{
    $year = (int) date('Y');
    $db = bb_db();
    if ($db) {
        $db->prepare('INSERT INTO bb_cislovani (rok, posledni) VALUES (?, LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE posledni = LAST_INSERT_ID(posledni + 1)')->execute([$year]);
        $seq = (int) $db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
    } else {
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
    }
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

    function SkewStart(float $t, float $baselineY): void
    {
        $this->_out(sprintf('q 1 0 %.4F 1 %.4F 0 cm', $t, -$t * ($this->h - $baselineY) * $this->k));
    }

    function SkewEnd(): void
    {
        $this->_out('Q');
    }

    function PtToMm(float $pt): float
    {
        return $pt / $this->k;
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
    $pdf->Cell($R - 80, 5, (bb_has_voucher($o) ? 'Dárkový poukaz · ' : '') . 'privátní relaxační prostor · Tábor', 0, 2, 'R');

    /* dodavatel / odběratel */
    $y = 60;
    $pdf->Label($L, $y, 'Dodavatel');
    $pdf->Label(112, $y, 'Odběratel');
    $pdf->SetTextColor(20, 18, 40);
    $pdf->SetFont('Manrope', 'B', 11);
    $pdf->SetXY($L, $y + 5);
    $pdf->Cell(88, 6, $s['name']);
    $pdf->SetXY(112, $y + 5);
    $pdf->MultiCell($R - 112, 6, $o['customer']['company'] ?: ($o['customer']['name'] ?: $o['customer']['email']));
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
    $cl[] = $c['addr'] ?? '';
    if ($c['ico']) {
        $cl[] = 'IČ: ' . $c['ico'];
    }
    if ($c['company'] || $c['name'] !== '') {
        $cl[] = $c['email'];
    }
    $cl[] = $c['phone'];
    $cl = array_values(array_filter($cl, fn($v) => trim((string) $v) !== ''));
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
    if (bb_has_voucher($o) && bb_delivery($o) !== '') {
        $pdf->SetXY($L, $yy);
        $pdf->Cell(100, 5, 'Poukaz na doručitele · doručení ' . bb_delivery_label($o));
        $yy += 5;
    }
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
        !bb_has_voucher($o) ? 'Po připsání platby vám e-mailem pošleme potvrzení o platbě.'
            : (bb_delivery($o) === 'posta' ? 'Po připsání platby vám tištěný dárkový poukaz pošleme poštou.' : 'Po připsání platby vám e-mailem pošleme potvrzení o platbě a dárkový poukaz.'),
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
    if (bb_has_voucher($o) && bb_delivery($o) !== '') {
        $rows .= '<div style="margin-top:6px;">Doručení poukazu: <b style="color:#ffffff;">' . bb_h(bb_delivery_label($o)) . '</b>'
            . (bb_delivery($o) === 'posta' && !empty($o['customer']['addr']) ? '<br>' . bb_h($o['customer']['name']) . ', ' . bb_h($o['customer']['addr']) : '') . '</div>';
    }
    if (($o['for'] ?? '') !== '') {
        $rows .= '<div style="margin-top:6px;">Pro: <b style="color:#ffffff;">' . bb_h($o['for']) . '</b></div>';
    }
    return '<tr><td class="px" style="padding:0 40px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#15113a;border:1px solid ' . ($gold ? '#5c4a22' : '#352a78') . ';border-radius:16px;">
<tr><td style="padding:20px 22px;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:21px;color:#b4adda;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td valign="top" style="font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:14px;line-height:21px;color:#b4adda;">
<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">' . (bb_has_voucher($o) ? 'Dárkový poukaz' : 'Objednávka') . '</div>
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
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">' . (bb_has_voucher($o) ? 'Vaše objednávka dárkového poukazu je přijata.' : 'Vaše objednávka je přijata.') . ' Fakturu najdete <b style="color:#ffffff;">v příloze tohoto e-mailu</b>. Jakmile platba dorazí, ozveme se s potvrzením.</td></tr>
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
        !bb_has_voucher($o) ? 'Po připsání platby vám pošleme <b style="color:#ffffff;">potvrzení o platbě</b>.'
            : (bb_delivery($o) === 'posta' ? 'Po připsání platby vám <b style="color:#ffffff;">tištěný poukaz pošleme poštou</b>.' : 'Po připsání platby vám pošleme <b style="color:#ffffff;">potvrzení o platbě</b> a dárkový poukaz e-mailem.'),
        'Pak už si jen zavoláte a <b style="color:#ffffff;">domluvíte termín</b> na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a>.',
    ], $gold) . '
</table>';
    return bb_email_layout('Faktura č. ' . $o['no'] . (bb_has_voucher($o) ? ' za dárkový poukaz Black Box – ' : ' – Black Box – ') . bb_money($o['product']['price']) . '.', $inner, $cfg, $gold);
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
        'Způsob' => !empty($o['quick']) ? 'Objednávka z patičky webu – faktura (převod)' : ($way === 'faktura' ? 'Faktura (převod)' : 'Osobní odběr na místě'),
        'Poukaz' => $o['product']['name'] . ' – ' . bb_money($o['product']['price']),
        'Doručení poukazu' => bb_delivery_label($o),
        'Jméno' => $c['name'] ?: '—',
        'Telefon' => $c['phone'],
        'E-mail' => $c['email'],
    ];
    if ($way === 'faktura') {
        $rows = ['Faktura / VS' => $o['no'] ?? '—'] + $rows;
        $rows[bb_delivery($o) === 'posta' ? 'Adresa pro poukaz' : 'Adresa'] = $c['addr'] ?: '—';
        $rows['Firma / IČO'] = trim(($c['company'] ?: '') . ' ' . ($c['ico'] ?: '')) ?: '—';
        $rows['Splatnost'] = isset($o['due']) ? bb_date($o['due']) : '—';
    }
    $h = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#111"><h2 style="margin:0 0 12px">' . (bb_has_voucher($o) ? 'Nová objednávka poukazu' : 'Nová objednávka') . '</h2><table cellpadding="6" style="border-collapse:collapse">';
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
    $m->XMailer = ' ';          // skrýt hlavičku "X-Mailer: PHPMailer…" – ta občas zvyšuje spam skóre
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
        // Message-ID/HELO musí být ze stejné domény jako odesílatel (jinak to vypadá podezřele
        // – hosting by jinak použil svůj vlastní technický název serveru, např. "server123.endora.cz").
        $m->Hostname = substr((string) strrchr($smtp['user'], '@'), 1) ?: 'email.cz';
    } else {
        $m->isMail();
        $m->setFrom($cfg['from_fallback'], $cfg['from_name']);
        $m->Hostname = substr((string) strrchr($cfg['from_fallback'], '@'), 1) ?: 'black-box-luxury.cz';
    }
    $m->addReplyTo($cfg['reply_to'], $cfg['from_name']);
    return $m;
}

/* ---------- uložené objednávky: MySQL databáze (nebo soubory, když databáze není nastavená) ---------- */

/** Připojení k MySQL podle config.php ('db'). Při prvním připojení vytvoří tabulky a převezme starší data ze souborů. */
function bb_db(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $pdo = null;
    $c = $GLOBALS['cfg']['db'] ?? [];
    if (empty($c['name']) || empty($c['user'])) {
        return null;
    }
    $pdo = new PDO(
        'mysql:host=' . ($c['host'] ?: 'localhost') . (!empty($c['port']) ? ';port=' . (int) $c['port'] : '') . ';dbname=' . $c['name'] . ';charset=utf8mb4',
        $c['user'],
        (string) ($c['pass'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    bb_db_install($pdo);
    return $pdo;
}

function bb_db_install(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS bb_objednavky (
        id            VARCHAR(40)  NOT NULL PRIMARY KEY,
        zpusob        VARCHAR(10)  NOT NULL COMMENT 'faktura / odber',
        poukaz        VARCHAR(10)  NOT NULL COMMENT 'standard / gold',
        stav          VARCHAR(15)  NOT NULL COMMENT 'ceka, nova, bez_faktury, zaplaceno, vyrizeno, storno',
        cislo_faktury VARCHAR(20)  NULL UNIQUE,
        kod_poukazu   VARCHAR(20)  NULL UNIQUE,
        cena          INT          NOT NULL,
        pro_koho      VARCHAR(100) NOT NULL DEFAULT '',
        venovani      VARCHAR(200) NOT NULL DEFAULT '',
        jmeno         VARCHAR(150) NOT NULL,
        email         VARCHAR(190) NOT NULL,
        telefon       VARCHAR(50)  NOT NULL,
        adresa        VARCHAR(255) NOT NULL DEFAULT '',
        firma         VARCHAR(160) NOT NULL DEFAULT '',
        ico           VARCHAR(12)  NOT NULL DEFAULT '',
        vytvoreno     DATETIME     NOT NULL,
        splatnost     DATE         NULL,
        zaplaceno     DATETIME     NULL,
        upraveno      DATETIME     NOT NULL,
        data          MEDIUMTEXT   NOT NULL COMMENT 'kompletní objednávka (JSON)',
        KEY k_stav (stav),
        KEY k_vytvoreno (vytvoreno)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='Black Box – objednávky dárkových poukazů'");
    $db->exec("CREATE TABLE IF NOT EXISTS bb_nastaveni (
        klic     VARCHAR(40) NOT NULL PRIMARY KEY,
        hodnota  TEXT        NOT NULL,
        upraveno DATETIME    NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Black Box – nastavení ze správy (ceník)'");
    $db->exec("CREATE TABLE IF NOT EXISTS bb_cislovani (
        rok      INT NOT NULL PRIMARY KEY,
        posledni INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Black Box – poslední číslo faktury v roce'");

    // jednorázový převod starších objednávek a číslování ze souborů
    $dataDir = dirname(__DIR__) . '/data';
    if ((int) $db->query('SELECT COUNT(*) FROM bb_objednavky')->fetchColumn() === 0) {
        foreach (glob($dataDir . '/objednavky/*.json') ?: [] as $f) {
            $o = json_decode((string) file_get_contents($f), true);
            if (is_array($o) && !empty($o['id'])) {
                bb_db_save($db, $o);
            }
        }
    }
    $cnt = $dataDir . '/cislovani-faktur.json';
    if (is_file($cnt)) {
        foreach (json_decode((string) file_get_contents($cnt), true) ?: [] as $rok => $n) {
            $db->prepare('INSERT INTO bb_cislovani (rok, posledni) VALUES (?, ?) ON DUPLICATE KEY UPDATE posledni = GREATEST(posledni, VALUES(posledni))')->execute([(int) $rok, (int) $n]);
        }
        @rename($cnt, $cnt . '.prevedeno');
    }
}

function bb_db_save(PDO $db, array $o): void
{
    $c = $o['customer'];
    $dt = fn($ts) => $ts ? date('Y-m-d H:i:s', (int) $ts) : null;
    $db->prepare('INSERT INTO bb_objednavky
        (id, zpusob, poukaz, stav, cislo_faktury, kod_poukazu, cena, pro_koho, venovani, jmeno, email, telefon, adresa, firma, ico, vytvoreno, splatnost, zaplaceno, upraveno, data)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE zpusob=VALUES(zpusob), poukaz=VALUES(poukaz), stav=VALUES(stav), cislo_faktury=VALUES(cislo_faktury),
        kod_poukazu=VALUES(kod_poukazu), cena=VALUES(cena), pro_koho=VALUES(pro_koho), venovani=VALUES(venovani), jmeno=VALUES(jmeno),
        email=VALUES(email), telefon=VALUES(telefon), adresa=VALUES(adresa), firma=VALUES(firma), ico=VALUES(ico),
        splatnost=VALUES(splatnost), zaplaceno=VALUES(zaplaceno), upraveno=VALUES(upraveno), data=VALUES(data)')
        ->execute([
            $o['id'], $o['way'], $o['kind'], $o['status'], $o['no'] ?? null, $o['voucher'] ?? null, (int) $o['product']['price'],
            mb_substr((string) ($o['for'] ?? ''), 0, 100), mb_substr((string) ($o['msg'] ?? ''), 0, 200),
            $c['name'], $c['email'], $c['phone'], (string) ($c['addr'] ?? ''), (string) ($c['company'] ?? ''), (string) ($c['ico'] ?? ''),
            $dt($o['created'] ?? time()), !empty($o['due']) ? date('Y-m-d', (int) $o['due']) : null, $dt($o['paid_at'] ?? null), $dt(time()),
            json_encode($o, JSON_UNESCAPED_UNICODE),
        ]);
}

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
    if ($db = bb_db()) {
        bb_db_save($db, $o);
        return;
    }
    file_put_contents(bb_orders_dir($dataDir) . '/' . bb_safe_id($o['id']) . '.json', json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function bb_load_order(string $dataDir, string $id): ?array
{
    if ($db = bb_db()) {
        $st = $db->prepare('SELECT data FROM bb_objednavky WHERE id = ?');
        $st->execute([$id]);
        $raw = $st->fetchColumn();
        $o = $raw ? json_decode((string) $raw, true) : null;
        return is_array($o) ? $o : null;
    }
    $f = bb_orders_dir($dataDir) . '/' . bb_safe_id($id) . '.json';
    if (!is_file($f)) {
        return null;
    }
    $o = json_decode((string) file_get_contents($f), true);
    return is_array($o) ? $o : null;
}

function bb_delete_order(string $dataDir, string $id): void
{
    if ($db = bb_db()) {
        $db->prepare('DELETE FROM bb_objednavky WHERE id = ?')->execute([$id]);
        return;
    }
    @unlink(bb_orders_dir($dataDir) . '/' . bb_safe_id($id) . '.json');
}

function bb_list_orders(string $dataDir): array
{
    $list = [];
    if ($db = bb_db()) {
        foreach ($db->query('SELECT data FROM bb_objednavky ORDER BY vytvoreno DESC') as $r) {
            $o = json_decode((string) $r['data'], true);
            if (is_array($o)) {
                $list[] = $o;
            }
        }
        return $list;
    }
    foreach (glob(bb_orders_dir($dataDir) . '/*.json') ?: [] as $f) {
        $o = json_decode((string) file_get_contents($f), true);
        if (is_array($o)) {
            $list[] = $o;
        }
    }
    usort($list, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
    return $list;
}

/*
 * Kód poukazu je stejné číslo jako variabilní symbol (VS) – u faktury se tedy
 * poukaz pozná podle čísla faktury/VS. U osobního odběru (bez faktury) se mu
 * přidělí další číslo ze stejné řady, takže formát je vždy stejný – jen číslice.
 */
function bb_ensure_voucher(array $cfg, string $dataDir, array &$o): string
{
    if (!empty($o['voucher'])) {
        return $o['voucher'];
    }
    if (empty($o['vs'])) {
        $o['vs'] = preg_replace('/\D/', '', bb_next_invoice_no($cfg, $dataDir));
    }
    $code = $o['vs'];
    if (bb_find_voucher($dataDir, $code, (string) ($o['id'] ?? ''))) {   // číslo už někdo dostal ručně (poukaz poštou)
        $code .= '-E';
    }
    $o['voucher'] = $code;
    return $o['voucher'];
}

/* ---------- PDF poukaz – stejný jako karta na webu ---------- */

/*
 * Podklad assets/poukaz-*.jpg je přesný snímek karty poukazu z webu (bez textu),
 * 616.6 × 510.3 CSS px (karta 528.6 × 326.3 px, okraj 44 px, dole 140 px). Poukaz je univerzální
 * (na doručitele) – na kartě je „Black Box Luxury / relaxační zóna“, dopisuje se jen kód poukazu.
 */
function bb_voucher_pdf(array $cfg, array $o): string
{
    $gold = $o['kind'] === 'gold';
    $A = $gold ? [240, 207, 133] : [185, 150, 255];

    $W = 210.0;                        // šířka stránky v mm
    $k = $W / 616.578;                 // mm na 1 CSS px
    $imgH = 510.281 * $k;              // ≈ 173.8 mm
    $H = round(482 * $k, 2);           // stránka končí kousek pod kódem poukazu
    $pt = $k / 0.352778;               // pt na 1 CSS px

    $pdf = new BBInvoicePdf('L', 'mm', [$H, $W]);
    $pdf->SetTitle('Dárkový poukaz Black Box ' . $o['voucher'], true);
    $pdf->SetAuthor($cfg['seller']['name'], true);
    $pdf->AddFont('Manrope', '', 'Manrope-Regular.ttf', true);
    $pdf->AddFont('Manrope', 'B', 'Manrope-Bold.ttf', true);
    $pdf->AddFont('Unbounded', '', 'Unbounded-Regular.ttf', true);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->AddPage();

    $pdf->SetFillColor(6, 5, 26);
    $pdf->Rect(0, 0, $W, $H, 'F');
    $pdf->Image(__DIR__ . '/assets/poukaz-' . ($gold ? 'gold' : 'standard') . '.jpg', 0, 0, $W, $imgH);

    $x = (37 + 44) * $k;
    $maxW = 416.5 * $k;

    /* Kód poukazu – v pravém horním rohu karty, pod „Standard · 2 hodiny" */
    $codeTxt = 'KÓD ' . $o['voucher'];
    $pdf->SetFont('Manrope', 'B', 13 * $pt);
    $pdf->SetTextColor(...$A);
    $cw = $pdf->GetStringWidth($codeTxt);
    $xr = 535.6 * $k - $cw;
    $yr = 138 * $k;
    $pdf->Text($xr, $yr, $codeTxt);

    /* pruh pod kartou: kód + rezervace + adresa */
    $y = (370.28 + 64) * $k;
    $pdf->SetFont('Manrope', 'B', 6.5);
    $pdf->SetTextColor(...$A);
    $cx = $x;
    $parts = [['KÓD POUKAZU', $o['voucher']], ['REZERVACE TERMÍNU', $cfg['phone']], ['ADRESA', 'Purkyňova 2961, Tábor']];
    $colW = [44, 52, 60];
    foreach ($parts as $i => $p) {
        $pdf->SetFont('Manrope', 'B', 6.3);
        $pdf->SetTextColor(...$A);
        $pdf->SetXY($cx, $y);
        $pdf->Cell($colW[$i], 3, $p[0]);
        $pdf->SetFont('Manrope', 'B', 10.5);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($cx, $y + 3.6);
        $pdf->Cell($colW[$i], 5, $p[1]);
        $cx += $colW[$i];
    }
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
    $hasV = bb_has_voucher($o);
    $post = $hasV && bb_delivery($o) === 'posta';
    if ($post) {
        $hasV = false;                       // poukaz poštou: v e-mailu není PDF ani kód (dorazí tištěný)
    }
    $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:38px 40px 8px;' . $f . 'font-size:11px;letter-spacing:3px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Potvrzení o platbě</td></tr>
<tr><td class="px h1" style="padding:0 40px 14px;font-family:Unbounded,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:30px;line-height:38px;color:#ffffff;font-weight:400;">Platba dorazila. Děkujeme!</td></tr>
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">Potvrzujeme přijetí platby ' . $ref . 've výši <b style="color:#ffffff;">' . bb_h(bb_money($o['product']['price'])) . '</b> (' . bb_h(bb_date($o['paid_at'])) . '). ' . ($hasV ? 'Dárkový poukaz najdete <b style="color:#ffffff;">v příloze</b> – připravený k tisku i k poslání dál.' : ($post ? 'Tištěný dárkový poukaz vám <b style="color:#ffffff;">pošleme poštou</b> na adresu ' . bb_h($o['customer']['name'] . ', ' . $o['customer']['addr']) . '.' : 'Teď už si stačí jen zavolat a domluvit termín.')) . '</td></tr>
' . bb_email_summary($o, $gold) . ($hasV ? '
<tr><td class="px" style="padding:22px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px dashed ' . $acc . ';border-radius:14px;"><tr>
<td style="padding:16px 20px;' . $f . 'font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8f89b8;font-weight:700;">Kód poukazu</td>
<td align="right" style="padding:16px 20px;' . $f . 'font-size:20px;letter-spacing:2px;color:#ffffff;font-weight:700;">' . bb_h($o['voucher']) . '</td>
</tr></table>
</td></tr>' : '') . '
' . bb_email_steps($hasV ? [
        'Poukaz z přílohy vytiskněte, nebo ho rovnou pošlete obdarovanému.',
        'Termín si domluvíte telefonicky na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a> – stačí říct kód poukazu.',
        'Pak už jen přijďte do Black Boxu v Táboře a užijte si večer jen pro vás dva.',
    ] : ($post ? [
        'Poukaz připravíme a pošleme poštou – o odeslání vám dáme vědět e-mailem.',
        'Termín si obdarovaný domluví telefonicky na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a> – stačí říct číslo poukazu.',
        'Pak už jen přijďte do Black Boxu v Táboře a užijte si večer jen pro vás dva.',
    ] : [
        'Termín si domluvíte telefonicky na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a> – stačí říct číslo faktury.',
        'Pak už jen přijďte do Black Boxu v Táboře a užijte si večer jen pro vás dva.',
    ]), $gold) . '
</table>';
    return bb_email_layout($hasV ? 'Platba přijata – posíláme dárkový poukaz Black Box.' : ($post ? 'Platba přijata – poukaz vám pošleme poštou.' : 'Platba přijata – děkujeme.'), $inner, $cfg, $gold);
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
    $o['spayd'] = bb_spayd($iban, (float) $o['product']['price'], $o['vs'], (bb_has_voucher($o) ? 'Poukaz Black Box ' : 'Black Box ') . $no);
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
    $m->Subject = 'Faktura č. ' . $o['no'] . (bb_has_voucher($o) ? ' – dárkový poukaz Black Box' : ' – Black Box');
    $m->isHTML(true);
    $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
    if ($qrPng) {
        $m->addStringEmbeddedImage($qrPng, 'bbqr', 'qr-platba.png', 'base64', 'image/png');
    }
    $m->Body = bb_email_invoice($cfg, $o, (bool) $qrPng);
    $m->AltBody = (bb_has_voucher($o) ? "Děkujeme za objednávku dárkového poukazu." : "Děkujeme za objednávku.") . "\n\nFaktura č. {$o['no']} je v příloze.\n"
        . "Číslo účtu: {$o['account']}\nVariabilní symbol: {$o['vs']}\nČástka: " . bb_money($o['product']['price']) . "\nSplatnost: " . bb_date($o['due']) . "\n\n"
        . "Po připsání platby vám pošleme potvrzení o platbě" . (bb_has_voucher($o) ? " a dárkový poukaz" : "") . ". Termín si pak domluvíte na {$cfg['phone']}.\n\nBlack Box Luxury";
    $m->addStringAttachment($pdf, $o['invoice_file'], 'base64', 'application/pdf');
    $m->send();
}

function bb_send_invoice_copy(array $cfg, string $dataDir, array $o, string $title): void
{
    $m = bb_mailer($cfg);
    $m->addAddress($cfg['invoice_copy_to']);
    $m->clearReplyTos();
    $m->addReplyTo($o['customer']['email'], $o['customer']['name']);
    $m->Subject = $title . ' ' . $o['no'] . ' – ' . $o['product']['name'] . ' – ' . bb_who($o);
    $m->isHTML(true);
    $m->Body = bb_email_internal($o, 'faktura');
    $m->AltBody = strip_tags(str_replace(['</tr>', '</td>'], ["\n", ' '], $m->Body));
    $m->addStringAttachment(bb_invoice_bytes($cfg, $dataDir, $o), $o['invoice_file'], 'base64', 'application/pdf');
    $m->send();
}

/** Potvrzení o platbě + poukaz zákazníkovi, kopie majiteli */
function bb_send_paid(array $cfg, array $o): void
{
    $hasV = bb_has_voucher($o) && bb_delivery($o) !== 'posta';
    $post = bb_has_voucher($o) && bb_delivery($o) === 'posta';
    $pdf = $hasV ? bb_voucher_pdf($cfg, $o) : '';
    $file = $hasV ? 'poukaz-black-box-' . $o['voucher'] . '.pdf' : '';
    $m = bb_mailer($cfg);
    $m->addAddress($o['customer']['email'], $o['customer']['name']);
    $m->Subject = $hasV ? 'Platba přijata – váš dárkový poukaz Black Box' : ($post ? 'Platba přijata – poukaz pošleme poštou' : 'Platba přijata – Black Box');
    $m->isHTML(true);
    $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
    $m->Body = bb_email_paid($cfg, $o);
    $m->AltBody = "Platba " . (!empty($o['no']) ? "za fakturu č. {$o['no']} " : '') . "ve výši " . bb_money($o['product']['price']) . " byla přijata. Děkujeme!\n\n"
        . ($hasV ? "Dárkový poukaz je v příloze (kód {$o['voucher']}).\n" : '') . ($post ? "Tištěný poukaz vám pošleme poštou.\n" : '') . "Termín si domluvíte na {$cfg['phone']}.\n\nBlack Box Luxury";
    if ($hasV) {
        $m->addStringAttachment($pdf, $file, 'base64', 'application/pdf');
    }
    $m->send();
    try {
        $m = bb_mailer($cfg);
        $m->addAddress($cfg['invoice_copy_to']);
        $m->Subject = 'Odesláno potvrzení o platbě ' . ($o['no'] ?? '') . ' – ' . bb_who($o) . ($hasV ? ' – poukaz ' . $o['voucher'] : ($post ? ' – poukaz POŠTOU (přidělte číslo a odešlete)' : ' – bez poukazu'));
        $m->isHTML(true);
        $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
        $m->Body = bb_email_paid($cfg, $o);
        $m->AltBody = 'Kopie potvrzení o platbě pro ' . $o['customer']['email'];
        if ($hasV) {
            $m->addStringAttachment($pdf, $file, 'base64', 'application/pdf');
        }
        $m->send();
    } catch (Throwable $e) {
    }
}

/* ---------- poukaz odeslán poštou ---------- */

function bb_email_posted(array $cfg, array $o): string
{
    $gold = $o['kind'] === 'gold';
    $acc = $gold ? '#f0cf85' : '#b98cff';
    $telHref = 'tel:' . preg_replace('/\s+/', '', $cfg['phone']);
    $f = 'font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;';
    $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td class="px" style="padding:38px 40px 8px;' . $f . 'font-size:11px;letter-spacing:3px;text-transform:uppercase;color:' . $acc . ';font-weight:700;">Poukaz je na cestě</td></tr>
<tr><td class="px h1" style="padding:0 40px 14px;font-family:Unbounded,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:30px;line-height:38px;color:#ffffff;font-weight:400;">Poukaz jsme odeslali poštou.</td></tr>
<tr><td class="px" style="padding:0 40px 26px;' . $f . 'font-size:16px;line-height:25px;color:#c9c3ee;">Tištěný dárkový poukaz je na cestě na adresu <b style="color:#ffffff;">' . bb_h($o['customer']['name'] . ', ' . $o['customer']['addr']) . '</b>. Obvykle dorazí do 1–3 pracovních dnů.</td></tr>
' . bb_email_summary($o, $gold) . '
<tr><td class="px" style="padding:22px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px dashed ' . $acc . ';border-radius:14px;"><tr>
<td style="padding:16px 20px;' . $f . 'font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8f89b8;font-weight:700;">Číslo poukazu</td>
<td align="right" style="padding:16px 20px;' . $f . 'font-size:20px;letter-spacing:2px;color:#ffffff;font-weight:700;">' . bb_h((string) $o['voucher']) . '</td>
</tr></table>
</td></tr>
' . bb_email_steps([
        'Poukaz předejte obdarovanému – platí pro kohokoli, kdo ho přinese.',
        'Termín si domluví telefonicky na <a href="' . $telHref . '" style="color:' . $acc . ';text-decoration:none;font-weight:700;">' . bb_h($cfg['phone']) . '</a> – stačí říct číslo poukazu.',
        'Pak už jen přijít do Black Boxu v Táboře a užít si večer jen pro dva.',
    ], $gold) . '
</table>';
    return bb_email_layout('Dárkový poukaz Black Box č. ' . $o['voucher'] . ' jsme odeslali poštou.', $inner, $cfg, $gold);
}

/** E-mail zákazníkovi: poukaz byl odeslán poštou */
function bb_send_posted(array $cfg, array $o): void
{
    $m = bb_mailer($cfg);
    $m->addAddress($o['customer']['email'], $o['customer']['name']);
    $m->Subject = 'Dárkový poukaz Black Box je na cestě';
    $m->isHTML(true);
    $m->addEmbeddedImage(__DIR__ . '/assets/logo-email.png', 'bblogo', 'black-box.png');
    $m->Body = bb_email_posted($cfg, $o);
    $m->AltBody = "Tištěný dárkový poukaz č. {$o['voucher']} jsme odeslali poštou na adresu {$o['customer']['name']}, {$o['customer']['addr']}.\n"
        . "Termín si obdarovaný domluví na {$cfg['phone']}.\n\nBlack Box Luxury";
    $m->send();
}
