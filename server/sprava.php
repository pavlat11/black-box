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

/* ---- logo (bere se z objednavky/lib/assets, nezávisle na složce img/ webu) ---- */
if (isset($_GET['logo'])) {
    $f = $BASE . '/lib/assets/logo-email.png';
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=604800');
    header_remove('X-Robots-Tag');
    readfile($f);
    exit;
}

$PW = (string) ($cfg['admin_password'] ?? '');
$h = fn($s) => bb_h((string) $s);
$self = strtok($_SERVER['REQUEST_URI'] ?? 'sprava.php', '?') ?: 'sprava.php';

function page(string $title, string $body): void
{
    echo '<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . bb_h($title) . ' · Black Box</title>
<link rel="icon" type="image/png" href="?logo=1">
<style>
:root{--bg:#06051a;--card:#0e0b2c;--line:#2a2160;--txt:#e9e6ff;--mut:#8f89b8;--acc:#b98cff;--acc2:#8a4dff;--gold:#f0cf85;--ok:#5fd39a;--warn:#ffb86b;--bad:#ff6b8a}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%;background:#06051a}
body{margin:0;background:radial-gradient(ellipse at 50% -10%,#24125e,var(--bg) 60%) fixed;color:var(--txt);font:15px/1.5 Manrope,"Segoe UI",system-ui,sans-serif;min-height:100vh;display:flow-root}
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
details.edit{margin-top:12px;border-top:1px solid var(--line);padding-top:10px}
details.edit summary{cursor:pointer;color:var(--mut);font-size:13px;font-weight:700;list-style:none}
details.edit summary::-webkit-details-marker{display:none}
details.edit summary::before{content:"✎ ";opacity:.8}
details.edit[open] summary{color:var(--txt)}
.editform{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:12px}
.editform label{display:block;font-size:12px;color:var(--mut)}
.editform label.full{grid-column:1/-1}
.editform input,.editform textarea{width:100%;margin-top:5px;padding:9px 11px;border-radius:10px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:14px}
.editform textarea{resize:vertical;min-height:42px}
.editform button.full{grid-column:1/-1;width:100%;justify-content:center}
.prices{max-width:520px}
.pc{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;cursor:text}
.pc .ttl em{display:block;margin:2px 0 0}
.pin{display:flex;align-items:center;gap:8px}
.pin input{width:130px;padding:11px 12px;border-radius:12px;border:1px solid var(--line);background:#15113a;color:#fff;font:700 20px/1 Manrope,"Segoe UI",system-ui,sans-serif;text-align:right}
.pc.gold .pin input{color:var(--gold);border-color:#5c4a22}
.pin b{color:var(--mut);font-size:14px}
.prices .btn{margin-top:6px}
.menu{position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:14px;padding:10px 16px;background:rgba(8,6,30,.86);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.mlogo{display:flex;align-items:center;gap:9px;color:var(--txt);text-decoration:none;font-weight:700;letter-spacing:.14em;text-transform:uppercase;font-size:12px;white-space:nowrap}
.mlogo img{width:30px;height:30px;border-radius:8px}
.mi{display:flex;gap:4px;flex:1;justify-content:center;overflow-x:auto;scrollbar-width:none}
.mi::-webkit-scrollbar{display:none}
.mi a{padding:8px 14px;border-radius:999px;color:var(--mut);text-decoration:none;font-size:14px;font-weight:600;white-space:nowrap}
.mi a:hover{color:var(--txt)}
.mi a.on{background:rgba(185,140,255,.16);color:#fff}
.mout{color:var(--mut);font-size:13px;white-space:nowrap}
.mr{display:flex;align-items:center;gap:14px}
.mweb{padding:7px 14px;border-radius:999px;border:1px solid var(--line);color:var(--txt);text-decoration:none;font-size:13px;font-weight:600;white-space:nowrap}
.mweb:hover{border-color:var(--acc);color:#fff}
.mweb span{color:var(--acc)}
@media (max-width:640px){.menu{flex-wrap:wrap;gap:8px}.mlogo{order:1}.mr{order:2;margin-left:auto;gap:10px}.mweb{padding:6px 11px;font-size:12.5px}.mi{order:3;flex-basis:100%;justify-content:space-between;gap:2px}.mi a{padding:8px 10px;font-size:13.5px}}
.ph{font-size:22px;font-weight:700;margin:4px 0 18px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.notice{max-width:620px;padding:22px}
.notice::before{display:none}
.lbl{display:block;font-size:12px;color:var(--mut);font-weight:600}
.lbl textarea,.lbl input{width:100%;margin-top:6px;padding:12px 13px;border-radius:12px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:15px;color-scheme:dark}
.lbl textarea{resize:vertical;min-height:84px}
.nrow{display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;margin:14px 0 18px}
.nrow .lbl{flex:1;min-width:180px}
.sw{display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;padding-bottom:10px}
.sw input{position:absolute;opacity:0}
.sw i{width:44px;height:26px;border-radius:99px;background:#2a2160;position:relative;transition:.2s}
.sw i::after{content:"";position:absolute;left:3px;top:3px;width:20px;height:20px;border-radius:50%;background:#8f89b8;transition:.2s}
.sw input:checked+i{background:var(--acc2)}
.sw input:checked+i::after{left:21px;background:#fff}
.sw input:focus-visible+i{outline:2px solid var(--acc);outline-offset:2px}
.scards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:12px}
.sc{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px}
.sc span{display:block;color:var(--mut);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.sc b{display:block;font-size:26px;margin:8px 0 4px;white-space:nowrap}
.sc small{color:var(--mut);font-size:13px}
.sc.hi{border-color:var(--acc2);background:linear-gradient(180deg,rgba(138,77,255,.18),var(--card))}
.sc.wait b{color:var(--warn)}
.split{padding:18px}
.bar2{display:flex;height:12px;border-radius:99px;overflow:hidden;background:#15113a;margin:12px 0 8px}
.bar2 i{background:var(--acc2)}
.bar2 i.g{background:var(--gold)}
.leg{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;color:var(--mut)}
.leg .g,.st .g{color:var(--gold)}
.st{width:100%;border-collapse:collapse;font-size:14px}
.st th{text-align:left;color:var(--mut);font-size:12px;font-weight:600;padding:6px 8px;border-bottom:1px solid var(--line)}
.st td{padding:9px 8px;border-bottom:1px solid rgba(42,33,96,.6)}
.st .r{text-align:right;white-space:nowrap}
.st tr.zero td{color:#5d5888}
.st .barc{width:28%}
.st .barc i{display:block;height:8px;border-radius:99px;background:linear-gradient(90deg,var(--acc2),var(--acc))}
@media (max-width:640px){.st .barc{display:none}}
.mut{color:var(--mut)}
.sub{color:var(--mut);font-size:13px;margin-top:3px}
.o.hi{border-color:var(--acc);box-shadow:0 0 0 3px rgba(185,140,255,.18)}
.b-post{background:rgba(143,227,255,.14);color:#8fe3ff}
.b-used{background:rgba(143,137,184,.2);color:#cfc9f2}
.prog{list-style:none;display:flex;gap:0;margin:16px 0 6px;padding:0;counter-reset:p}
.prog li{flex:1;position:relative;padding:22px 6px 0 0;font-size:12.5px;color:var(--mut);min-width:0}
.prog li::before{content:"";position:absolute;left:0;top:4px;width:12px;height:12px;border-radius:50%;border:2px solid #3a3070;background:var(--bg);z-index:1}
.prog li::after{content:"";position:absolute;left:12px;right:0;top:9px;height:2px;background:#2a2160}
.prog li:last-child::after{display:none}
.prog li b{display:block;font-weight:700;color:inherit;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prog li small{display:block;font-size:11.5px;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prog li.done{color:var(--txt)}
.prog li.done::before{background:var(--ok);border-color:var(--ok)}
.prog li.done::after{background:rgba(95,211,154,.5)}
.prog li.cur{color:var(--warn)}
.prog li.cur::before{border-color:var(--warn);box-shadow:0 0 0 4px rgba(255,184,107,.15)}
.o.gold .prog li.done::before{background:var(--gold);border-color:var(--gold)}
.o.gold .prog li.done::after{background:rgba(240,207,133,.45)}
@media (max-width:560px){.prog li{font-size:11.5px}.prog li small{font-size:10.5px}}
.code{font-size:16px;letter-spacing:.06em;font-variant-numeric:tabular-nums}
.usedbox{margin-top:10px;padding:10px 14px;border-radius:12px;background:rgba(143,137,184,.12);border:1px solid rgba(143,137,184,.3);color:#d9d4f5;font-weight:600;font-size:14px}
.useform{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1}
.useform input{flex:1;min-width:180px;padding:11px 13px;border-radius:999px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:14px}
.postbox{margin-top:14px;padding:14px 16px;border-radius:14px;border:1px dashed rgba(143,227,255,.45);background:rgba(143,227,255,.05);display:flex;flex-wrap:wrap;gap:12px 16px;align-items:flex-end}
.postbox.sent{border-style:solid;border-color:rgba(95,211,154,.4);background:rgba(95,211,154,.05)}
.postbox .pbh{flex-basis:100%;font-weight:700;color:#8fe3ff}
.postbox.sent .pbh{color:var(--ok)}
.postbox .lbl{flex:0 1 200px}
.postbox .lbl input{font:700 17px/1 Manrope,"Segoe UI",system-ui,sans-serif;letter-spacing:.06em;text-transform:uppercase;padding:11px 13px}
.postbox .meta{flex-basis:100%;margin:0}
.chk{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;padding-bottom:11px}
.chk input{width:18px;height:18px;accent-color:var(--acc2)}
.check{background:linear-gradient(135deg,rgba(138,77,255,.22),rgba(14,11,44,.9));border:1px solid var(--acc2);border-radius:18px;padding:16px 18px;margin:0 0 16px}
.check label{display:block;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--acc);margin-bottom:8px}
.ch-row{display:flex;gap:8px}
.ch-row input::placeholder{text-transform:none;letter-spacing:0;font-weight:500;color:#6f6a99}
.ch-row input{flex:1;min-width:0;padding:13px 16px;border-radius:999px;border:1px solid var(--line);background:#15113a;color:#fff;font:700 18px/1 Manrope,"Segoe UI",system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase}
.verdict{display:flex;flex-direction:column;gap:2px;padding:14px 18px;border-radius:14px;margin:0 0 10px;font-size:14px}
.verdict b{font-size:17px}
.verdict.ok{background:rgba(95,211,154,.12);border:1px solid rgba(95,211,154,.45);color:var(--ok)}
.verdict.bad{background:rgba(255,107,138,.12);border:1px solid rgba(255,107,138,.45);color:var(--bad)}
.verdict.warn{background:rgba(255,184,107,.1);border:1px solid rgba(255,184,107,.4);color:var(--warn)}
.verdict span{color:var(--txt);opacity:.85}
details.more{margin-top:12px;border-top:1px solid var(--line);padding-top:10px}
details.more summary{cursor:pointer;color:var(--mut);font-size:13px;font-weight:700;list-style:none}
details.more summary::-webkit-details-marker{display:none}
details.more summary::before{content:"▸ ";}
details.more[open] summary::before{content:"▾ ";}
details.more[open] summary{color:var(--txt)}
.editform select{width:100%;margin-top:5px;padding:9px 11px;border-radius:10px;border:1px solid var(--line);background:#15113a;color:#fff;font-family:inherit;font-size:14px}
</style></head><body>' . $body . '</body></html>';
    exit;
}

/* ---- správa vypnutá ---- */
if ($PW === '') {
    page('Správa', '<div class="login"><img src="?logo=1" alt=""><h2>Správa je vypnutá</h2><p style="color:#8f89b8">Nastavte heslo <code>admin_password</code> v souboru <code>objednavky/config.php</code>.</p></div>');
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
    page('Přihlášení', '<div class="login"><img src="?logo=1" alt="Black Box"><h2 style="margin:16px 0 0;font-weight:600">Správa objednávek</h2>
<form method="post"><input type="password" name="heslo" placeholder="Heslo" autocomplete="current-password" autofocus required><button class="btn" type="submit">Přihlásit</button></form>'
        . ($err ? '<p style="color:#ff6b8a;margin:14px 0 0">' . bb_h($err) . '</p>' : '') . '</div>');
}

/* ---- databáze ---- */
try {
    bb_db();
} catch (Throwable $e) {
    page('Chyba databáze', '<div class="login"><img src="?logo=1" alt=""><h2>Nelze se připojit k databázi</h2><p style="color:#8f89b8">Zkontrolujte údaje <code>db</code> v <code>objednavky/config.php</code>.</p><p style="color:#ff6b8a;font-size:13px">' . bb_h($e->getMessage()) . '</p></div>');
}

/* ---- hlavní menu správy ---- */
function menu(string $on): string
{
    $items = ['objednavky' => 'Objednávky', 'cenik' => 'Ceník', 'oznameni' => 'Oznámení', 'statistiky' => 'Statistiky'];
    $self = strtok($_SERVER['REQUEST_URI'] ?? 'sprava.php', '?') ?: 'sprava.php';
    $o = '<nav class="menu"><a class="mlogo" href="' . bb_h($self) . '"><img src="?logo=1" alt="">Black Box</a><div class="mi">';
    foreach ($items as $k => $t) {
        $href = $k === 'objednavky' ? $self : '?' . $k . '=1';
        $o .= '<a' . ($on === $k ? ' class="on"' : '') . ' href="' . bb_h($href) . '">' . $t . '</a>';
    }
    $web = rtrim(dirname($self), '/\\') . '/';
    return $o . '</div><div class="mr"><a class="mweb" href="' . bb_h($web) . '" target="_blank" rel="noopener">Přejít na web <span aria-hidden="true">↗</span></a><a class="mout" href="?odhlasit=1">Odhlásit</a></div></nav>';
}
function flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    [$t, $m] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="flash ' . $t . '">' . bb_h($m) . '</div>';
}
function csrf_ok(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        throw new RuntimeException('Platnost stránky vypršela, zkuste to znovu.');
    }
}
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$csrfIn = '<input type="hidden" name="csrf" value="' . $h($_SESSION['csrf'] ?? '') . '">';

/* ---- ceník ---- */
if (isset($_GET['cenik'])) {
    $prices = bb_prices($cfg);
    $NAMES = ['standard' => ['Standard', '2 hodiny · libovolný blok'], 'gold' => ['Gold Time', 'Pátek a sobota 22–24 h']];
    if ($isPost) {
        try {
            csrf_ok();
            $new = [];
            foreach ($prices as $k => $v) {
                $raw = preg_replace('/[^\d]/', '', (string) ($_POST['cena_' . $k] ?? ''));
                $n = (int) $raw;
                if ($raw === '' || $n < 100 || $n > 100000) {
                    throw new RuntimeException('Zadejte platnou cenu pro ' . $NAMES[$k][0] . ' (v Kč, celé číslo).');
                }
                $new[$k] = $n;
            }
            bb_save_prices($new);
            $_SESSION['flash'] = ['ok', 'Ceník uložen. Na webu se nové ceny projeví do minuty.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['err', 'Nepovedlo se: ' . $e->getMessage()];
        }
        header('Location: ' . $self . '?cenik=1');
        exit;
    }
    $out = menu('cenik') . '<div class="wrap"><h1 class="ph">Ceník</h1>' . flash() . '<form method="post" class="prices">' . $csrfIn;
    foreach ($prices as $k => $v) {
        $out .= '<label class="o' . ($k === 'gold' ? ' gold' : '') . ' pc"><span class="ttl">' . $h($NAMES[$k][0]) . '<em>' . $h($NAMES[$k][1]) . '</em></span>
<span class="pin"><input type="text" inputmode="numeric" pattern="[0-9 ]*" name="cena_' . $h($k) . '" value="' . $h((string) $v) . '" required><b>Kč</b></span></label>';
    }
    $out .= '<button class="btn" type="submit">Uložit ceník</button>
<p class="meta" style="margin-top:14px">Cena se změní všude najednou: v ceníku na webu, u dárkových poukazů, v objednávce i na nových fakturách. Už vystavené faktury a objednávky si nechají původní cenu.</p></form></div>';
    page('Ceník', $out);
}

/* ---- oznámení na web ---- */
if (isset($_GET['oznameni'])) {
    $n = bb_notice();
    if ($isPost) {
        try {
            csrf_ok();
            $text = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['text'] ?? '')) ?? '');
            $do = (string) ($_POST['do'] ?? '');
            if ($do !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $do)) {
                $do = '';
            }
            $on = !empty($_POST['zapnuto']);
            if ($on && $text === '') {
                throw new RuntimeException('Napište text oznámení.');
            }
            bb_setting_set('oznameni', ['zapnuto' => $on, 'text' => mb_substr($text, 0, 220), 'do' => $do]);
            $_SESSION['flash'] = ['ok', $on ? 'Oznámení je zapnuté. Na webu se objeví do minuty.' : 'Oznámení je vypnuté.'];
        } catch (Throwable $e) {
            $_SESSION['flash'] = ['err', 'Nepovedlo se: ' . $e->getMessage()];
        }
        header('Location: ' . $self . '?oznameni=1');
        exit;
    }
    $live = bb_notice_public();
    $state = $live ? '<span class="badge b-zaplaceno">Zobrazuje se na webu</span>' : ($n['zapnuto'] && $n['do'] !== '' ? '<span class="badge b-storno">Platnost vypršela</span>' : '<span class="badge b-storno">Vypnuto</span>');
    $out = menu('oznameni') . '<div class="wrap"><h1 class="ph">Oznámení na webu ' . $state . '</h1>' . flash() . '
<form method="post" class="o notice">' . $csrfIn . '
<label class="lbl">Text oznámení<textarea name="text" rows="3" maxlength="220" placeholder="Např. Od 20. do 27. 10. máme zavřeno. Poukazy objednávejte dál.">' . $h($n['text']) . '</textarea></label>
<div class="nrow"><label class="lbl">Zobrazovat do (nepovinné)<input type="date" name="do" value="' . $h($n['do']) . '"></label>
<label class="sw"><input type="checkbox" name="zapnuto" value="1"' . ($n['zapnuto'] ? ' checked' : '') . '><i></i>Zobrazit na webu</label></div>
<button class="btn" type="submit">Uložit oznámení</button>
<p class="meta">Oznámení se ukáže nahoře na webu pod menu. Návštěvník ho může zavřít křížkem. Když text změníte, ukáže se mu znovu. Po zadaném datu zmizí samo.</p>
</form></div>';
    page('Oznámení', $out);
}

/* ---- statistiky prodeje ---- */
if (isset($_GET['statistiky'])) {
    $MES = [1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
    $sold = ['zaplaceno', 'vyrizeno'];
    $wait = ['ceka', 'nova', 'bez_faktury'];
    $z = fn() => ['standard' => 0, 'gold' => 0, 'ks' => 0, 'kc' => 0];
    $tot = $z(); $year = $z(); $month = $z(); $pend = $z();
    $byMonth = [];
    $y = (int) date('Y'); $ym = date('Y-m');
    foreach (bb_list_orders($DATA) as $o) {
        $st = $o['status'] ?? '';
        $k = ($o['kind'] ?? '') === 'gold' ? 'gold' : 'standard';
        $kc = (int) ($o['product']['price'] ?? 0);
        if (in_array($st, $wait, true)) {
            $pend[$k]++; $pend['ks']++; $pend['kc'] += $kc;
            continue;
        }
        if (!in_array($st, $sold, true)) {
            continue;
        }
        $ts = (int) ($o['paid_at'] ?? $o['done_at'] ?? $o['created'] ?? time());
        $add = function (array &$a) use ($k, $kc) { $a[$k]++; $a['ks']++; $a['kc'] += $kc; };
        $add($tot);
        if ((int) date('Y', $ts) === $y) $add($year);
        if (date('Y-m', $ts) === $ym) $add($month);
        $key = date('Y-m', $ts);
        $byMonth[$key] = $byMonth[$key] ?? $z();
        $add($byMonth[$key]);
    }
    $card = function (string $t, array $a, string $cls = '') use ($h) {
        return '<div class="sc ' . $cls . '"><span>' . $t . '</span><b>' . $h(bb_money((float) $a['kc'])) . '</b><small>' . $a['ks'] . ' ks · Standard ' . $a['standard'] . ' · Gold ' . $a['gold'] . '</small></div>';
    };
    $out = menu('statistiky') . '<div class="wrap"><h1 class="ph">Statistiky prodeje</h1>
<div class="scards">' . $card('Tento měsíc', $month, 'hi') . $card('Letos', $year) . $card('Celkem', $tot) . $card('Čeká na zaplacení', $pend, 'wait') . '</div>';
    if ($tot['ks'] > 0) {
        $gPct = (int) round($tot['gold'] / $tot['ks'] * 100);
        $out .= '<div class="o split"><div class="ttl">Poměr poukazů</div><div class="bar2"><i style="width:' . (100 - $gPct) . '%"></i><i class="g" style="width:' . $gPct . '%"></i></div>
<div class="leg"><span>Standard ' . $tot['standard'] . ' ks (' . (100 - $gPct) . ' %)</span><span class="g">Gold Time ' . $tot['gold'] . ' ks (' . $gPct . ' %)</span></div></div>';
    }
    $out .= '<div class="o"><div class="ttl" style="margin-bottom:10px">Po měsících <em>posledních 12 měsíců</em></div><table class="st"><thead><tr><th>Měsíc</th><th>Standard</th><th>Gold</th><th class="r">Tržba</th><th class="barc"></th></tr></thead><tbody>';
    $max = 1;
    for ($i = 0; $i < 12; $i++) {
        $key = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
        $max = max($max, $byMonth[$key]['kc'] ?? 0);
    }
    for ($i = 0; $i < 12; $i++) {
        $t = strtotime(date('Y-m-01') . " -$i month");
        $a = $byMonth[date('Y-m', $t)] ?? $z();
        $out .= '<tr' . ($a['ks'] ? '' : ' class="zero"') . '><td>' . $MES[(int) date('n', $t)] . ' ' . date('Y', $t) . '</td><td>' . $a['standard'] . '</td><td class="g">' . $a['gold'] . '</td><td class="r"><b>' . $h(bb_money((float) $a['kc'])) . '</b></td><td class="barc"><i style="width:' . round($a['kc'] / $max * 100) . '%"></i></td></tr>';
    }
    $out .= '</tbody></table></div>
<p class="meta">Počítají se zaplacené a vyzvednuté objednávky (podle data platby). Stornované se nepočítají. Částky jsou za cenu, za kterou se objednávka prodala.</p></div>';
    page('Statistiky', $out);
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
            bb_ensure_voucher($cfg, $DATA, $o);
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
    $SL = ['ceka' => 'čeká na platbu', 'k_odeslani' => 'zaplaceno – odeslat poštou', 'platny' => 'platný', 'pouzity' => 'použitý', 'storno' => 'storno'];
    fputcsv($out, ['Objednáno', 'Faktura / VS', 'Stav', 'Splatnost', 'Zaplaceno', 'Poukaz', 'Cena', 'Doručení', 'Kód poukazu', 'Poukaz odeslán', 'Použit', 'Poznámka k použití', 'Jméno', 'Telefon', 'E-mail', 'Adresa', 'Firma', 'IČO'], ';');
    foreach (bb_list_orders($DATA) as $o) {
        $c = $o['customer'];
        $d = fn($k, $f = 'j. n. Y') => !empty($o[$k]) ? date($f, (int) $o[$k]) : '';
        fputcsv($out, [
            $d('created', 'j. n. Y H:i'), $o['no'] ?? '', $SL[bb_voucher_state($o)] ?? $o['status'], $d('due'), $d('paid_at'),
            $o['kind'] === 'gold' ? 'Gold Time' : 'Standard', $o['product']['price'], bb_delivery_label($o), $o['voucher'] ?? '',
            $d('voucher_sent_at'), $d('used_at', 'j. n. Y H:i'), $o['used_note'] ?? '',
            $c['name'], $c['phone'], $c['email'], $c['addr'] ?? '', $c['company'] ?? '', $c['ico'] ?? '',
        ], ';');
    }
    exit;
}

/* ---- akce ---- */
if ($isPost) {
    $back = $self . '?f=' . urlencode((string) ($_POST['f'] ?? 'aktivni')) . (!empty($_POST['kodq']) ? '&kod=' . urlencode((string) $_POST['kodq']) : '');
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
        $who = bb_who($o);
        $email = $o['customer']['email'];
        switch ($a) {
            case 'zaplaceno':
                $dl = bb_delivery($o);
                if ($dl === 'email') {
                    bb_ensure_voucher($cfg, $DATA, $o);
                }
                $o['paid_at'] = $o['paid_at'] ?? time();
                bb_send_paid($cfg, $o);
                $o['status'] = 'zaplaceno';
                $o['paid_mail_at'] = time();
                if ($dl === 'email') {
                    $o['voucher_sent_at'] = time();
                }
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', $dl === 'email' ? "Platba potvrzena – poukaz {$o['voucher']} odešel e-mailem na $email."
                    : ($dl === 'posta' ? "Platba potvrzena – zákazníkovi odešlo potvrzení. Teď přidělte číslo poukazu a odešlete ho poštou."
                    : "Platba potvrzena – potvrzení odešlo na $email.")];
                break;

            case 'posta':   // číslo poukazu + odesláno poštou
                $code = bb_norm_code((string) ($_POST['kod'] ?? ''));
                $sent = !empty($_POST['odeslano']);
                if ($code === '') {
                    throw new RuntimeException('Zadejte číslo poukazu.');
                }
                if (mb_strlen($code) > 20) {
                    throw new RuntimeException('Číslo poukazu může mít nejvýš 20 znaků.');
                }
                if ($dup = bb_find_voucher($DATA, $code, $o['id'])) {
                    throw new RuntimeException("Poukaz $code už je přidělený k jiné objednávce (" . bb_who($dup) . (!empty($dup['no']) ? ', faktura ' . $dup['no'] : '') . ').');
                }
                $o['voucher'] = $code;
                $msg = "Číslo poukazu $code uloženo.";
                if ($sent && empty($o['voucher_sent_at'])) {
                    $o['voucher_sent_at'] = time();
                    $msg = "Poukaz $code označen jako odeslaný poštou.";
                    if (!empty($_POST['notify'])) {
                        try {
                            bb_send_posted($cfg, $o);
                            $o['posted_mail_at'] = time();
                            $msg .= " Zákazníkovi odešel e-mail ($email).";
                        } catch (Throwable $e) {
                            $msg .= ' E-mail zákazníkovi se nepodařilo odeslat.';
                        }
                    }
                } elseif (!$sent && !empty($o['voucher_sent_at'])) {
                    unset($o['voucher_sent_at']);
                    $msg = "Poukaz $code: odeslání zrušeno.";
                }
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', $msg];
                break;

            case 'pouzit':
                $state = bb_voucher_state($o);
                if ($state === 'pouzity') {
                    throw new RuntimeException('Poukaz ' . ($o['voucher'] ?? '') . ' už byl použit ' . date('j. n. Y H:i', (int) $o['used_at']) . '.');
                }
                if ($state === 'ceka' || $state === 'storno') {
                    throw new RuntimeException('Poukaz není zaplacený – nelze ho uplatnit.');
                }
                if (empty($o['voucher'])) {
                    throw new RuntimeException('Objednávka nemá číslo poukazu.');
                }
                $o['used_at'] = time();
                $o['used_note'] = mb_substr(trim((string) ($_POST['pozn'] ?? '')), 0, 120);
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Poukaz {$o['voucher']} je označený jako POUŽITÝ (" . date('j. n. Y H:i') . '). Znovu už ho uplatnit nepůjde.'];
                break;

            case 'vratit':
                unset($o['used_at'], $o['used_note']);
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', 'Použití poukazu ' . ($o['voucher'] ?? '') . ' zrušeno – poukaz je znovu platný.'];
                break;

            case 'poukaz_znovu':
                bb_send_paid($cfg, $o);
                $_SESSION['flash'] = ['ok', "Potvrzení" . (bb_delivery($o) === 'email' ? ' a poukaz' : '') . " odeslány znovu na $email."];
                break;

            case 'faktura_znovu':
                bb_send_invoice_customer($cfg, $DATA, $o);
                $o['sent_at'] = time();
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Faktura {$o['no']} znovu odeslána na $email."];
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

            case 'upravit':
                $c = &$o['customer'];
                $c['name'] = trim((string) ($_POST['jmeno'] ?? ''));
                $c['phone'] = trim((string) ($_POST['telefon'] ?? ''));
                $c['email'] = trim((string) ($_POST['email'] ?? ''));
                $c['addr'] = trim((string) ($_POST['adresa'] ?? ''));
                $c['company'] = trim((string) ($_POST['firma'] ?? ''));
                unset($c);
                if (bb_has_voucher($o) && in_array((string) ($_POST['doruceni'] ?? ''), ['email', 'posta'], true)) {
                    $o['delivery'] = (string) $_POST['doruceni'];
                }
                if (!filter_var($o['customer']['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Neplatný e-mail.');
                }
                if (bb_delivery($o) === 'posta' && (mb_strlen($o['customer']['name']) < 2 || mb_strlen($o['customer']['addr']) < 5)) {
                    throw new RuntimeException('Pro poukaz poštou vyplňte jméno a adresu.');
                }
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', 'Údaje objednávky – ' . bb_who($o) . ' – uloženy.'];
                break;

            case 'storno':
                $o['prev_status'] = $o['status'];
                $o['status'] = 'storno';
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Objednávka – $who – stornována."];
                break;

            case 'obnovit':
                $o['status'] = $o['prev_status'] ?? (($o['way'] ?? '') === 'faktura' ? 'ceka' : 'nova');
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "Objednávka – $who – obnovena."];
                break;

            default:
                throw new RuntimeException('Neznámá akce.');
        }
    } catch (Throwable $e) {
        @file_put_contents($DATA . '/chyby.log', date('c') . ' sprava ' . $e->getMessage() . "\n", FILE_APPEND);
        $msg = $e->getMessage();
        if ($e instanceof PDOException && strpos($msg, 'Duplicate') !== false) {
            $msg = 'Toto číslo poukazu už je v databázi použité u jiné objednávky.';
        }
        $_SESSION['flash'] = ['err', 'Nepovedlo se: ' . $msg];
    }
    header('Location: ' . $back);
    exit;
}

/* ---- výpis ---- */
$all = bb_list_orders($DATA);
$f = (string) ($_GET['f'] ?? 'aktivni');
$q = trim((string) ($_GET['q'] ?? ''));
$kodq = bb_norm_code((string) ($_GET['kod'] ?? ''));

$TAB = [
    'aktivni' => ['K vyřízení', fn($s) => in_array($s, ['ceka', 'k_odeslani'], true)],
    'platne'  => ['Platné poukazy', fn($s) => $s === 'platny'],
    'pouzite' => ['Použité', fn($s) => $s === 'pouzity'],
    'vse'     => ['Vše', fn($s) => true],
];
if (!isset($TAB[$f])) {
    $f = 'aktivni';
}
$counts = array_fill_keys(array_keys($TAB), 0);
foreach ($all as $o) {
    $s = bb_voucher_state($o);
    foreach ($TAB as $k => $t) {
        if ($t[1]($s)) $counts[$k]++;
    }
}
$list = array_values(array_filter($all, function ($o) use ($f, $TAB, $q) {
    if (!$TAB[$f][1](bb_voucher_state($o))) return false;
    if ($q !== '') {
        $hay = mb_strtolower(implode(' ', [$o['no'] ?? '', $o['voucher'] ?? '', $o['customer']['name'], $o['customer']['email'], $o['customer']['phone'], $o['customer']['addr'] ?? '']));
        if (mb_strpos($hay, mb_strtolower($q)) === false) return false;
    }
    return true;
}));
// v záložce K vyřízení nejdřív to, co čeká na odeslání poštou
if ($f === 'aktivni') {
    usort($list, fn($a, $b) => [bb_voucher_state($a) !== 'k_odeslani', -($a['created'] ?? 0)] <=> [bb_voucher_state($b) !== 'k_odeslani', -($b['created'] ?? 0)]);
}

$csrf = $_SESSION['csrf'] ?? '';
$hid = function (array $o, string $a) use ($csrf, $f, $h, $kodq) {
    return '<input type="hidden" name="csrf" value="' . $h($csrf) . '"><input type="hidden" name="id" value="' . $h($o['id']) . '"><input type="hidden" name="a" value="' . $a . '"><input type="hidden" name="f" value="' . $h($f) . '">' . ($kodq !== '' ? '<input type="hidden" name="kodq" value="' . $h($kodq) . '">' : '');
};
$btn = function (array $o, string $a, string $label, string $cls = 'ghost', string $confirm = '') use ($hid, $h) {
    return '<form method="post"' . ($confirm ? ' onsubmit="return confirm(\'' . $h($confirm) . '\')"' : '') . '>' . $hid($o, $a) . '<button class="btn ' . $cls . '" type="submit">' . $label . '</button></form>';
};
$SBADGE = [
    'ceka' => ['Čeká na platbu', 'b-ceka'],
    'k_odeslani' => ['Odeslat poštou', 'b-post'],
    'platny' => ['Platný poukaz', 'b-zaplaceno'],
    'pouzity' => ['Použitý', 'b-used'],
    'storno' => ['Storno', 'b-storno'],
];
$dt = fn($ts, $f = 'j. n. Y') => $ts ? date($f, (int) $ts) : '';

/* jedna karta objednávky */
$card = function (array $o, bool $hi = false) use ($h, $btn, $hid, $SBADGE, $dt, $f) {
    $gold = ($o['kind'] ?? '') === 'gold';
    $c = $o['customer'];
    $st = (string) ($o['status'] ?? '');
    $vs = bb_voucher_state($o);
    $hasV = bb_has_voucher($o);
    $dl = bb_delivery($o);
    $id = $o['id'];
    [$bl, $bc] = $SBADGE[$vs] ?? [$st, 'b-storno'];
    if ($st === 'bez_faktury') { $bl = 'Bez faktury'; $bc = 'b-bez_faktury'; }
    if ($st === 'nova') { $bl = 'Osobní odběr (starší)'; $bc = 'b-nova'; }

    $out = '<article class="o' . ($gold ? ' gold' : '') . ($hi ? ' hi' : '') . '" id="o-' . $h($id) . '">';
    // hlavička
    $out .= '<div class="top"><div><div class="ttl">' . ($gold ? 'Gold Time' : 'Standard')
        . '<span class="badge ' . $bc . '">' . $h($bl) . '</span></div>'
        . '<div class="sub">' . $h($c['name'] ?: $c['email']) . (!empty($o['no']) ? ' · faktura ' . $h($o['no']) : '') . '</div></div>'
        . '<div class="price">' . $h(bb_money((float) $o['product']['price'])) . '</div></div>';

    // průběh
    $steps = [];
    $steps[] = ['Objednáno', $dt($o['created'] ?? 0), true];
    $paid = in_array($st, ['zaplaceno', 'vyrizeno'], true);
    $due = !empty($o['due']) && !$paid ? 'splatnost ' . $dt($o['due']) . ((int) $o['due'] < time() ? ' · po splatnosti' : '') : '';
    $steps[] = ['Zaplaceno', $paid ? $dt($o['paid_at'] ?? 0) : $due, $paid];
    if ($hasV) {
        $steps[] = ['Odesláno', $dt($o['voucher_sent_at'] ?? ($dl !== 'posta' && $paid ? ($o['paid_at'] ?? 0) : 0)), !empty($o['voucher_sent_at']) || ($dl !== 'posta' && $paid)];
        $steps[] = ['Použito', $dt($o['used_at'] ?? 0), !empty($o['used_at'])];
    }
    if ($st !== 'storno') {
        $out .= '<ol class="prog">';
        $cur = false;
        foreach ($steps as [$t, $d, $done]) {
            $cls = $done ? 'done' : (!$cur ? 'cur' : '');
            if (!$done) $cur = true;
            $out .= '<li class="' . $cls . '"><b>' . $h($t) . '</b>' . ($d !== '' ? '<small>' . $h($d) . '</small>' : '') . '</li>';
        }
        $out .= '</ol>';
    }

    // údaje
    $out .= '<div class="grid">'
        . '<div><span>Kontakt</span><a href="tel:' . $h(preg_replace('/\s+/', '', $c['phone'])) . '">' . $h($c['phone']) . '</a><br><a href="mailto:' . $h($c['email']) . '">' . $h($c['email']) . '</a></div>'
        . '<div><span>Doručení poukazu</span>' . ($hasV ? ($dl === 'posta' ? '<b>Poštou</b><br>' . $h($c['name']) . '<br>' . $h($c['addr'] ?? '') : '<b>' . $h(ucfirst(bb_delivery_label($o))) . '</b>') : '<i class="mut">bez poukazu</i>') . '</div>'
        . ($hasV ? '<div><span>Číslo poukazu</span>' . (!empty($o['voucher']) ? '<b class="code">' . $h($o['voucher']) . '</b>' : '<i class="mut">zatím nepřiděleno</i>') . '</div>' : '')
        . ($c['company'] ? '<div><span>Firma</span>' . $h($c['company']) . ($c['ico'] ? '<br>IČ ' . $h($c['ico']) : '') . '</div>' : '')
        . '</div>';
    if (!empty($o['used_at'])) {
        $out .= '<div class="usedbox">✔ Použito ' . $h($dt($o['used_at'], 'j. n. Y H:i')) . ($o['used_note'] ?? '' ? ' · ' . $h($o['used_note']) : '') . '</div>';
    }

    // hlavní akce podle stavu
    $out .= '<div class="act">';
    if ($st === 'ceka') {
        $lbl = !$hasV ? 'Platba přišla – poslat potvrzení' : ($dl === 'posta' ? 'Platba přišla' : 'Platba přišla – poslat poukaz e-mailem');
        $out .= $btn($o, 'zaplaceno', $lbl, $gold ? 'gold' : '', 'Potvrdit platbu ' . bb_money((float) $o['product']['price']) . ' od ' . bb_who($o) . '?');
    }
    if ($st === 'bez_faktury') {
        $out .= $btn($o, 'vystavit', 'Vystavit a poslat fakturu', $gold ? 'gold' : '', 'Vystavit fakturu a poslat ji na ' . $c['email'] . '?');
    }
    if ($vs === 'platny' && !empty($o['voucher'])) {
        $out .= '<form method="post" class="useform" onsubmit="return confirm(\'Označit poukaz ' . $h($o['voucher']) . ' jako POUŽITÝ? Znovu už ho uplatnit nepůjde.\')">' . $hid($o, 'pouzit')
            . '<input type="text" name="pozn" maxlength="120" placeholder="poznámka (např. termín 24. 10.)"><button class="btn ' . ($gold ? 'gold' : '') . '" type="submit">Uplatnit – poukaz použit</button></form>';
    }
    if ($vs === 'pouzity') {
        $out .= $btn($o, 'vratit', 'Zrušit použití', 'ghost', 'Opravdu vrátit poukaz ' . ($o['voucher'] ?? '') . ' do stavu platný?');
    }
    $out .= '</div>';

    // pošta: číslo poukazu + odesláno
    $postbox = '';
    if ($hasV && $dl === 'posta' && $paid && $vs !== 'pouzity') {
        $sug = !empty($o['voucher']) ? $o['voucher'] : '';
        $sent = !empty($o['voucher_sent_at']);
        $postbox = '<form method="post" class="postbox' . ($sent ? ' sent' : '') . '">' . $hid($o, 'posta')
            . '<div class="pbh">' . ($sent ? 'Poukaz odeslán poštou ' . $h($dt($o['voucher_sent_at'])) : 'Poukaz poštou – přidělte číslo a odešlete') . '</div>'
            . '<label class="lbl">Číslo poukazu<input type="text" name="kod" value="' . $h($sug) . '" placeholder="např. ' . $h($o['vs'] ?? '0001') . '" maxlength="20" required autocomplete="off"></label>'
            . '<label class="chk"><input type="checkbox" name="odeslano" value="1"' . ($sent ? ' checked' : '') . '><span>Poukaz odeslán poštou</span></label>'
            . ($sent ? '' : '<label class="chk"><input type="checkbox" name="notify" value="1" checked><span>poslat zákazníkovi e-mail o odeslání</span></label>')
            . '<button class="btn ' . ($gold ? 'gold' : '') . '" type="submit">Uložit</button>'
            . '<p class="meta">Číslo musí být jedinečné – stejné číslo nepůjde přidělit dvakrát. Poukaz lze vytisknout i z PDF v „Další možnosti“.</p></form>';
        if (!$sent) {
            $out .= $postbox;
            $postbox = '';
        }
    }

    // vedlejší akce
    $sec = [];
    if (!empty($o['no'])) $sec[] = '<a class="btn ghost" href="?dl=faktura&id=' . urlencode($id) . '" target="_blank">Faktura PDF</a>';
    if ($hasV && ($paid || !empty($o['voucher']))) $sec[] = '<a class="btn ghost" href="?dl=poukaz&id=' . urlencode($id) . '" target="_blank">Poukaz PDF</a>';
    if ($st === 'ceka') $sec[] = $btn($o, 'faktura_znovu', 'Poslat fakturu znovu', 'ghost', 'Poslat fakturu znovu na ' . $c['email'] . '?');
    if ($paid && $dl === 'email' && $vs !== 'pouzity') $sec[] = $btn($o, 'poukaz_znovu', 'Poslat poukaz znovu', 'ghost', 'Poslat potvrzení a poukaz znovu na ' . $c['email'] . '?');
    if ($st === 'storno') $sec[] = $btn($o, 'obnovit', 'Obnovit');
    elseif (!$paid) $sec[] = $btn($o, 'storno', 'Storno', 'ghost del', 'Opravdu stornovat objednávku?');

    $out .= '<details class="more"><summary>Další možnosti</summary><div class="act">' . implode('', $sec) . '</div>' . $postbox
        . '<form method="post" class="editform">' . $hid($o, 'upravit')
        . '<label>Jméno a příjmení<input type="text" name="jmeno" value="' . $h($c['name']) . '"></label>'
        . '<label>Telefon<input type="text" name="telefon" value="' . $h($c['phone']) . '" required></label>'
        . '<label>E-mail<input type="email" name="email" value="' . $h($c['email']) . '" required></label>'
        . '<label class="full">Adresa' . ($dl === 'posta' ? ' pro doručení poukazu' : '') . '<input type="text" name="adresa" value="' . $h($c['addr'] ?? '') . '" placeholder="ulice, PSČ město"></label>'
        . '<label>Firma<input type="text" name="firma" value="' . $h($c['company'] ?? '') . '"></label>'
        . ($hasV ? '<label>Doručení poukazu<select name="doruceni"><option value="email"' . ($dl === 'email' ? ' selected' : '') . '>e-mailem</option><option value="posta"' . ($dl === 'posta' ? ' selected' : '') . '>poštou</option></select></label>' : '')
        . '<button class="btn ghost full" type="submit">Uložit změny</button></form>';
    $meta = [];
    if (!empty($o['sent_at'])) $meta[] = 'faktura odeslána ' . date('j. n. H:i', (int) $o['sent_at']);
    if (!empty($o['paid_mail_at'])) $meta[] = 'potvrzení o platbě ' . date('j. n. H:i', (int) $o['paid_mail_at']);
    if (!empty($o['posted_mail_at'])) $meta[] = 'e-mail o odeslání poštou ' . date('j. n. H:i', (int) $o['posted_mail_at']);
    if ($meta) $out .= '<div class="meta">' . $h(implode(' · ', $meta)) . '</div>';
    $out .= '</details></article>';
    return $out;
};

$out = menu('objednavky') . '<div class="wrap">';

/* ověření / uplatnění poukazu */
$out .= '<form class="check" method="get"><input type="hidden" name="f" value="' . $h($f) . '"><div class="ch-in"><label for="kod">Ověřit / uplatnit poukaz</label>'
    . '<div class="ch-row"><input id="kod" type="text" name="kod" value="' . $h($kodq) . '" placeholder="číslo poukazu" autocomplete="off" inputmode="text"><button class="btn" type="submit">Ověřit</button></div></div></form>';
$out .= flash();
if ($kodq !== '') {
    $found = bb_find_voucher($DATA, $kodq);
    if (!$found) {
        $out .= '<div class="verdict bad"><b>Poukaz ' . $h($kodq) . ' neexistuje.</b><span>Zkontrolujte číslo – v objednávkách takový poukaz není.</span></div>';
    } else {
        $vs = bb_voucher_state($found);
        $V = [
            'platny' => ['ok', 'Poukaz ' . $found['voucher'] . ' je PLATNÝ', 'Můžete ho uplatnit tlačítkem níže.'],
            'k_odeslani' => ['ok', 'Poukaz ' . $found['voucher'] . ' je zaplacený', 'Ještě není označený jako odeslaný poštou.'],
            'pouzity' => ['bad', 'Poukaz ' . $found['voucher'] . ' UŽ BYL POUŽIT', 'Uplatněn ' . date('j. n. Y H:i', (int) ($found['used_at'] ?? 0)) . (($found['used_note'] ?? '') !== '' ? ' (' . rtrim((string) $found['used_note'], '. ') . ')' : '') . '. Znovu ho přijmout nelze.'],
            'ceka' => ['warn', 'Poukaz ' . $found['voucher'] . ' NENÍ ZAPLACENÝ', 'Faktura zatím nebyla uhrazena.'],
            'storno' => ['bad', 'Poukaz ' . $found['voucher'] . ' je STORNOVANÝ', 'Objednávka byla zrušena.'],
        ][$vs];
        $out .= '<div class="verdict ' . $V[0] . '"><b>' . $h($V[1]) . '</b><span>' . $h($V[2]) . '</span></div>' . $card($found, true);
    }
    $out .= '<p class="meta" style="margin:-4px 0 22px"><a href="?f=' . $h($f) . '">× zavřít ověření</a></p>';
}

/* záložky + hledání */
$out .= '<header><div class="tabs">';
foreach ($TAB as $k => $t) {
    $out .= '<a class="' . ($f === $k ? 'on' : '') . '" href="?f=' . $k . '">' . $t[0] . '<b>' . $counts[$k] . '</b></a>';
}
$out .= '</div><form class="search" method="get"><input type="hidden" name="f" value="' . $h($f) . '"><input type="search" name="q" value="' . $h($q) . '" placeholder="Hledat jméno, e-mail, VS, poukaz…"></form>
<a href="?export=csv" class="mut" style="font-size:13px">Export do Excelu</a></header>';

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
    $out .= $card($o);
}
$out .= '</div>';
page('Objednávky', $out);
