<?php
/* Black Box – kontrola, že se na FTP nahrálo vše potřebné pro objednávky a faktury.
   Po kontrole tento soubor smažte (obsahuje cesty na serveru). */

header('Content-Type: text/html; charset=utf-8');
$base = __DIR__ . '/objednavky/lib';

$files = [
    'qrcode.php',
    'tfpdf/tfpdf.php',
    'tfpdf/font/unifont/ttfonts.php',
    'tfpdf/font/unifont/Manrope-Regular.ttf',
    'tfpdf/font/unifont/Manrope-SemiBold.ttf',
    'tfpdf/font/unifont/Manrope-Bold.ttf',
    'tfpdf/font/unifont/Unbounded-Regular.ttf',
    'PHPMailer/PHPMailer.php',
    'PHPMailer/SMTP.php',
    'PHPMailer/Exception.php',
    'assets/logo-email.png',
    'assets/logo-pdf.jpg',
    'assets/poukaz-standard.jpg',
    'assets/poukaz-gold.jpg',
];

echo '<!doctype html><html lang="cs"><head><meta charset="utf-8"><title>Kontrola souborů – Black Box</title>
<style>body{font:15px/1.6 system-ui,sans-serif;background:#0b0a1f;color:#e9e6ff;padding:30px;max-width:900px;margin:0 auto}
h1{font-size:20px}table{width:100%;border-collapse:collapse;margin:16px 0}td{padding:6px 10px;border-bottom:1px solid #2a2160}
.ok{color:#5fd39a;font-weight:700}.bad{color:#ff6b8a;font-weight:700}code{color:#b98cff}</style></head><body>';
echo '<h1>Kontrola souborů pro objednávky poukazů</h1>';

echo '<h2>1) Soubory v objednavky/lib</h2><table>';
$missing = [];
foreach ($files as $f) {
    $p = $base . '/' . $f;
    $exists = is_file($p);
    $readable = $exists && is_readable($p);
    $size = $exists ? filesize($p) : 0;
    if (!$exists || !$readable) {
        $missing[] = $f;
    }
    $status = $exists ? ($readable ? '<span class="ok">OK</span> (' . $size . ' B)' : '<span class="bad">existuje, ale nejde číst (práva)</span>') : '<span class="bad">CHYBÍ</span>';
    echo '<tr><td><code>' . htmlspecialchars($f) . '</code></td><td>' . $status . '</td></tr>';
}
echo '</table>';

echo '<h2>2) Zápis do objednavky/data</h2><table>';
$dataDir = __DIR__ . '/objednavky/data';
$writable = is_dir($dataDir) && is_writable($dataDir);
echo '<tr><td><code>objednavky/data</code></td><td>' . ($writable ? '<span class="ok">OK, lze zapisovat</span>' : '<span class="bad">nejde zapisovat – nastavte práva 755/777</span>') . '</td></tr></table>';

echo '<h2>3) PHP rozšíření</h2><table>';
foreach (['gd' => 'obrázky / QR kód', 'mbstring' => 'čeština', 'pdo_mysql' => 'databáze', 'openssl' => 'e-mail přes SMTP', 'zlib' => 'PDF'] as $ext => $why) {
    $ok = extension_loaded($ext);
    echo '<tr><td><code>' . $ext . '</code> (' . $why . ')</td><td>' . ($ok ? '<span class="ok">OK</span>' : '<span class="bad">CHYBÍ – požádejte hosting o zapnutí</span>') . '</td></tr>';
}
echo '<tr><td>Verze PHP</td><td>' . PHP_VERSION . '</td></tr>';
echo '</table>';

if ($missing) {
    echo '<h2 style="color:#ff6b8a">Chybí ' . count($missing) . ' soubor(ů) – ty je potřeba znovu nahrát na FTP:</h2><ul>';
    foreach ($missing as $f) {
        echo '<li><code>objednavky/lib/' . htmlspecialchars($f) . '</code></li>';
    }
    echo '</ul>';
} else {
    echo '<h2 style="color:#5fd39a">Všechny soubory jsou na místě ✓</h2><p>Pokud se faktura/poukaz přesto nevytváří, pošlete prosím přesné znění chyby.</p>';
}
echo '<p style="color:#8f89b8;margin-top:30px">Až dokončíte kontrolu, tento soubor (<code>test-souboru.php</code>) z FTP smažte.</p>';
echo '</body></html>';
