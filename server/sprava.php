<?php
/* Black Box Luxury – správa objednávek poukazů (potvrzení plateb, faktury, poukazy) */

declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require $BASE . '/lib/app.php';
$DATA = $BASE . '/data';

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('bbsprava');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $secure, 'samesite' => 'Strict']);
session_start();

$PW = (string) ($cfg['admin_password'] ?? '');
$h = fn($s) => bb_h((string) $s);
$self = strtok($_SERVER['REQUEST_URI'] ?? 'sprava.php', '?') ?: 'sprava.php';

function page(string $title, string $body): void
{
    echo '<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . bb_h($title) . ' · Black Box</title>
<link rel="icon" href="favicon.ico">
<style>
:root{--bg:#06051a;--card:#0e0b2c;--line:#2a2160;--txt:#e9e6ff;--mut:#8f89b8;--acc:#b98cff;--acc2:#8a4dff;--gold:#f0cf85;--ok:#5fd39a;--warn:#ffb86b;--bad:#ff6b8a}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%;background:#06051a}
body{margin:0;background:radial-gradient(ellipse at 50% -10%,#24125e,var(--bg) 60%) fixed;color:var(--txt);font:15px/1.5 Manrope,"Segoe UI",system-ui,sans-serif;min-height:100vh}
a{color:var(--acc)}
.wrap{max-width:980px;margin:0 auto;padding:22px 16px 60px}
header{display:flex;align-items:center;gap:14px;justify-content:space-between;flex-wrap:wrap;margin-bottom:18px}
header h1{font-size:20px;margin:0;font-weight:700;letter-spacing:.02em}
header h1 small{display:block;font-size:12px;color:var(--mut);font-weight:500;letter-spacing:.1em;text-transform:uppercase}
.tabs{display:flex;gap:6px;flex-wrap:wrap}
.tabs a{padding:7px 14px;border-radius:999px;border:1px solid var(--line);color:var(--mut);text-decoration:none;font-size:13px;font-weight:600}
.tabs a.on{background:var(--acc2);border-color:var(--acc2);color:#fff}
.tabs a b{opacity:.75;font-weight:700;margin-left:4px}
.flash{padding:12px 16px;border-radius:12px;margin:0 0 16px;font-weight:600}
.flash.ok{background:rgba(95,211,154,.12);border:1px solid rgba(95,211,154,.4);color:var(--ok)}
.flash.err{background:rgba(255,107,138,.12);border:1px solid rgba(255,107,138,.4);color:var(--bad)}
.warn{padding:12px 16px;border-radius:12px;margin:0 0 16px;background:rgba(255,184,107,.1);border:1px solid rgba(255,184,107,.35);color:var(--warn);font-size:14px}
.o{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px 18px 16px;margin-bottom:12px;position:relative;overflow:hidden}
.o.gold{border-color:#5c4a22}
.o::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--acc2)}
.o.gold::before{background:var(--gold)}
.top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
.ttl{font-weight:700;font-size:16px}
.ttl em{font-style:normal;color:var(--mut);font-weight:500;font-size:13px;margin-left:6px}
.price{font-weight:700;font-size:18px;color:var(--acc);white-space:nowrap}
.o.gold .price{color:var(--gold)}
.badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:999px;margin-left:6px;vertical-align:middle}
.b-ceka{background:rgba(255,184,107,.14);color:var(--warn)}
.b-zaplaceno,.b-vyrizeno{background:rgba(95,211,154,.14);color:var(--ok)}
.b-nova{background:rgba(185,140,255,.16);color:var(--acc)}
.b-bez_faktury{background:rgba(255,107,138,.14);color:var(--bad)}
.b-storno{background:rgba(143,137,184,.16);color:var(--mut)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:4px 18px;margin:12px 0 4px;font-size:14px}
.grid div span{color:var(--mut);font-size:12px;display:block}
.grid a{color:var(--txt)}
.ded{color:#cfc8f5;font-style:italic;margin:6px 0 0;font-size:14px}
.act{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;align-items:center}
.act form{margin:0}
.btn{appearance:none;border:0;cursor:pointer;font-family:inherit;font-weight:700;font-size:14px;line-height:1;padding:12px 18px;border-radius:999px;background:linear-gradient(135deg,#b066ff,#6f3bff);color:#fff;text-decoration:none;display:inline-flex;gap:8px;align-items:center}
.btn.gold{background:linear-gradient(135deg,#f6dc9c,#c9a04a);color:#1a1406}
.btn.ghost{background:transparent;border:1px solid var(--line);color:var(--txt);font-weight:600;padding:11px 14px;font-size:13px}
.btn.ghost:hover{border-color:var(--acc)}
.btn.del{color:var(--mut)}
.meta{color:var(--mut);font-size:12px;margin-top:10px}
.empty{color:var(--mut);text-align:center;padding:50px 0}
.login{max-width:360px;margin:12vh auto 0;background:var(--card);border:1px solid var(--line);border-radius:22px;padding:30px 26px;text-align:center}
.login img{width:110px;height:110px;border-radius:18px}
.login input{width:100%;padding:13px 14px;border-radius:12px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:16px;margin:16px 0 12px}
.login .btn{width:100%;justify-content:center}
.search{flex:1;min-width:180px;max-width:280px}
.search input{width:100%;padding:9px 14px;border-radius:999px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:14px}
</style></head><body>' . $body . '</body></html>';
    exit;
}

/* ---- správa vypnutá ---- */
if ($PW === '') {
    page('Správa', '<div class="login"><img src="img/logo-520.webp" alt=""><h2>Správa je vypnutá</h2><p style="color:#8f89b8">Nastavte heslo <code>admin_password</code> v souboru <code>objednavky/config.php</code>.</p></div>');
}

/* ---- přihlášení (s ochranou proti hádání hesla) ---- */
$lockFile = $DATA . '/prihlaseni.json';
$ipKey = sha1($_SERVER['REMOTE_ADDR'] ?? '0');
$locks = is_file($lockFile) ? (json_decode((string) file_get_contents($lockFile), true) ?: []) : [];
$fails = array_values(array_filter($locks[$ipKey] ?? [], fn($t) => $t > time() - 900));

if (isset($_GET['odhlasit'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $self);
    exit;
}

if (empty($_SESSION['bb_admin'])) {
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['heslo'])) {
        if (count($fails) >= 5) {
            $err = 'Příliš mnoho pokusů. Zkuste to za 15 minut.';
        } elseif (hash_equals($PW, (string) $_POST['heslo'])) {
            session_regenerate_id(true);
            $_SESSION['bb_admin'] = 1;
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            unset($locks[$ipKey]);
            @file_put_contents($lockFile, json_encode($locks), LOCK_EX);
            header('Location: ' . $self);
            exit;
        } else {
            usleep(800000);
            $fails[] = time();
            $locks[$ipKey] = $fails;
            @file_put_contents($lockFile, json_encode($locks), LOCK_EX);
            $err = 'Špatné heslo.';
        }
    }
    page('Přihlášení', '<div class="login"><img src="img/logo-520.webp" alt="Black Box"><h2 style="margin:16px 0 0;font-weight:600">Správa objednávek</h2>
<form method="post"><input type="password" name="heslo" placeholder="Heslo" autocomplete="current-password" autofocus required><button class="btn" type="submit">Přihlásit</button></form>'
        . ($err ? '<p style="color:#ff6b8a;margin:14px 0 0">' . bb_h($err) . '</p>' : '') . '</div>');
}

/* ---- databáze ---- */
try {
    bb_db();
} catch (Throwable $e) {
    page('Chyba databáze', '<div class="login"><img src="img/logo-520.webp" alt=""><h2>Nelze se připojit k databázi</h2><p style="color:#8f89b8">Zkontrolujte údaje <code>db</code> v <code>objednavky/config.php</code>.</p><p style="color:#ff6b8a;font-size:13px">' . bb_h($e->getMessage()) . '</p></div>');
}

/* ---- stažení PDF ---- */
if (isset($_GET['dl'], $_GET['id'])) {
    $o = bb_load_order($DATA, (string) $_GET['id']);
    if (!$o) {
        http_response_code(404);
        exit('Objednávka nenalezena.');
    }
    if ($_GET['dl'] === 'faktura' && !empty($o['no'])) {
        $pdf = bb_invoice_bytes($cfg, $DATA, $o);
        $name = $o['invoice_file'];
    } elseif ($_GET['dl'] === 'poukaz') {
        if (empty($o['voucher'])) {
            $o['voucher'] = bb_voucher_code();
            bb_save_order($DATA, $o);
        }
        $pdf = bb_voucher_pdf($cfg, $o);
        $name = 'poukaz-black-box-' . $o['voucher'] . '.pdf';
    } else {
        http_response_code(404);
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $name . '"');
    echo $pdf;
    exit;
}

/* ---- export do CSV (Excel) ---- */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="objednavky-poukazu-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Objednáno', 'Způsob', 'Stav', 'Faktura / VS', 'Splatnost', 'Zaplaceno', 'Poukaz', 'Kód poukazu', 'Cena', 'Pro koho', 'Věnování', 'Jméno', 'Telefon', 'E-mail', 'Adresa', 'Firma', 'IČO'], ';');
    foreach (bb_list_orders($DATA) as $o) {
        $c = $o['customer'];
        fputcsv($out, [
            date('j. n. Y H:i', (int) $o['created']), $o['way'] === 'faktura' ? 'faktura' : 'osobní odběr', $o['status'], $o['no'] ?? '',
            !empty($o['due']) ? date('j. n. Y', (int) $o['due']) : '', !empty($o['paid_at']) ? date('j. n. Y', (int) $o['paid_at']) : '',
            $o['kind'] === 'gold' ? 'Gold Time' : 'Standard', $o['voucher'] ?? '', $o['product']['price'], $o['for'] ?? '', $o['msg'] ?? '',
            $c['name'], $c['phone'], $c['email'], $c['addr'] ?? '', $c['company'] ?? '', $c['ico'] ?? '',
        ], ';');
    }
    exit;
}

/* ---- akce ---- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $back = $self . '?f=' . urlencode((string) ($_POST['f'] ?? 'aktivni'));
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['flash'] = ['err', 'Platnost stránky vypršela, zkuste to znovu.'];
        header('Location: ' . $back);
        exit;
    }
    $o = bb_load_order($DATA, (string) ($_POST['id'] ?? ''));
    $a = (string) ($_POST['a'] ?? '');
    try {
        if (!$o) {
            throw new RuntimeException('Objednávka nenalezena.');
        }
        $who = $o['customer']['name'];
        switch ($a) {
            case 'zaplaceno':
                if (empty($o['voucher'])) {
                    $o['voucher'] = bb_voucher_code();
                }
                $o['paid_at'] = $o['paid_at'] ?? time();
                bb_send_paid($cfg, $o);
                $o['status'] = 'zaplaceno';
                $o['paid_mail_at'] = time();
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Platba potvrzena – potvrzení a poukaz {$o['voucher']} odešly na {$o['customer']['email']}."];
                break;
            case 'faktura_znovu':
                bb_send_invoice_customer($cfg, $DATA, $o);
                $o['sent_at'] = time();
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Faktura {$o['no']} znovu odeslána na {$o['customer']['email']}."];
                break;
            case 'vystavit':
                $iban = bb_iban((string) $cfg['bank_account']);
                if (!$iban) {
                    throw new RuntimeException('V config.php chybí platné číslo účtu.');
                }
                $oldId = $o['id'];
                $o = bb_issue_invoice($cfg, $DATA, $o, $iban);
                bb_send_invoice_customer($cfg, $DATA, $o);
                $o['sent_at'] = time();
                bb_save_order($DATA, $o);
                if ($oldId !== $o['id']) {
                    bb_delete_order($DATA, $oldId);
                }
                try {
                    bb_send_invoice_copy($cfg, $DATA, $o, 'Vystavena faktura');
                } catch (Throwable $e) {
                }
                $_SESSION['flash'] = ['ok', "Faktura {$o['no']} vystavena a odeslána na {$o['customer']['email']}."];
                break;
            case 'vyrizeno':
                $o['status'] = 'vyrizeno';
                $o['done_at'] = time();
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Objednávka – $who – označena jako vyřízená."];
                break;
            case 'storno':
                $o['prev_status'] = $o['status'];
                $o['status'] = 'storno';
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Objednávka – $who – stornována."];
                break;
            case 'obnovit':
                $o['status'] = $o['prev_status'] ?? ($o['way'] === 'faktura' ? 'ceka' : 'nova');
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Objednávka – $who – obnovena."];
                break;
            default:
                throw new RuntimeException('Neznámá akce.');
        }
    } catch (Throwable $e) {
        @file_put_contents($DATA . '/chyby.log', date('c') . ' sprava ' . $e->getMessage() . "\n", FILE_APPEND);
        $_SESSION['flash'] = ['err', 'Nepovedlo se: ' . $e->getMessage()];
    }
    header('Location: ' . $back);
    exit;
}

/* ---- výpis ---- */
$all = bb_list_orders($DATA);
$f = (string) ($_GET['f'] ?? 'aktivni');
$q = trim((string) ($_GET['q'] ?? ''));
$active = ['ceka', 'nova', 'bez_faktury'];
$counts = ['aktivni' => 0, 'zaplaceno' => 0, 'vse' => count($all)];
foreach ($all as $o) {
    if (in_array($o['status'], $active, true)) $counts['aktivni']++;
    if (in_array($o['status'], ['zaplaceno', 'vyrizeno'], true)) $counts['zaplaceno']++;
}
$list = array_filter($all, function ($o) use ($f, $active, $q) {
    if ($f === 'aktivni' && !in_array($o['status'], $active, true)) return false;
    if ($f === 'zaplaceno' && !in_array($o['status'], ['zaplaceno', 'vyrizeno'], true)) return false;
    if ($q !== '') {
        $hay = mb_strtolower(implode(' ', [$o['no'] ?? '', $o['voucher'] ?? '', $o['for'] ?? '', $o['customer']['name'], $o['customer']['email'], $o['customer']['phone']]));
        if (mb_strpos($hay, mb_strtolower($q)) === false) return false;
    }
    return true;
});

$LABEL = ['ceka' => 'Čeká na platbu', 'nova' => 'Nová – osobní odběr', 'bez_faktury' => 'Bez faktury', 'zaplaceno' => 'Zaplaceno', 'vyrizeno' => 'Vyřízeno', 'storno' => 'Storno'];
$csrf = $_SESSION['csrf'] ?? '';
$btn = function (array $o, string $a, string $label, string $cls = 'ghost', string $confirm = '') use ($csrf, $f, $h) {
    return '<form method="post"' . ($confirm ? ' onsubmit="return confirm(\'' . $h($confirm) . '\')"' : '') . '><input type="hidden" name="csrf" value="' . $h($csrf) . '"><input type="hidden" name="id" value="' . $h($o['id']) . '"><input type="hidden" name="a" value="' . $a . '"><input type="hidden" name="f" value="' . $h($f) . '"><button class="btn ' . $cls . '" type="submit">' . $label . '</button></form>';
};

$out = '<div class="wrap"><header><h1><small>Black Box Luxury</small>Objednávky poukazů</h1>
<div class="tabs">';
foreach (['aktivni' => 'K vyřízení', 'zaplaceno' => 'Zaplacené', 'vse' => 'Vše'] as $k => $t) {
    $out .= '<a class="' . ($f === $k ? 'on' : '') . '" href="?f=' . $k . '">' . $t . '<b>' . $counts[$k] . '</b></a>';
}
$out .= '</div><form class="search" method="get"><input type="hidden" name="f" value="' . $h($f) . '"><input type="search" name="q" value="' . $h($q) . '" placeholder="Hledat jméno, VS, kód…"></form>
<a href="?export=csv" style="color:#8f89b8;font-size:13px">Export do Excelu</a> <a href="?odhlasit=1" style="color:#8f89b8;font-size:13px">Odhlásit</a></header>';

if (!empty($_SESSION['flash'])) {
    [$t, $m] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $out .= '<div class="flash ' . $t . '">' . $h($m) . '</div>';
}
if (!bb_db()) {
    $out .= '<div class="warn">Databáze není nastavená – objednávky se ukládají do souborů. Doplňte údaje <code>db</code> v <code>objednavky/config.php</code>.</div>';
}
if (!bb_iban((string) $cfg['bank_account'])) {
    $out .= '<div class="warn">V <code>objednavky/config.php</code> chybí číslo účtu – faktury se zatím nevystavují automaticky.</div>';
}
if (!$list) {
    $out .= '<div class="empty">' . ($f === 'aktivni' ? 'Nic nečeká. Všechno je vyřízené ✨' : 'Žádné objednávky.') . '</div>';
}

foreach ($list as $o) {
    $gold = $o['kind'] === 'gold';
    $c = $o['customer'];
    $st = $o['status'];
    $out .= '<div class="o' . ($gold ? ' gold' : '') . '"><div class="top"><div>
<div class="ttl">' . ($gold ? 'Gold Time' : 'Standard') . '<em>' . ($o['way'] === 'faktura' ? (!empty($o['no']) ? 'Faktura ' . $h($o['no']) : 'Faktura') : 'Osobní odběr') . '</em><span class="badge b-' . $h($st) . '">' . $h($LABEL[$st] ?? $st) . '</span></div>
<div class="meta" style="margin-top:2px">Objednáno ' . $h(date('j. n. Y H:i', (int) $o['created'])) . (!empty($o['due']) && $st === 'ceka' ? ' · splatnost ' . $h(bb_date((int) $o['due'])) . ((int) $o['due'] < time() ? ' <b style="color:#ff6b8a">po splatnosti</b>' : '') : '') . '</div>
</div><div class="price">' . $h(bb_money((float) $o['product']['price'])) . '</div></div>
<div class="grid">
<div><span>Zákazník</span>' . $h($c['name']) . ($c['company'] ? '<br>' . $h($c['company']) : '') . '</div>
<div><span>Kontakt</span><a href="tel:' . $h(preg_replace('/\s+/', '', $c['phone'])) . '">' . $h($c['phone']) . '</a><br><a href="mailto:' . $h($c['email']) . '">' . $h($c['email']) . '</a></div>
<div><span>Poukaz pro</span>' . $h($o['for'] ?: '—') . (!empty($o['voucher']) ? '<br><span style="display:inline;color:#8f89b8">kód</span> <b>' . $h($o['voucher']) . '</b>' : '') . '</div>
' . (!empty($o['vs']) ? '<div><span>Variabilní symbol</span><b>' . $h($o['vs']) . '</b></div>' : '') . '
</div>' . (($o['msg'] ?? '') !== '' ? '<p class="ded">„' . $h($o['msg']) . '“</p>' : '') . '<div class="act">';

    if ($st === 'ceka') {
        $out .= $btn($o, 'zaplaceno', 'Platba přišla – poslat potvrzení a poukaz', $gold ? 'gold' : '', 'Potvrdit platbu ' . bb_money((float) $o['product']['price']) . ' a poslat poukaz na ' . $c['email'] . '?');
        $out .= $btn($o, 'faktura_znovu', 'Poslat fakturu znovu', 'ghost', 'Poslat fakturu znovu na ' . $c['email'] . '?');
    }
    if ($st === 'bez_faktury') {
        $out .= $btn($o, 'vystavit', 'Vystavit a poslat fakturu', $gold ? 'gold' : '', 'Vystavit fakturu a poslat ji na ' . $c['email'] . '?');
    }
    if ($st === 'nova') {
        $out .= '<a class="btn ' . ($gold ? 'gold' : '') . '" href="?dl=poukaz&id=' . urlencode($o['id']) . '" target="_blank">Poukaz k tisku</a>';
        $out .= $btn($o, 'vyrizeno', 'Předáno – vyřízeno');
    }
    if (!empty($o['no'])) {
        $out .= '<a class="btn ghost" href="?dl=faktura&id=' . urlencode($o['id']) . '" target="_blank">Faktura PDF</a>';
    }
    if (in_array($st, ['zaplaceno', 'vyrizeno'], true)) {
        $out .= '<a class="btn ghost" href="?dl=poukaz&id=' . urlencode($o['id']) . '" target="_blank">Poukaz PDF</a>';
        if ($st === 'zaplaceno') {
            $out .= $btn($o, 'zaplaceno', 'Poslat poukaz znovu', 'ghost', 'Poslat potvrzení a poukaz znovu na ' . $c['email'] . '?');
        }
    }
    if ($st === 'storno') {
        $out .= $btn($o, 'obnovit', 'Obnovit');
    } elseif (!in_array($st, ['zaplaceno', 'vyrizeno'], true)) {
        $out .= $btn($o, 'storno', 'Storno', 'ghost del', 'Opravdu stornovat objednávku?');
    }
    $out .= '</div>';
    $meta = [];
    if (!empty($o['sent_at'])) $meta[] = 'faktura odeslána ' . date('j. n. H:i', (int) $o['sent_at']);
    if (!empty($o['paid_mail_at'])) $meta[] = 'potvrzení o platbě odesláno ' . date('j. n. H:i', (int) $o['paid_mail_at']);
    if (!empty($o['done_at'])) $meta[] = 'vyřízeno ' . date('j. n. H:i', (int) $o['done_at']);
    if ($meta) $out .= '<div class="meta">' . $h(implode(' · ', $meta)) . '</div>';
    $out .= '</div>';
}
$out .= '</div>';
page('Objednávky', $out);
