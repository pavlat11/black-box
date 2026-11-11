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
/* ---- pracovní seznam ---- */
:root{--c-late:#ff6b8a;--c-bezf:#ff8f6b;--c-post:#5cc8ff;--c-ceka:#ffb86b;--c-ok:#5fd39a;--c-used:#8f89b8;--c-storno:#5d5888}
.wrap{max-width:1280px}
.o,.sc,.check,.login{border-radius:10px}
.btn{border-radius:8px}.btn.sm{padding:8px 12px;font-size:12.5px}
.tabs a,.search input{border-radius:8px}
header{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:10px}
.tools{display:flex;gap:8px}
.check{padding:12px 14px;display:block}
.check label{margin-bottom:6px}
.ch-row input,.ch-row .btn{border-radius:8px}.ch-row input{font-size:16px;padding:10px 14px}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:0 0 14px}
.kpi{display:block;text-decoration:none;color:var(--txt);background:var(--card);border:1px solid var(--line);border-left:4px solid var(--mut);border-radius:10px;padding:10px 14px}
.kpi span{display:block;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--mut)}
.kpi b{font-size:26px;line-height:1.2;margin-right:8px}
.kpi small{color:var(--mut);font-size:12.5px}
.kpi.k-wait{border-left-color:var(--c-ceka)}.kpi.k-wait b{color:var(--c-ceka)}
.kpi.k-bad{border-left-color:var(--c-late)}.kpi.k-bad b{color:var(--c-late)}
.kpi.k-post{border-left-color:var(--c-post)}.kpi.k-post b{color:var(--c-post)}
.kpi.k-ok{border-left-color:var(--c-ok)}.kpi.k-ok b{color:var(--c-ok)}
.kpi.nil{opacity:.55}.kpi.nil b{color:var(--mut)}
.kpi.k-bad:not(.nil){background:linear-gradient(90deg,rgba(255,107,138,.14),var(--card) 70%)}
.kpi:hover{border-color:var(--acc)}
/* záložky */
.tabs a.tb{display:inline-flex;align-items:center;gap:6px}
.tabs a.tb::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--c,var(--mut))}
.tabs a.st-todo::before,.tabs a.st-all::before{display:none}
.tabs a.tb b{background:rgba(255,255,255,.08);border-radius:999px;padding:1px 7px;margin-left:2px;font-size:12px;opacity:1}
.tabs a.tb.zero{opacity:.5}
.tabs a.tb.hot:not(.on){border-color:var(--c-post);color:var(--c-post)}
.tabs a.tb.hot b{background:var(--c-post);color:#0b0926}
.tabs a.tb.on{background:var(--c,var(--acc2));border-color:var(--c,var(--acc2));color:#0b0926}
.tabs a.st-todo.on,.tabs a.st-all.on{color:#fff}
.tabs a.tb.on::before{background:#0b0926}
/* seznam */
.list{background:var(--card);border:1px solid var(--line);border-radius:10px;overflow:hidden}
.list.solo{margin-bottom:10px}
.lh,.rs{display:grid;grid-template-columns:165px minmax(170px,1.3fr) 330px 120px minmax(90px,.6fr) 170px 18px;gap:14px;align-items:center;padding:10px 14px}
.lh{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--mut);background:#0a0824;border-bottom:1px solid var(--line)}
.gh{--c:var(--mut);display:flex;justify-content:space-between;align-items:center;padding:8px 14px;background:linear-gradient(90deg,color-mix(in srgb,var(--c) 20%,transparent),transparent 60%);border-top:1px solid var(--line);border-left:4px solid var(--c)}
.gh b{color:var(--c);font-size:12px;letter-spacing:.1em;text-transform:uppercase}
.gh span{color:var(--mut);font-size:12.5px;font-variant-numeric:tabular-nums}
.st-late{--c:var(--c-late)}.st-bezf{--c:var(--c-bezf)}.st-post{--c:var(--c-post)}.st-ceka{--c:var(--c-ceka)}.st-ok{--c:var(--c-ok)}.st-used{--c:var(--c-used)}.st-storno{--c:var(--c-storno)}
.rw{border-top:1px solid rgba(42,33,96,.7)}
.rs{cursor:pointer;list-style:none;font-size:14px;box-shadow:inset 4px 0 0 var(--c)}
.rs::-webkit-details-marker{display:none}
.rs:hover{background:rgba(185,140,255,.06)}
.rs>span{min-width:0}
.rs small{display:block;color:var(--mut);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rs b{font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pill{display:inline-flex;align-items:center;gap:7px;padding:5px 11px 5px 6px;border-radius:999px;font-size:12.5px;font-weight:800;letter-spacing:.02em;white-space:nowrap;color:var(--c);background:color-mix(in srgb,var(--c) 16%,transparent);border:1px solid color-mix(in srgb,var(--c) 45%,transparent)}
.pill i{font-style:normal;display:grid;place-items:center;width:20px;height:20px;border-radius:50%;background:var(--c);color:#0b0926;font-size:12px;font-weight:900}
.st-late .pill{animation:pulse 2s infinite}
@keyframes pulse{50%{box-shadow:0 0 0 4px color-mix(in srgb,var(--c-late) 25%,transparent)}}
.st-used .rs,.st-storno .rs{opacity:.6}
.st-storno .c-cus b{text-decoration:line-through}
.c-cus small{color:#cfc8f5}
.st-late .c-cus small{color:var(--c-late);font-weight:700}
.st-post .c-cus small,.st-bezf .c-cus small{color:var(--c);font-weight:700}
.tg{font-size:12px;font-weight:700;color:var(--acc)}
details.row.gold .tg{color:var(--gold)}
.c-typ .pr{font-size:14px;font-variant-numeric:tabular-nums;margin:1px 0}
.c-vou .code{font-size:14px}
.c-act{text-align:right}
/* časová osa */
.tl{list-style:none;margin:0;padding:0;display:grid;grid-auto-flow:column;grid-auto-columns:1fr}
.tl li{position:relative;padding-top:15px;text-align:center;min-width:0}
.tl li::before{content:"";position:absolute;top:2px;left:50%;width:10px;height:10px;margin-left:-5px;border-radius:50%;background:#0b0926;border:2px solid #3a3175;z-index:1;box-sizing:border-box}
.tl li+li::after{content:"";position:absolute;top:6px;right:50%;width:100%;height:2px;background:#3a3175}
.tl li.d::before{background:var(--c);border-color:var(--c)}
.tl li.d+li.d::after{background:var(--c)}
.tl li.d+li.c::after{background:repeating-linear-gradient(90deg,color-mix(in srgb,var(--c) 55%,transparent) 0 4px,transparent 4px 8px)}
.tl li.c::before{background:#0b0926;border-color:var(--c);box-shadow:0 0 0 3px color-mix(in srgb,var(--c) 22%,transparent)}
.tl li.c b{font-weight:600}
.tl li.x::before{border-color:var(--c-late);background:var(--c-late)}
.tl b{display:block;font-size:10.5px!important;font-weight:700;letter-spacing:.03em;color:var(--mut);white-space:nowrap}
.tl small{font-size:11.5px!important;color:#cfc8f5!important;font-variant-numeric:tabular-nums}
.tl li.d b{color:var(--txt)}
.tl li.c b{color:var(--c)}
.tl li:not(.d):not(.c) small{color:#5d5888!important}
.tl li.x small{color:var(--c-late)!important;font-weight:700}
.st-storno .tl{opacity:.5}
/* rychlé akce */
.qa{appearance:none;display:inline-block;text-decoration:none;border:1px solid color-mix(in srgb,var(--c) 60%,transparent);background:color-mix(in srgb,var(--c) 18%,transparent);color:#fff;font:700 12.5px/1 inherit;font-family:inherit;padding:9px 12px;border-radius:8px;cursor:pointer;white-space:nowrap}
.qa:hover{background:var(--c);color:#0b0926}
.qpost{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:0 14px 12px 32px;box-shadow:inset 4px 0 0 var(--c);font-size:13px}
.qp-l{color:var(--c-post);font-weight:700}
.qp-a{color:var(--txt);flex:1;min-width:180px}
.qpost input[type=text]{width:150px;padding:8px 11px;border-radius:8px;border:1px solid var(--c-post);background:#15113a;color:#fff;font:700 14px inherit;font-family:inherit;letter-spacing:.06em;text-transform:uppercase}
.qpost .chk{padding:0;font-size:12.5px;color:var(--mut)}
.qpost .btn{background:var(--c-post);border-color:var(--c-post);color:#0b0926}
details.row[open]+.qpost{display:none}
.c-ch::before{content:"";display:block;width:7px;height:7px;border-right:2px solid var(--mut);border-bottom:2px solid var(--mut);transform:rotate(45deg);transition:transform .15s;margin-top:-4px}
details.row[open]>.rs .c-ch::before{transform:rotate(-135deg);margin-top:4px}
details.row[open]>.rs{background:color-mix(in srgb,var(--c) 10%,transparent);opacity:1}
details.row.hi,details.row:target{outline:2px solid var(--acc);outline-offset:-2px}
details.row>.o{margin:0;border:0;border-top:1px dashed var(--line);border-radius:0;background:#0b0926;padding:14px 18px 14px 28px;box-shadow:inset 4px 0 0 var(--c)}
details.row>.o::before{display:none}
details.row>.o .top{display:none}
.lcount{text-align:right}
@media (max-width:1000px){
.kpis{grid-template-columns:repeat(2,1fr)}
.lh{display:none}
.rs{grid-template-columns:1fr auto 16px;gap:6px 10px;padding:12px 12px 12px 16px}
.c-st{grid-column:1;grid-row:1}.c-typ{grid-column:2;grid-row:1;text-align:right}.c-ch{grid-column:3;grid-row:1}
.c-cus{grid-column:1/4;grid-row:2}
.c-tl{grid-column:1/4;grid-row:3;margin:4px 0}
.c-act{grid-column:1/4;grid-row:4;text-align:left}.c-act:empty{display:none}
.qa{width:100%;padding:11px;text-align:center}
.c-vou{display:none}
.qpost{padding:0 12px 12px 16px}.qpost input[type=text]{flex:1}.qpost .btn{width:100%}
details.row>.o{padding:12px 14px}
}

/* ---- nástěnka ---- */
.board{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:start}
.bcol{background:var(--card);border:1px solid var(--line);border-top:4px solid var(--c);border-radius:12px;padding:12px;min-width:0}
.bct{display:flex;align-items:center;gap:10px}
.bct .ic{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:color-mix(in srgb,var(--c) 20%,transparent);color:var(--c);font-size:17px;flex:none}
.bct div{flex:1;min-width:0}
.bct b{display:block;font-size:15.5px}
.bct small{display:block;color:var(--mut);font-size:12px}
.bct em{font-style:normal;font-weight:800;font-size:22px;color:var(--c);min-width:36px;text-align:center;padding:2px 10px;border-radius:10px;background:color-mix(in srgb,var(--c) 14%,transparent)}
.bcs{color:var(--mut);font-size:12.5px;margin:6px 0 10px 44px;font-variant-numeric:tabular-nums}
.lt{color:var(--c-late)!important;font-weight:700}
.bc{background:#0b0926;border:1px solid var(--line);border-left:3px solid var(--c);border-radius:10px;padding:11px 12px;margin-top:8px;transition:border-color .15s}
.bc:hover{border-color:color-mix(in srgb,var(--c) 70%,transparent)}
.bc.late{border-left-color:var(--c-late);background:linear-gradient(90deg,rgba(255,107,138,.09),#0b0926 55%)}
.bh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
.bh>div:first-child{min-width:0}
.bn{display:block;color:var(--txt);font-weight:700;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bn:hover{color:var(--c)}
.bh small{display:block;color:var(--mut);font-size:12px;margin-top:1px}
.tg.g{color:var(--gold)}
.bp{font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums}
.bc .tl{margin:10px 0 4px}
.bmeta{font-size:12.5px;color:#cfc8f5;margin-top:8px}
.bmeta .code{font-size:13px}
.addr{margin-top:9px;padding:8px 10px;border-radius:8px;background:rgba(92,200,255,.07);border:1px dashed rgba(92,200,255,.35);font-size:13px;line-height:1.45}
.addr span{display:block;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--c-post);margin-bottom:2px}
.bpost{margin-top:10px}
.bpost input[type=text]{width:100%;box-sizing:border-box;padding:9px 11px;border-radius:8px;border:1px solid var(--c-post);background:#15113a;color:#fff;font:700 14px inherit;font-family:inherit;letter-spacing:.06em;text-transform:uppercase}
.bpost .chk{display:flex;padding:6px 0 0;font-size:12.5px;color:var(--mut)}
.bbtn{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.bbtn form{margin:0;display:contents}
.bbtn .btn{flex:1;text-align:center;justify-content:center}
.btn.b-go{background:var(--c);border-color:var(--c);color:#0b0926;font-weight:800}
.bc.late .btn.b-go{background:var(--c-late);border-color:var(--c-late)}
.bempty{padding:22px 10px;text-align:center;color:var(--mut);font-size:13px;border:1px dashed var(--line);border-radius:10px;margin-top:8px}
.bmore{display:block;text-align:center;margin-top:10px;padding:9px;border-radius:8px;color:var(--c);text-decoration:none;font-weight:700;font-size:13px;background:color-mix(in srgb,var(--c) 10%,transparent)}
@media (max-width:1100px){.board{grid-template-columns:1fr 1fr}.board .st-ok{grid-column:1/-1}}
@media (max-width:720px){.board{grid-template-columns:1fr}}

/* ===== přehledná správa: 3 kroky ===== */
:root{--s-pay:#ffb86b;--s-post:#5cc8ff;--s-done:#5fd39a;--s-late:#ff6b8a}
.wrap{max-width:1180px}
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin:4px 0 16px}
.step{--c:var(--mut);position:relative;display:block;text-decoration:none;color:var(--txt);background:var(--card);border:2px solid var(--line);border-radius:16px;padding:16px 18px 14px;transition:border-color .15s,background .15s}
.step:hover{border-color:color-mix(in srgb,var(--c) 55%,var(--line))}
.step:not(:last-child)::after{content:"";position:absolute;right:-22px;top:50%;width:13px;height:13px;margin-top:-7px;border-top:3px solid #3a3175;border-right:3px solid #3a3175;transform:rotate(45deg);border-radius:2px}
.step.s-pay{--c:var(--s-pay)}.step.s-post{--c:var(--s-post)}.step.s-done{--c:var(--s-done)}
.step .sh{display:flex;align-items:center;gap:10px}
.step .num{flex:none;width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:14px;background:color-mix(in srgb,var(--c) 18%,transparent);color:var(--c)}
.step .stt{font-weight:750;font-size:16px}
.step .scnt{display:flex;align-items:baseline;gap:10px;margin-top:10px}
.step .scnt b{font-size:38px;line-height:1;font-weight:800;color:var(--c);font-variant-numeric:tabular-nums}
.step .scnt span{color:var(--mut);font-size:13.5px;font-variant-numeric:tabular-nums}
.step .snote{display:block;margin-top:8px;font-size:13px;color:var(--mut);min-height:19px}
.bad{color:var(--s-late)!important;font-weight:700}
.step.on{background:color-mix(in srgb,var(--c) 14%,var(--card));border-color:var(--c);box-shadow:0 8px 30px -12px color-mix(in srgb,var(--c) 65%,transparent)}
.step.on .num{background:var(--c);color:#0b0926}
.step.zero:not(.on){opacity:.7}.step.zero:not(.on) .scnt b{color:#5d5888}
.sbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
.ssearch{flex:1;min-width:220px;margin:0}
.ssearch input{width:100%;padding:12px 16px;border-radius:11px;border:1px solid var(--line);background:#15113a;color:#fff;font:inherit;font-size:14px}
.ssearch input:focus{outline:none;border-color:var(--acc)}
.sside{display:flex;gap:6px;flex-wrap:wrap}
.sside a{padding:10px 13px;border-radius:10px;border:1px solid var(--line);color:var(--mut);text-decoration:none;font-size:13px;font-weight:600}
.sside a b{color:var(--txt);margin-left:5px}
.sside a.on,.sside a:hover{background:var(--line);color:var(--txt)}
.lhead{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin:6px 2px 10px}
.lhead h2{margin:0;font-size:18px}
.lhead span{color:var(--mut);font-size:13px;font-variant-numeric:tabular-nums}
.dls{display:flex;gap:6px}.dls b{font-size:12px;padding:4px 9px;border-radius:7px}
.dls .dp{background:rgba(92,200,255,.12);color:#5cc8ff}.dls .dm{background:rgba(185,140,255,.12);color:#cdb8ff}
.lhr{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.btn.lbl{background:var(--s-post);border-color:var(--s-post);color:#0b0926;font-weight:800;padding:10px 16px;text-decoration:none}
.btn.lbl:hover{filter:brightness(1.08)}
.btn.lbl.ghostb{background:transparent;color:var(--s-post);border:1px solid var(--s-post)}
.btn.lbl.ghostb:hover{background:rgba(92,200,255,.12);filter:none}
.empty b{display:block;font-size:17px;color:var(--txt);margin-bottom:4px}
.empty{border:1px dashed #3a3175;border-radius:14px;padding:46px 20px}
.card{--c:var(--mut);background:var(--card);border:1px solid var(--line);border-left:4px solid var(--c);border-radius:14px;margin-bottom:10px}
.card.g-pay{--c:var(--s-pay)}.card.g-post{--c:var(--s-post)}.card.g-done{--c:var(--s-done)}.card.g-used,.card.g-storno{--c:#5d5888}
.card.late{--c:var(--s-late);background:linear-gradient(90deg,rgba(255,107,138,.08),var(--card) 45%)}
.card.g-used .cmain,.card.g-storno .cmain{opacity:.72}
.card.hi{box-shadow:0 0 0 3px rgba(185,140,255,.3)}
.cmain{display:grid;grid-template-columns:minmax(190px,1.1fr) minmax(240px,1.25fr) minmax(300px,1.5fr);gap:20px;align-items:center;padding:15px 18px}
.who .nm{font-weight:750;font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wm{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;font-size:13px;color:var(--mut)}
.tag{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11.5px;font-weight:700;background:rgba(185,140,255,.16);color:#cdb8ff}
.tag.gold{background:rgba(240,207,133,.15);color:var(--gold)}
.wm .pr{color:var(--txt);font-weight:750;font-variant-numeric:tabular-nums}
.dic{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:7px;flex:none;cursor:help}
.dic.d-post{background:rgba(92,200,255,.16);color:#5cc8ff}
.dic.d-mail{background:rgba(185,140,255,.16);color:#cdb8ff}
.grp{display:inline-block;margin-top:6px;font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:color-mix(in srgb,var(--c) 16%,transparent);color:var(--c)}
.ctl .tl li+li::after{background:#3a3175!important}
.ctl .tl li.d+li.d::after{background:var(--c)!important}
.ctl .tl li.d::before{background:var(--c);border-color:var(--c)}
.ctl .tl li.c::before{background:var(--card);border-color:var(--c);box-shadow:0 0 0 4px color-mix(in srgb,var(--c) 22%,transparent)}
.ctl .tl li.c b{color:var(--c)}
.ctl .tl li.x::before{border-color:var(--s-late);background:var(--card)}
.ctl .tl b{font-size:11.5px!important}.ctl .tl small{font-size:12px!important}
.cact{display:flex;flex-direction:column;gap:8px;min-width:0}
.cact .info{font-size:13.5px;color:var(--mut)}
.cact .info b{color:var(--txt)}
.paddr{font-size:13px;line-height:1.4;color:var(--txt);padding:7px 10px;border-radius:9px;border:1px dashed rgba(92,200,255,.45);background:rgba(92,200,255,.06);margin-bottom:6px}
.paddr span{display:block;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--s-post)}
.btns{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btns form{margin:0}
.card .btn.main{background:var(--c);color:#0b0926;font-weight:800;padding:11px 16px}
.card .btn.main:hover{filter:brightness(1.08)}
.pform,.uform{display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%}
.pcode{flex:1;min-width:130px;padding:10px 12px;border-radius:9px;border:1px solid var(--s-post);background:#15113a;color:#fff;font:700 15px inherit;font-family:inherit;letter-spacing:.08em;text-transform:uppercase}
.pcode::placeholder{text-transform:none;letter-spacing:0;font-weight:500;color:#6f6a99}
.pform .chk{padding:0;font-size:12px;color:var(--mut);flex-basis:100%;gap:6px}
.pform .chk input{width:15px;height:15px}
.padr{margin-top:8px;padding-top:7px;border-top:1px dashed var(--line);font-size:12.5px;line-height:1.35;color:#cfc8f5;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.padr b{color:var(--txt);font-weight:700}
.card.g-post .cmain{padding:11px 18px}
.card.g-post .pcode{padding:8px 10px;font-size:14px;min-width:110px}
.card.g-post .btn.main{padding:9px 14px}
.card.g-post .btns .btn.sm{padding:8px 11px}
@media (max-width:680px){.padr{white-space:normal}}
.uform input{flex:1;min-width:150px;padding:10px 12px;border-radius:9px;border:1px solid var(--line);background:#15113a;color:#fff;font:inherit;font-size:14px}
details.det{border-top:1px solid var(--line)}
details.det>summary{cursor:pointer;list-style:none;padding:8px 18px;font-size:12.5px;color:var(--mut);font-weight:600}
details.det>summary::-webkit-details-marker{display:none}
details.det>summary::before{content:"▸ "}
details.det[open]>summary::before{content:"▾ "}
details.det>summary:hover{color:var(--txt)}
details.det>.o{margin:0;border:0;border-radius:0 0 14px 14px;background:#0b0926;padding:6px 18px 16px}
details.det>.o::before,details.det>.o>.top,details.det>.o>.prog,details.det>.o>.act{display:none}
details.det .more{border-top:0;margin-top:6px;padding-top:0}
details.det .more>summary{display:none}
.card.g-post details.det .postbox:not(.sent){display:none}
.mailhist{margin:10px 0;padding:10px 12px;border-radius:10px;border:1px solid var(--line);font-size:13px;color:var(--txt)}
.mailhist span{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--mut);margin-bottom:4px}
@media (max-width:1000px){.cmain{grid-template-columns:1fr 1fr}.cact{grid-column:1/-1}}
@media (max-width:680px){
.steps{gap:8px}.step{padding:11px 8px 10px;text-align:center;border-radius:13px}
.step:not(:last-child)::after{display:none}
.step .sh{flex-direction:column;gap:5px}.step .num{width:24px;height:24px;font-size:12.5px}
.step .stt{font-size:12.5px;line-height:1.2}
.step .scnt{justify-content:center;margin-top:6px}.step .scnt b{font-size:28px}
.step .scnt span,.step .snote{display:none}
.cmain{grid-template-columns:1fr;padding:14px}
.sside{width:100%}.sside a{flex:1;text-align:center;padding:9px 6px}
.card .btn.main{flex:1;justify-content:center}
.lhead{flex-wrap:wrap}.lhr{width:100%;justify-content:space-between}.btn.lbl{flex:1;justify-content:center;text-align:center}
}
</style></head><body>' . $body . '<script>
(function(){var open=function(){var h=location.hash;if(h&&h.indexOf("#o-")===0){var d=document.getElementById(decodeURIComponent(h.slice(1)));if(d){var x=d.tagName==="DETAILS"?d:d.querySelector("details.det");if(x)x.open=true;d.scrollIntoView({block:"center"});}}};open();window.addEventListener("hashchange",open);
var b=document.getElementById("xall");if(b)b.addEventListener("click",function(){var r=document.querySelectorAll("details.det"),any=[].some.call(r,function(d){return !d.open});[].forEach.call(r,function(d){d.open=any});b.textContent=any?"Sbalit vše":"Rozbalit vše";});
document.addEventListener("click",function(e){var q=e.target.closest(".qa");if(!q||q.tagName==="A")return;e.preventDefault();e.stopPropagation();
if(q.dataset.form){if(!q.dataset.confirm||confirm(q.dataset.confirm)){q.disabled=true;document.getElementById(q.dataset.form).submit();}return;}
var d=q.closest("details");d.open=true;var i=d.querySelector(q.dataset.open);if(i){var m=i.closest("details.more");if(m)m.open=true;setTimeout(function(){i.scrollIntoView({block:"center"});i.focus();},30);}},true);
document.querySelectorAll(".search input").forEach(function(i){i.addEventListener("keydown",function(e){if(e.key==="Escape"){i.value="";i.form.submit();}})});})();
</script></body></html>';
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
<p class="meta">Oznámení se ukáže nahoře na webu pod menu. Návštěvník ho může zavřít křížkem – za 24 hodin se mu ukáže znovu. Když text změníte, ukáže se mu hned. Po zadaném datu zmizí samo.</p>
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

/* ---- štítky s adresami k tisku (poukazy k odeslání poštou) ---- */
if (isset($_GET['stitky'])) {
    $L = [];
    foreach (bb_list_orders($DATA) as $o) {
        if (bb_voucher_state($o) !== 'k_odeslani') continue;
        $c = $o['customer'] ?? [];
        $addr = trim((string) ($c['addr'] ?? ''));
        $lines = array_values(array_filter(array_map('trim', preg_split('/\s*[,\n]\s*/u', $addr)), 'strlen'));
        $lines = array_map(fn($l) => preg_replace('/^(\d{3})\s?(\d{2})\b/u', '$1 $2', $l), $lines);
        $L[] = ['id' => (string) $o['id'], 'name' => (string) ($c['name'] ?? ''), 'company' => (string) ($c['company'] ?? ''), 'lines' => $lines,
            'ok' => $addr !== '' && mb_strlen($addr) >= 5, 'paid' => !empty($o['paid_at']) ? date('j. n.', (int) $o['paid_at']) : ''];
    }
    usort($L, fn($a, $b) => strcmp($a['name'], $b['name']));
    $json = json_encode($L, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Štítky k odeslání poukazů</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#e9e7f0;color:#1d1a2e;font:14px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.panel{max-width:1000px;margin:20px auto;background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
h1{margin:0 0 4px;font-size:20px}
.sub{color:#6c6880;margin:0 0 16px}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px 16px;margin-bottom:16px}
label.f{display:flex;flex-direction:column;gap:4px;font-size:12.5px;font-weight:650;color:#4a4560}
label.f select,label.f input{padding:9px 10px;border:1px solid #d3cfe0;border-radius:8px;font:inherit;font-weight:400;color:#1d1a2e;background:#fff}
.chkline{display:flex;align-items:center;gap:7px;font-size:13px;color:#4a4560;margin:2px 0 14px}
.list{border:1px solid #e3e0ec;border-radius:10px;max-height:260px;overflow:auto;margin-bottom:16px}
.list label{display:flex;gap:10px;align-items:flex-start;padding:9px 12px;border-bottom:1px solid #f0eef5;cursor:pointer}
.list label:last-child{border-bottom:0}
.list label:hover{background:#f7f5fb}
.list b{display:block}
.list small{color:#6c6880}
.list .bad{color:#c2334d;font-weight:650}
.list input{margin-top:4px;accent-color:#6b4fd8}
.bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.btn{padding:12px 20px;border-radius:10px;border:0;background:#6b4fd8;color:#fff;font:800 15px inherit;font-family:inherit;cursor:pointer}
.btn.ghost{background:#efedf6;color:#3a3555;font-weight:650;font-size:13.5px;padding:10px 14px}
.note{font-size:12.5px;color:#6c6880;background:#f6f4fb;border-radius:8px;padding:9px 12px;margin-top:14px}
.empty{padding:30px;text-align:center;color:#6c6880}
.prevh{max-width:1000px;margin:0 auto 8px;color:#6c6880;font-size:13px;padding:0 4px}
.sheets{display:flex;flex-direction:column;align-items:center;gap:20px;padding-bottom:40px}
.sheet{width:210mm;height:297mm;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.15);position:relative;overflow:hidden;display:grid;align-content:start}
.lab{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;padding:3mm 5mm 3mm 6mm;outline:1px dashed #cfcbe0;outline-offset:-1px}
.lab.skip{background:repeating-linear-gradient(45deg,#f4f3f8 0 6px,#fff 6px 12px)}
.lab .from{font-size:6pt;line-height:1.2;color:#555;border-bottom:.2mm solid #999;padding-bottom:.8mm;margin-bottom:1.6mm;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.lab .to{font-size:var(--fs);line-height:1.25;color:#000}
.lab .to b{display:block;font-weight:700}
.lab .to .co,.lab .to span{display:block}
.lab .to .psc{font-weight:700;letter-spacing:.02em}
.lab.snd .to{font-size:calc(var(--fs) * .92)}
.lab.snd .tag{display:block;font-size:6pt;letter-spacing:.12em;text-transform:uppercase;color:#777;margin-bottom:1.2mm}
@page{size:A4 portrait;margin:0}
@media print{html,body{width:210mm;margin:0!important;padding:0!important}.sheet{width:210mm!important;height:297mm!important;margin:0!important;transform:none!important}}
body.full .lab{padding:7mm 9mm;align-items:flex-start}
body.full .lab .from{font-size:calc(var(--fs) * .45);margin-bottom:3mm;padding-bottom:2mm}
body.full .lab.snd .tag{font-size:calc(var(--fs) * .4);margin-bottom:2.5mm}
body.full .lab .to{line-height:1.3}
@media print{body{background:#fff}.panel,.prevh{display:none!important}.sheets{display:block;padding:0;gap:0}.sheet{box-shadow:none;page-break-after:always;break-after:page}.sheet:last-child{page-break-after:auto;break-after:auto}.lab{outline:0}.lab.skip{background:none}body.cut .lab{outline:.2mm dashed #bbb}}
@media screen and (max-width:760px){.grid{grid-template-columns:1fr}.sheet{transform:scale(.45);transform-origin:top center;margin-bottom:-160mm}}
</style></head>
<body>
<div class="panel">
  <h1>Štítky s adresami</h1>
  <p class="sub">Poukazy, které jsou zaplacené a čekají na odeslání poštou.</p>
  <div class="grid">
    <label class="f">Formát archu
      <select id="fmt"><option value="3x8">A4 · 3 × 8 = 24 štítků (70 × 37 mm)</option><option value="4x8">A4 · 4 × 8 = 32 štítků (52,5 × 37 mm)</option></select></label>
    <label class="f">Začít od štítku č.<input id="start" type="number" min="1" value="1"></label>
    <label class="f">Odesílatel<input id="from" type="text" value="Black Box · Purkyňova 2961 · 390 02 Tábor"><small style="font-weight:400;color:#8a859c">řádky oddělte tečkou „·“</small></label>
  </div>
  <div class="grid" style="grid-template-columns:2fr 1fr">
    <label class="f">Jak tisknout odesílatele
      <select id="fmode">
        <option value="small">Malým písmem nahoře na každém štítku s adresou</option>
        <option value="pair">Samostatný štítek odesílatele za každou adresou (na zadní stranu obálky)</option>
        <option value="only">Jen štítky s odesílatelem (zadaný počet)</option>
        <option value="none">Netisknout odesílatele</option>
      </select></label>
    <label class="f" id="fcntWrap">Počet štítků s odesílatelem<input id="fcnt" type="number" min="1" max="500" value="24"></label>
  </div>
  <label class="chkline"><input type="checkbox" id="cut"> tisknout mřížku (čáry na stříhání)</label>
  <div class="list" id="list"></div>
  <div class="bar"><button class="btn" id="print">Tisknout štítky</button><button class="btn ghost" id="all">Vybrat vše</button><button class="btn ghost" id="none">Zrušit výběr</button><span id="sum" style="color:#6c6880;font-size:13px"></span></div>
  <div class="note">V okně tisku nastavte <b>Měřítko 100 % / Skutečná velikost</b> a <b>Okraje: žádné</b>, jinak štítky nesednou na arch. Na částečně použitém archu nastavte „Začít od štítku č.“ – prázdná místa se přeskočí.</div>
</div>
<div class="prevh">Náhled tisku:</div>
<div class="sheets" id="sheets"></div>
<script>
(function(){
var DATA=<?= $json ?>;
var ONLY_SENDER=<?= !empty($_GET['odesilatel']) ? 'true' : 'false' ?>;
var F={'3x8':[3,8,70,37,'10.5pt'],'4x8':[4,8,52.5,37,'8.5pt']};
var $=function(i){return document.getElementById(i)};
var esc=function(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})};
var sel={};DATA.forEach(function(x){sel[x.id]=x.ok});
try{var st=JSON.parse(localStorage.getItem('bbStitkyA4')||'{}');if(st.fmt&&(st.fmt in F))$('fmt').value=st.fmt;if(st.from)$('from').value=st.from;if(st.fmode&&st.fmode!=='only')$('fmode').value=st.fmode;else if(st.showFrom===false)$('fmode').value='none';if(st.fcnt)$('fcnt').value=st.fcnt;}catch(e){}
function save(){try{localStorage.setItem('bbStitkyA4',JSON.stringify({fmt:$('fmt').value,from:$('from').value,fmode:$('fmode').value,fcnt:$('fcnt').value}))}catch(e){}}
function list(){
  if(!DATA.length){$('list').innerHTML='<div class="empty">Žádný poukaz teď nečeká na odeslání poštou.</div>';return;}
  $('list').innerHTML=DATA.map(function(x){return '<label><input type="checkbox" data-id="'+esc(x.id)+'"'+(sel[x.id]?' checked':'')+'><span><b>'+esc(x.name)+'</b>'
    +(x.ok?'<small>'+esc(x.lines.join(', '))+(x.paid?' · zaplaceno '+esc(x.paid):'')+'</small>':'<small class="bad">Chybí adresa – doplňte ji ve správě</small>')+'</span></label>'}).join('');
  [].forEach.call($('list').querySelectorAll('input'),function(i){i.onchange=function(){sel[i.dataset.id]=i.checked;draw()}});
}
function label(x){
  var t='<div class="to">'+(x.company?'<span class="co">'+esc(x.company)+'</span>':'')+'<b>'+esc(x.name)+'</b>';
  x.lines.forEach(function(l){t+=/^\d{3} \d{2}\b/.test(l)?'<span class="psc">'+esc(l)+'</span>':'<span>'+esc(l)+'</span>'});
  return ($('fmode').value==='small'&&$('from').value.trim()?'<div class="from">Odesílatel: '+esc($('from').value.trim())+'</div>':'')+t+'</div>';
}
function sender(){
  var L=$('from').value.split(/\s*[·|\n]\s*/).filter(function(l){return l.trim()});
  if(!L.length)return '';
  var t='<span class="tag">Odesílatel</span><div class="to"><b>'+esc(L[0])+'</b>';
  L.slice(1).forEach(function(l){t+=/^\d{3} ?\d{2}\b/.test(l)?'<span class="psc">'+esc(l)+'</span>':'<span>'+esc(l)+'</span>'});
  return t+'</div>';
}
function draw(){
  var full=$('fmt').value==='full';
  var mode=$('fmode').value,SND={snd:1};
  var f,cols,rows,w,h,per;
  if(full){
    // kolik štítků bude celkem → rozvrh, který zaplní A4 (na výšku, 8 mm okraj)
    var nAddr=mode==='only'?0:DATA.filter(function(x){return sel[x.id]&&x.ok}).length;
    var nAll=mode==='only'?Math.max(1,Math.min(500,parseInt($('fcnt').value,10)||1)):nAddr*(mode==='pair'?2:1);
    var L=[[1,1],[1,2],[2,2],[2,2],[2,3],[2,3],[2,4],[2,4]];
    var g=nAll>=8?[2,4]:L[Math.max(0,nAll-1)];
    cols=g[0];rows=g[1];per=cols*rows;
    w=(210-16)/cols;h=(297-16)/rows;
    var fs=Math.max(13,Math.min(30,Math.min(w,h)*.22));
    f=[cols,rows,w,h,fs.toFixed(1)+'pt'];
  }else{f=F[$('fmt').value];cols=f[0];rows=f[1];w=f[2];h=f[3];per=cols*rows;}
  var start=Math.max(1,Math.min(per,parseInt($('start').value,10)||1));
  $('fcntWrap').style.display=mode==='only'?'':'none';
  $('list').style.display=mode==='only'?'none':'';$('all').style.display=$('none').style.display=mode==='only'?'none':'';
  var pick=mode==='only'?[]:DATA.filter(function(x){return sel[x.id]&&x.ok});
  var cells=[];for(var i=1;i<start;i++)cells.push(null);
  if(mode==='only'){var n=Math.max(1,Math.min(500,parseInt($('fcnt').value,10)||1));for(var j=0;j<n;j++)cells.push(SND);}
  else pick.forEach(function(x){cells.push(x);if(mode==='pair')cells.push(SND);});
  var cnt=cells.length-(start-1);
  $('sum').textContent=cnt?('K tisku '+cnt+' štítků'+(mode==='pair'?' ('+pick.length+' adres + '+pick.length+' odesílatel)':'')):'Nic není vybráno';
  var noFrom=(mode==='only'||mode==='pair')&&!$('from').value.trim();
  $('print').disabled=!cnt||noFrom;
  document.body.classList.toggle('cut',$('cut').checked||full);document.body.classList.toggle('full',full);
  var html='';
  for(var p=0;p<Math.max(1,Math.ceil(cells.length/per));p++){
    var top=(297-rows*h)/2,left=(210-cols*w)/2;
    html+='<div class="sheet" style="grid-template-columns:repeat('+cols+','+w+'mm);grid-auto-rows:'+h+'mm;padding:'+top+'mm '+left+'mm;--fs:'+f[4]+'">';
    for(var k=0;k<per;k++){var x=cells[p*per+k];html+=x===null?'<div class="lab skip"></div>':(x===SND?'<div class="lab snd">'+sender()+'</div>':(x?'<div class="lab">'+label(x)+'</div>':'<div class="lab"></div>'));}
    html+='</div>';
  }
  $('sheets').innerHTML=html;save();
}
['fmt','start','from','fmode','fcnt','cut'].forEach(function(i){$(i).oninput=draw;$(i).onchange=draw});
$('all').onclick=function(){DATA.forEach(function(x){sel[x.id]=x.ok});list();draw()};
$('none').onclick=function(){DATA.forEach(function(x){sel[x.id]=false});list();draw()};
$('print').onclick=function(){window.print()};
if(ONLY_SENDER){$('fmode').value='only';document.querySelector('h1').textContent='Štítky odesílatele';document.querySelector('.sub').textContent='Arch štítků s adresou Black Box – na zadní stranu obálek.';}
list();draw();
})();
</script>
</body></html>
<?php
    exit;
}

/* ---- hromadné vygenerování poukazů v intervalu čísel ---- */
if (isset($_GET['generovat'])) {
    $h2 = fn($x) => bb_h((string) $x);
    $err = '';
    $vals = ['prefix' => '', 'od' => '1', 'do' => '10', 'cifer' => '4', 'typ' => 'standard', 'lay' => '3', 'okraje' => '', 'bez' => '', 'pocet' => '3'];
    if ($isPost) {
        foreach ($vals as $k => $v) $vals[$k] = trim((string) ($_POST[$k] ?? $v));
        $from = (int) $vals['od'];
        $to = (int) $vals['do'];
        $pad = max(1, min(8, (int) $vals['cifer']));
        $prefix = bb_norm_code($vals['prefix']);
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            $err = 'Platnost stránky vypršela, načtěte ji znovu.';
        } elseif ($vals['bez'] !== '') {
            /* poukazy bez čísla – číslo se dopíše rukou */
            $cnt = (int) $vals['pocet'];
            if ($cnt < 1 || $cnt > 200) {
                $err = 'Počet poukazů musí být 1 až 200.';
            } else {
                @set_time_limit(300);
                $per = in_array((int) $vals['lay'], [2, 3, 4], true) ? (int) $vals['lay'] : 3;
                $gold = $vals['typ'] === 'gold';
                $list = array_fill(0, $cnt, ['id' => 'tisk-bez-cisla', 'kind' => $gold ? 'gold' : 'standard', 'voucher' => '',
                    'customer' => ['name' => '', 'email' => '', 'phone' => '', 'addr' => '', 'company' => '', 'ico' => '']]);
                $okraje = !empty($_POST['okraje']);
                $bytes = $cnt === 1 && !$okraje ? bb_voucher_pdf($cfg, $list[0], $per) : bb_vouchers_sheet_pdf($cfg, $list, $per, $okraje ? 6.0 : 0.0);
                $base = 'poukaz-' . ($gold ? 'gold' : 'standard') . '-bez-cisla-' . $cnt . 'x-' . $per . '-na-A4' . ($okraje ? '-s-okraji' : '');
                if (!empty($_POST['json'])) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['name' => $base, 'count' => $cnt, 'pages' => $cnt === 1 && !$okraje ? 1 : (int) ceil($cnt / $per), 'single' => $cnt === 1 && !$okraje, 'pdf' => base64_encode($bytes)]);
                    exit;
                }
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
                echo $bytes;
                exit;
            }
        } elseif ($from < 0 || $to < $from) {
            $err = 'Číslo „do“ musí být stejné nebo větší než „od“.';
        } elseif ($to - $from + 1 > 200) {
            $err = 'Najednou jde vygenerovat nejvýš 200 poukazů.';
        } elseif (mb_strlen($prefix) + max($pad, strlen((string) $to)) > 20) {
            $err = 'Číslo poukazu může mít nejvýš 20 znaků.';
        } else {
            $codes = [];
            $used = [];
            for ($n = $from; $n <= $to; $n++) {
                $code = $prefix . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
                $codes[] = $code;
                if ($dup = bb_find_voucher($DATA, $code)) $used[] = $code . ' (' . bb_who($dup) . ')';
            }
            if ($used && empty($_POST['i_pouzite'])) {
                $err = 'Tato čísla už jsou přidělená objednávkám: ' . implode(', ', array_slice($used, 0, 10)) . (count($used) > 10 ? ' a další' : '') . '. Zvolte jiný interval, nebo zaškrtněte „vygenerovat i obsazená čísla“.';
            } else {
                @set_time_limit(300);
                $per = in_array((int) $vals['lay'], [2, 3, 4], true) ? (int) $vals['lay'] : 3;
                $list = [];
                foreach ($codes as $code) {
                    $list[] = ['id' => 'tisk-' . $code, 'kind' => $vals['typ'] === 'gold' ? 'gold' : 'standard', 'voucher' => $code,
                        'customer' => ['name' => '', 'email' => '', 'phone' => '', 'addr' => '', 'company' => '', 'ico' => '']];
                }
                $okraje = !empty($_POST['okraje']);
                $bytes = bb_vouchers_sheet_pdf($cfg, $list, $per, $okraje ? 6.0 : 0.0);
                $base = 'poukazy-' . $codes[0] . (count($codes) > 1 ? '-' . end($codes) : '') . '-' . $per . '-na-A4' . ($okraje ? '-s-okraji' : '');
                if (!empty($_POST['json'])) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['name' => $base, 'count' => count($codes), 'pages' => (int) ceil(count($codes) / $per), 'pdf' => base64_encode($bytes)]);
                    exit;
                }
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
                echo $bytes;
                exit;
            }
        }
    }
    if ($isPost && !empty($_POST['json'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $err ?: 'Poukazy se nepodařilo vygenerovat.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $prev = bb_norm_code($vals['prefix']) . str_pad((string) (int) $vals['od'], max(1, (int) $vals['cifer']), '0', STR_PAD_LEFT);
    $body = menu('objednavky') . '<div class="wrap"><div class="gen">'
        . '<a class="gback" href="?f=odeslat">← zpět do správy</a>'
        . '<h2>Vygenerovat poukazy k tisku</h2>'
        . '<p class="gsub">Zadejte interval čísel (nebo zaškrtněte „Bez čísel“) a vyberte, jak je vytisknout. Poukazy se nikomu nepřiřadí – číslo pak zadáte u objednávky v kroku „Odeslat poukaz“.</p>'
        . ($err ? '<div class="warn">' . $h2($err) . '</div>' : '')
        . '<form method="post" class="gform" id="gform"><input type="hidden" name="csrf" value="' . $h2($_SESSION['csrf'] ?? '') . '">'
        . '<label>Typ poukazu<select name="typ"><option value="standard"' . ($vals['typ'] !== 'gold' ? ' selected' : '') . '>Standard Time</option><option value="gold"' . ($vals['typ'] === 'gold' ? ' selected' : '') . '>Gold Time</option></select></label>'
        . '<label class="gchk gbez"><input type="checkbox" name="bez" id="gbez" value="1"' . ($vals['bez'] !== '' ? ' checked' : '') . '> <b>Bez čísel</b>&nbsp;– číslo poukazu dopíšete rukou</label>'
        . '<label class="gcnt">Počet poukazů<input type="number" name="pocet" value="' . $h2($vals['pocet']) . '" min="1" max="200"></label>'
        . '<label class="gnum">Předpona (nepovinná)<input type="text" name="prefix" value="' . $h2($vals['prefix']) . '" maxlength="10" placeholder="např. BB"></label>'
        . '<label class="gnum">Od čísla<input type="number" name="od" value="' . $h2($vals['od']) . '" min="0" required></label>'
        . '<label class="gnum">Do čísla<input type="number" name="do" value="' . $h2($vals['do']) . '" min="0" required></label>'
        . '<label class="gnum">Počet cifer<select name="cifer">' . implode('', array_map(fn($n) => '<option' . ((int) $vals['cifer'] === $n ? ' selected' : '') . '>' . $n . '</option>', [1, 2, 3, 4, 5, 6, 7, 8])) . '</select></label>'
        . '<div class="gprev gnum">Ukázka: <b id="gp">' . $h2($prev) . '</b> … <b id="gp2"></b><span id="gn"></span></div>'
        . '<label class="gchk gnum"><input type="checkbox" name="i_pouzite" value="1"> vygenerovat i čísla, která už jsou přidělená (jen dotisk)</label>'
        . '<fieldset class="glay"><legend>Jak to vytisknout</legend>'
        . implode('', array_map(fn($n, $t) => '<label class="gopt"><input type="radio" name="lay" value="' . $n . '"' . ((int) $vals['lay'] === $n ? ' checked' : '') . '><span><b>' . $n . ' poukazy na A4</b><small>' . $t . '</small></span></label>',
            [2, 3, 4], ['přes celou šířku · 210 × 148,5 mm · velký formát', 'přes celou šířku · 210 × 99 mm · klasický poukaz', 'přes celou šířku · 210 × 74 mm · úzký pruh (bez výčtu vybavení)']))
        . '<label class="gopt gok"><input type="checkbox" name="okraje" value="1"' . ($vals['okraje'] !== '' ? ' checked' : '') . '><span><b>Tisknout s okraji</b><small>pro běžnou tiskárnu, která netiskne až ke kraji papíru · okraj 6 mm, poukazy se zmenší na 94 %, mezi nimi mezera a značky pro stříhání</small></span></label>'
        . '</fieldset>'
        . '<button class="btn" type="submit" id="gbtn">Vygenerovat</button><div class="gmsg" id="gmsg" role="status"></div></form></div></div>'
        . '<script>(function(){var f=document.getElementById("gform");function u(){var p=f.prefix.value.trim().toUpperCase().replace(/[^A-Z0-9-]/g,""),c=+f.cifer.value,a=+f.od.value||0,b=+f.do.value||0,z=function(n){n=String(n);while(n.length<c)n="0"+n;return p+n};'
        . 'document.getElementById("gp").textContent=z(a);document.getElementById("gp2").textContent=z(b);var n=b-a+1;document.getElementById("gn").textContent=n>0?" · "+n+(n===1?" poukaz":n<5?" poukazy":" poukazů"):" · neplatný interval";}'
        . 'f.addEventListener("input",u);u();'
        . 'var bz=document.getElementById("gbez");function t(){var on=bz.checked;f.querySelectorAll(".gnum").forEach(function(e){e.style.display=on?"none":""});f.querySelector(".gcnt").style.display=on?"":"none";f.od.required=f.do.required=!on;}bz.addEventListener("change",t);t();})();</script>'
        . <<<'JS'
<script>
(function(){
var f=document.getElementById("gform"),btn=document.getElementById("gbtn"),msg=document.getElementById("gmsg");
function say(t,bad){msg.textContent=t;msg.className="gmsg"+(bad?" bad":"");}
function b64(s){var b=atob(s),u=new Uint8Array(b.length);for(var i=0;i<b.length;i++)u[i]=b.charCodeAt(i);return u;}
f.addEventListener("submit",async function(e){
  if(!window.fetch)return;
  e.preventDefault();
  btn.disabled=true;say("Generuji poukazy…");
  try{
    var fd=new FormData(f);fd.set("json","1");
    var r=await fetch(location.href,{method:"POST",body:fd,credentials:"same-origin"});
    var d;try{d=await r.json();}catch(x){throw new Error("Server nevrátil poukazy (chyba "+r.status+").");}
    if(d.error)throw new Error(d.error);
    var blob=new Blob([b64(d.pdf)],{type:"application/pdf"}),url=URL.createObjectURL(blob);
    var a=document.createElement("a");a.href=url;a.download=d.name+".pdf";document.body.appendChild(a);a.click();a.remove();
    window.open(url,"_blank");
    if(d.single){say("Hotovo ✓ 1 poukaz (210 × "+(f.lay.value==="2"?"148,5":f.lay.value==="4"?"74":"99")+" mm) bez čísla.");return;}
    say("Hotovo ✓ "+d.count+" "+(d.count===1?"poukaz":d.count<5?"poukazy":"poukazů")+" na "+d.pages+" "+(d.pages===1?"stránce":"stránkách")+" A4. Tiskněte ve skutečné velikosti (100 %).");
  }catch(err){say(err.message,true);}
  finally{btn.disabled=false;}
});
})();
</script>
JS
        . '<style>.gen{max-width:640px;margin:10px auto}.gback{color:var(--mut);text-decoration:none;font-size:13px}.gen h2{margin:10px 0 6px}.gsub{color:var(--mut);margin:0 0 16px;font-size:14px}'
        . '.gform{display:grid;grid-template-columns:1fr 1fr;gap:12px 14px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px}'
        . '.gform label{display:flex;flex-direction:column;gap:5px;font-size:12.5px;font-weight:700;color:var(--mut)}'
        . '.gform input[type=text],.gform input[type=number],.gform select{padding:11px 12px;border-radius:9px;border:1px solid var(--line);background:#15113a;color:#fff;font:inherit;font-size:15px;font-weight:600}'
        . '.gform .gchk{flex-direction:row;align-items:center;font-weight:500;grid-column:1/-1}.gprev{grid-column:1/-1;font-size:14px;color:var(--mut)}.gprev b{color:var(--txt);letter-spacing:.06em}'
        . '.gform .btn{grid-column:1/-1;justify-content:center;padding:14px}.gform .btn[disabled]{opacity:.55;cursor:wait}'
        . '.glay{grid-column:1/-1;border:0;padding:0;margin:4px 0 0;display:grid;gap:8px}.glay legend{font-size:12.5px;font-weight:700;color:var(--mut);margin-bottom:6px;padding:0}'
        . '.gform .gopt{flex-direction:row;align-items:flex-start;gap:10px;padding:11px 13px;border:1px solid var(--line);border-radius:10px;cursor:pointer;color:var(--txt)}'
        . '.gform .gopt:has(input:checked){border-color:var(--acc);background:rgba(185,140,255,.08)}.gopt input{margin-top:3px;accent-color:#b066ff}'
        . '.gopt b{display:block;font-size:14px;color:var(--txt)}.gopt small{display:block;font-weight:500;color:var(--mut);font-size:12.5px;margin-top:1px}'
        . '.gmsg{grid-column:1/-1;font-size:13.5px;color:var(--mut);min-height:18px}.gmsg.bad{color:#ff6b8a;font-weight:600}@media(max-width:600px){.gform{grid-template-columns:1fr}}</style>';
    page('Vygenerovat poukazy', $body);
}

/* ---- akce ---- */
if ($isPost) {
    $back = $self . '?f=' . urlencode((string) ($_POST['f'] ?? 'platba')) . (!empty($_POST['kodq']) ? '&kod=' . urlencode((string) $_POST['kodq']) : '');
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

            case 'email':
                $subj = trim((string) ($_POST['predmet'] ?? ''));
                $msg = trim((string) ($_POST['zprava'] ?? ''));
                if ($subj === '' || $msg === '') {
                    throw new RuntimeException('Vyplňte předmět i zprávu.');
                }
                if (mb_strlen($subj) > 200 || mb_strlen($msg) > 10000) {
                    throw new RuntimeException('Předmět nebo zpráva je příliš dlouhá.');
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Zákazník nemá platný e-mail.');
                }
                $gold = ($o['kind'] ?? '') === 'gold';
                $inner = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="px" style="padding:34px 40px 34px;font-family:Manrope,\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:16px;line-height:25px;color:#e9e6ff;">' . nl2br(bb_h($msg)) . '</td></tr></table>';
                $m = bb_mailer($cfg);
                $m->addAddress($email, (string) ($o['customer']['name'] ?? ''));
                $m->Subject = $subj;
                $m->isHTML(true);
                $m->addEmbeddedImage(__DIR__ . '/objednavky/lib/assets/logo-email.png', 'bblogo', 'black-box.png');
                $m->Body = bb_email_layout($subj, $inner, $cfg, $gold);
                $m->AltBody = $msg;
                $m->send();
                $o['mails'] = $o['mails'] ?? [];
                $o['mails'][] = ['at' => time(), 'subject' => $subj];
                bb_save_order($DATA, $o);
                $_SESSION['flash'] = ['ok', "E-mail „{$subj}“ odeslán na $email."];
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
    header('Location: ' . $back . (!empty($_POST['id']) ? '#o-' . rawurlencode((string) $_POST['id']) : ''));
    exit;
}

/* ---- výpis ---- */
$all = bb_list_orders($DATA);
$f = (string) ($_GET['f'] ?? 'platba');
$q = trim((string) ($_GET['q'] ?? ''));
$kodq = bb_norm_code((string) ($_GET['kod'] ?? ''));

$TAB = [
    'platba'  => ['Čeká na zaplacení', fn($s) => $s === 'ceka', 'pay'],
    'odeslat' => ['Odeslat poukaz', fn($s) => $s === 'k_odeslani', 'post'],
    'platne'  => ['Zaplaceno', fn($s) => $s === 'platny', 'done'],
    'pouzite' => ['Použité', fn($s) => $s === 'pouzity', 'used'],
    'storno'  => ['Storno', fn($s) => $s === 'storno', 'storno'],
    'vse'     => ['Vše', fn($s) => true, 'all'],
];
if (!isset($TAB[$f])) {
    $f = 'platba';
}
$counts = array_fill_keys(array_keys($TAB), 0);
foreach ($all as $o) {
    $s = bb_voucher_state($o);
    foreach ($TAB as $k => $t) {
        if ($t[1]($s)) $counts[$k]++;
    }
}
$list = array_values(array_filter($all, function ($o) use ($f, $TAB, $q) {
    if ($q === '' && !$TAB[$f][1](bb_voucher_state($o))) return false;
    if ($q !== '') {
        $hay = mb_strtolower(implode(' ', [$o['no'] ?? '', $o['voucher'] ?? '', $o['customer']['name'], $o['customer']['email'], $o['customer']['phone'], $o['customer']['addr'] ?? '']));
        if (mb_strpos($hay, mb_strtolower($q)) === false) return false;
    }
    return true;
}));
// řazení podle toho, co je nejnaléhavější
$lateF = fn($o) => bb_voucher_state($o) === 'ceka' && !empty($o['due']) && (int) $o['due'] < time();
if ($q === '' && $f === 'platba') {
    usort($list, fn($a, $b) => [!$lateF($a), ($a['status'] ?? '') !== 'bez_faktury', (int) ($a['due'] ?? PHP_INT_MAX)] <=> [!$lateF($b), ($b['status'] ?? '') !== 'bez_faktury', (int) ($b['due'] ?? PHP_INT_MAX)]);
} elseif ($q === '' && $f === 'odeslat') {
    usort($list, fn($a, $b) => (int) ($a['paid_at'] ?? $a['created'] ?? 0) <=> (int) ($b['paid_at'] ?? $b['created'] ?? 0));
} elseif ($q === '' && $f === 'platne') {
    usort($list, fn($a, $b) => (int) ($b['paid_at'] ?? 0) <=> (int) ($a['paid_at'] ?? 0));
} elseif ($q === '' && $f === 'pouzite') {
    usort($list, fn($a, $b) => (int) ($b['used_at'] ?? 0) <=> (int) ($a['used_at'] ?? 0));
} else {
    usort($list, fn($a, $b) => (int) ($b['created'] ?? 0) <=> (int) ($a['created'] ?? 0));
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

/* jednotný stav objednávky pro seznam: klíč, štítek, ikona, další krok */
$ROWST = function (array $o) use ($dt): array {
    $st = (string) ($o['status'] ?? '');
    $vs = bb_voucher_state($o);
    $late = $vs === 'ceka' && !empty($o['due']) && (int) $o['due'] < time();
    if ($st === 'bez_faktury') return ['bezf', 'Bez faktury', '!', 'Vystavit a poslat fakturu'];
    if ($late) {
        $d = (int) floor((time() - (int) $o['due']) / 86400);
        return ['late', 'Po splatnosti', '!', 'Ověřit platbu na účtu' . ($d > 0 ? ' · ' . $d . ' ' . ($d === 1 ? 'den' : ($d < 5 ? 'dny' : 'dní')) . ' po splatnosti' : '')];
    }
    if ($vs === 'ceka') return ['ceka', 'Čeká na platbu', '…', !empty($o['due']) ? 'Splatnost ' . $dt($o['due']) : 'Čekáme na úhradu'];
    if ($vs === 'k_odeslani') return ['post', 'Odeslat poštou', '✉', 'Přidělit číslo a odeslat'];
    if ($vs === 'platny') return ['ok', 'Platný', '✓', 'Čeká na uplatnění zákazníkem'];
    if ($vs === 'pouzity') return ['used', 'Použitý', '●', 'Uplatněno ' . $dt($o['used_at'] ?? 0)];
    if ($vs === 'storno') return ['storno', 'Storno', '✕', 'Objednávka zrušena'];
    return ['ceka', $st, '…', ''];
};
$GROUPS = ['late' => 'Po splatnosti', 'bezf' => 'Bez faktury', 'post' => 'Odeslat poštou', 'ceka' => 'Čeká na platbu', 'ok' => 'Platné poukazy', 'used' => 'Použité', 'storno' => 'Stornované'];

/* jedna karta objednávky */
$card = function (array $o, bool $hi = false, bool $showGroup = false) use ($h, $btn, $hid, $SBADGE, $dt, $f, $ROWST) {
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

    // řádek seznamu (souhrn)
    [$sk, $sl, $si, $next] = $ROWST($o);
    $paidX = in_array($st, ['zaplaceno', 'vyrizeno'], true);
    // časová osa: objednáno → zaplaceno → odesláno → použito (s daty)
    $sentAt = !empty($o['voucher_sent_at']) ? (int) $o['voucher_sent_at'] : ($dl !== 'posta' && $paidX ? (int) ($o['paid_at'] ?? 0) : 0);
    $TL = [['Objednáno', (int) ($o['created'] ?? 0), true]];
    $TL[] = [$paidX ? 'Zaplaceno' : 'Platba', $paidX ? (int) ($o['paid_at'] ?? 0) : (int) ($o['due'] ?? 0), $paidX];
    if ($hasV) {
        $TL[] = [$dl === 'posta' ? 'Pošta' : 'E-mail', $sentAt, $sentAt > 0 || ($dl !== 'posta' && $paidX)];
        $TL[] = ['Použito', (int) ($o['used_at'] ?? 0), !empty($o['used_at'])];
    }
    $mini = '<ol class="tl">';
    $curSet = $sk === 'storno';
    foreach ($TL as $n => [$t, $ts, $done]) {
        $cls = $done ? 'd' : (!$curSet ? 'c' : '');
        if (!$done) $curSet = true;
        if ($n === 1 && !$done && $sk === 'late') $cls .= ' x';
        $sub = $ts ? date('j. n.', $ts) : '–';
        if ($n === 1 && !$done) $sub = $ts ? 'do ' . date('j. n.', $ts) : '–';
        $mini .= '<li class="' . trim($cls) . '"><b>' . $h($t) . '</b><small>' . $h($sub) . '</small></li>';
    }
    $mini .= '</ol>';
    // ---- karta: kdo | časová osa | co udělat ----
    $G = ['ceka' => 'pay', 'k_odeslani' => 'post', 'platny' => 'done', 'pouzity' => 'used', 'storno' => 'storno'][$vs] ?? 'pay';
    $GL = ['pay' => 'Čeká na zaplacení', 'post' => 'Odeslat poukaz', 'done' => 'Zaplaceno', 'used' => 'Použito', 'storno' => 'Storno'];
    $isLate = $sk === 'late';
    $price = bb_money((float) $o['product']['price']);
    $tel = !empty($c['phone']) ? '<a class="btn ghost sm" href="tel:' . $h(preg_replace('/\s+/', '', (string) $c['phone'])) . '">☎ Zavolat</a>' : '';
    $tel .= !empty($c['email']) ? '<a class="btn ghost sm js-mail" href="mailto:' . $h($c['email']) . '" data-id="' . $h($id) . '" data-to="' . $h($c['email']) . '" data-name="' . $h($c['name'] ?? '') . '" data-voucher="' . $h($o['voucher'] ?? '') . '" data-no="' . $h($o['no'] ?? '') . '" data-vs="' . $h($o['vs'] ?? '') . '" data-price="' . $h(bb_money((float) $o['product']['price'])) . '">✉ E-mail</a>' : '';
    $info = '';
    $acts = '';
    $under = '';
    if ($G === 'pay') {
        if ($st === 'bez_faktury') {
            $info = '<span class="bad">Faktura zatím nevystavena</span>';
            $acts = $btn($o, 'vystavit', 'Vystavit fakturu', 'main', 'Vystavit fakturu a poslat ji na ' . $c['email'] . '?') . $tel;
        } else {
            $dd = $isLate ? (int) floor((time() - (int) $o['due']) / 86400) : 0;
            $info = ($isLate ? '<span class="bad">Po splatnosti' . ($dd > 0 ? ' ' . $dd . ' ' . ($dd === 1 ? 'den' : ($dd < 5 ? 'dny' : 'dní')) : '') . '</span>'
                    : (!empty($o['due']) ? 'Splatnost <b>' . $h($dt($o['due'])) . '</b>' : 'Čekáme na úhradu'))
                . (!empty($o['vs']) ? ' · VS <b>' . $h($o['vs']) . '</b>' : '');
            $lbl = (!$hasV || $dl === 'posta') ? '✓ Platba přišla' : '✓ Platba přišla + poslat poukaz';
            $acts = $btn($o, 'zaplaceno', $lbl, 'main', 'Potvrdit platbu ' . $price . ' od ' . bb_who($o) . '?' . ($hasV && $dl !== 'posta' ? ' Zákazníkovi se zároveň pošle poukaz e-mailem.' : ''))
                . $tel . (!empty($o['no']) ? '<a class="btn ghost sm" href="?dl=faktura&id=' . urlencode($id) . '" target="_blank">Faktura</a>' : '');
        }
    } elseif ($G === 'post') {
        $info = '';
        $under = '<div class="padr">' . (!empty($c['addr']) ? '<b>' . $h($c['name']) . '</b>, ' . $h($c['addr']) : '<span class="bad">Adresa chybí – doplňte v detailu</span>') . '</div>';
        $acts = '<form method="post" class="pform" onsubmit="return confirm(\'Poukaz byl opravdu odeslán poštou?\')">' . $hid($o, 'posta') . '<input type="hidden" name="odeslano" value="1">'
            . '<input class="pcode" type="text" name="kod" value="' . $h($o['voucher'] ?? '') . '" placeholder="Číslo poukazu" maxlength="20" required autocomplete="off">'
            . '<a class="btn ghost sm" href="?dl=poukaz&id=' . urlencode($id) . '" target="_blank">Tisk PDF</a>'
            . '<button class="btn main" type="submit">✓ Odesláno</button>'
            . '<label class="chk"><input type="checkbox" name="notify" value="1" checked><span>poslat zákazníkovi e-mail</span></label></form>';
    } elseif ($G === 'done') {
        $info = (!empty($o['voucher']) ? 'Poukaz <b class="code">' . $h($o['voucher']) . '</b> · ' : '') . ($dl === 'posta' ? 'poštou' : 'e-mailem')
            . (!empty($o['voucher_sent_at']) ? ' ' . $h($dt($o['voucher_sent_at'])) : '');
        if (!empty($o['voucher'])) {
            $acts = '<form method="post" class="uform" onsubmit="return confirm(\'Označit poukaz ' . $h($o['voucher']) . ' jako POUŽITÝ? Znovu už ho uplatnit nepůjde.\')">' . $hid($o, 'pouzit')
                . '<input type="text" name="pozn" maxlength="120" placeholder="Poznámka (např. termín 24. 10.)"><button class="btn main" type="submit">Uplatnit</button></form>';
        }
        if ($hasV) $acts .= '<a class="btn ghost sm" href="?dl=poukaz&id=' . urlencode($id) . '" target="_blank">Poukaz PDF</a>';
    } elseif ($G === 'used') {
        $info = 'Uplatněno <b>' . $h($dt($o['used_at'] ?? 0, 'j. n. Y H:i')) . '</b>' . (($o['used_note'] ?? '') !== '' ? ' · ' . $h($o['used_note']) : '') . (!empty($o['voucher']) ? ' · ' . $h($o['voucher']) : '');
        $acts = $btn($o, 'vratit', 'Zrušit použití', 'ghost sm', 'Opravdu vrátit poukaz ' . ($o['voucher'] ?? '') . ' do stavu platný?');
    } else {
        $info = 'Objednávka zrušena';
        $acts = $btn($o, 'obnovit', 'Obnovit', 'ghost sm');
    }
    $out = '<article class="card g-' . $G . ($isLate ? ' late' : '') . ($gold ? ' gold' : '') . ($hi ? ' hi' : '') . '" id="o-' . $h($id) . '">'
        . '<div class="cmain">'
        . '<div class="who"><div class="nm">' . $h($c['name'] ?: $c['email']) . '</div>'
        . '<div class="wm"><span class="dic ' . ($dl === 'posta' ? 'd-post' : 'd-mail') . '" title="' . ($dl === 'posta' ? 'Poukaz poštou' . (!empty($c['addr']) ? ': ' . $h($c['addr']) : ' – adresa chybí') : 'Poukaz e-mailem (PDF)') . '">'
            . ($dl === 'posta'
                ? '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/><path d="M7.5 5.5l9 5"/></svg>'
                : '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>')
            . '</span><span class="tag' . ($gold ? ' gold' : '') . '">' . ($gold ? 'Gold Time' : 'Standard Time') . '</span><span class="pr">' . $h($price) . '</span>' . (!empty($o['no']) ? '<span>' . $h($o['no']) . '</span>' : '') . '</div>'
        . ($showGroup ? '<span class="grp">' . $GL[$G] . '</span>' : '') . '</div>'
        . '<div class="ctl">' . $mini . $under . '</div>'
        . '<div class="cact">' . ($info !== '' ? '<div class="info">' . $info . '</div>' : '') . '<div class="btns">' . $acts . '</div></div>'
        . '</div>'
        . '<details class="det"' . ($hi ? ' open' : '') . '><summary>Detail a další možnosti</summary>';
    $out .= '<div class="o' . ($gold ? ' gold' : '') . '">';
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
        . '<div><span>Kontakt</span><a href="tel:' . $h(preg_replace('/\s+/', '', $c['phone'])) . '">' . $h($c['phone']) . '</a><br><a class="js-mail" href="mailto:' . $h($c['email']) . '" data-id="' . $h($id) . '" data-to="' . $h($c['email']) . '" data-name="' . $h($c['name'] ?? '') . '" data-voucher="' . $h($o['voucher'] ?? '') . '" data-no="' . $h($o['no'] ?? '') . '" data-vs="' . $h($o['vs'] ?? '') . '" data-price="' . $h(bb_money((float) $o['product']['price'])) . '">' . $h($c['email']) . '</a></div>'
        . '<div><span>Doručení poukazu</span>' . ($hasV ? ($dl === 'posta' ? '<b>Poštou</b><br>' . $h($c['name']) . '<br>' . $h($c['addr'] ?? '') : '<b>' . $h(ucfirst(bb_delivery_label($o))) . '</b>') : '<i class="mut">bez poukazu</i>') . '</div>'
        . ($hasV ? '<div><span>Číslo poukazu</span>' . (!empty($o['voucher']) ? '<b class="code">' . $h($o['voucher']) . '</b>' : '<i class="mut">zatím nepřiděleno</i>') . '</div>' : '')
        . ($c['company'] ? '<div><span>Firma</span>' . $h($c['company']) . ($c['ico'] ? '<br>IČ ' . $h($c['ico']) : '') . '</div>' : '')
        . '</div>';
    if (!empty($o['mails'])) {
        $out .= '<div class="mailhist"><span>Odeslané e-maily</span>' . implode('', array_map(fn($m) => '<div>✉ ' . $h(date('j. n. Y H:i', (int) $m['at'])) . ' · ' . $h($m['subject']) . '</div>', array_reverse($o['mails']))) . '</div>';
    }
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

    $out .= '<details class="more" open><summary>Další možnosti</summary><div class="act">' . implode('', $sec) . '</div>' . $postbox
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
    $out .= '</details></div></details></article>';
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
        $out .= '<div class="verdict ' . $V[0] . '"><b>' . $h($V[1]) . '</b><span>' . $h($V[2]) . '</span></div>' . '<div class="list solo">' . $card($found, true) . '</div>';
    }
    $out .= '<p class="meta" style="margin:-4px 0 22px"><a href="?f=' . $h($f) . '">× zavřít ověření</a></p>';
}

/* hlavní přepínač: 1 Čeká na zaplacení → 2 Odeslat poukaz → 3 Zaplaceno */
$sumF = fn(array $r) => array_sum(array_map(fn($o) => (float) ($o['product']['price'] ?? 0), $r));
$inTab = fn(string $k) => array_values(array_filter($all, fn($o) => $TAB[$k][1](bb_voucher_state($o))));
$STEPS = [['platba', 1, 'pay'], ['odeslat', 2, 'post'], ['platne', 3, 'done']];
$out .= '<nav class="steps">';
foreach ($STEPS as [$k, $n, $cls]) {
    $rows = $inTab($k);
    if (!$rows) {
        $note = 'Hotovo ✓ nic nečeká';
    } elseif ($k === 'platba') {
        $l = count(array_filter($rows, $lateF));
        $note = $l ? '<span class="bad">' . $l . '× po splatnosti</span>' : 'vše v termínu';
    } elseif ($k === 'odeslat') {
        $old = max(array_map(fn($o) => (int) floor((time() - (int) ($o['paid_at'] ?? $o['created'] ?? time())) / 86400), $rows));
        $note = $old > 0 ? 'nejdéle čeká ' . $old . ' ' . ($old === 1 ? 'den' : ($old < 5 ? 'dny' : 'dní')) : 'zaplaceno dnes';
    } else {
        $note = 'čekají na uplatnění';
    }
    $out .= '<a class="step s-' . $cls . ($f === $k && $q === '' ? ' on' : '') . ($rows ? '' : ' zero') . '" href="?f=' . $k . '">'
        . '<span class="sh"><span class="num">' . $n . '</span><span class="stt">' . $TAB[$k][0] . '</span></span>'
        . '<span class="scnt"><b>' . count($rows) . '</b><span>' . $h(bb_money($sumF($rows))) . '</span></span>'
        . '<span class="snote">' . $note . '</span></a>';
}
$out .= '</nav>';
$out .= '<div class="sbar"><form class="ssearch" method="get"><input type="hidden" name="f" value="' . $h($f) . '"><input type="search" name="q" value="' . $h($q) . '" placeholder="Hledat jméno, telefon, e-mail, VS, číslo poukazu…"></form>'
    . '<div class="sside">';
foreach (['pouzite', 'storno', 'vse'] as $k) {
    $out .= '<a class="' . ($f === $k && $q === '' ? 'on' : '') . '" href="?f=' . $k . '">' . $TAB[$k][0] . '<b>' . $counts[$k] . '</b></a>';
}
$out .= '<a href="?export=csv">Export</a></div></div>';
$TITLE = ['platba' => 'Čeká na zaplacení', 'odeslat' => 'Odeslat poukaz', 'platne' => 'Zaplaceno – poukaz je u zákazníka', 'pouzite' => 'Použité poukazy', 'storno' => 'Stornované objednávky', 'vse' => 'Všechny objednávky'];
$nPost = count($inTab('odeslat'));
$nP = count(array_filter($list, fn($o) => bb_delivery($o) === 'posta'));
$dlSum = ($f === 'platba' && $q === '' && $list) ? '<span class="dls"><b class="dp">✉ ' . $nP . '× poštou</b><b class="dm">@ ' . (count($list) - $nP) . '× e-mailem</b></span>' : '';
$out .= '<div class="lhead"><h2>' . ($q !== '' ? 'Výsledky hledání „' . $h($q) . '“' : $TITLE[$f]) . '</h2>'
    . '<div class="lhr">' . ($f === 'odeslat' && $q === '' ? '<a class="btn lbl ghostb" href="?generovat=1">Vygenerovat poukazy</a>' . ($nPost ? '<a class="btn lbl" href="?stitky=1" target="_blank">Vygenerovat štítky (' . $nPost . ')</a>' : '') : '')
    . '<span>' . count($list) . ' ks · ' . $h(bb_money($sumF($list))) . '</span></div></div>';

if (!bb_db()) {
    $out .= '<div class="warn">Databáze není nastavená – objednávky se ukládají do souborů. Doplňte údaje <code>db</code> v <code>objednavky/config.php</code>.</div>';
}
if (!bb_iban((string) $cfg['bank_account'])) {
    $out .= '<div class="warn">V <code>objednavky/config.php</code> chybí číslo účtu – faktury se zatím nevystavují automaticky.</div>';
}
if (!$list) {
    $out .= '<div class="empty"><b>' . ($q !== '' ? 'Nic jsme nenašli' : 'Hotovo ✓') . '</b>' . ($q !== '' ? 'Zkuste jiné jméno nebo číslo.' : 'V této části teď nic nečeká.') . '</div>';
} else {
    foreach ($list as $o) {
        $out .= $card($o, false, $q !== '' || $f === 'vse');
    }
}
/* modál: e-mail zákazníkovi */
$out .= '<dialog class="mdlg" id="mailDlg"><form method="post" class="mform" id="mailForm">'
    . '<input type="hidden" name="csrf" value="' . $h($_SESSION['csrf'] ?? '') . '"><input type="hidden" name="a" value="email"><input type="hidden" name="id" value=""><input type="hidden" name="f" value="' . $h($f) . '">'
    . '<div class="mhead"><div><b>Napsat zákazníkovi</b><small id="mailTo"></small></div><button type="button" class="mx" id="mailX" aria-label="Zavřít">✕</button></div>'
    . '<label class="ml">Šablona<select id="mailTpl"><option value="">— vlastní zpráva —</option><option value="poslan">Poukaz je na cestě</option><option value="platba">Připomínka platby</option><option value="termin">Domluva termínu</option><option value="dekujeme">Poděkování</option></select></label>'
    . '<label class="ml">Předmět<input type="text" name="predmet" id="mailSubj" maxlength="200" required></label>'
    . '<label class="ml">Zpráva<textarea name="zprava" id="mailMsg" rows="11" required></textarea></label>'
    . '<div class="mfoot"><span class="mhint">Odejde ve stejném vzhledu jako faktury a poukazy.</span><button type="button" class="btn ghost sm" id="mailCancel">Zrušit</button><button type="submit" class="btn" id="mailSend">Odeslat e-mail</button></div>'
    . '</form></dialog>';
$out .= <<<'HTML'
<style>
.mdlg{border:0;padding:0;background:transparent;width:min(620px,94vw);max-height:92vh;color:var(--txt)}
.mdlg::backdrop{background:rgba(5,3,18,.72);backdrop-filter:blur(3px)}
.mform{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:12px}
.mhead{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.mhead b{display:block;font-size:18px}.mhead small{display:block;color:var(--mut);font-size:13.5px;margin-top:2px}
.mx{background:none;border:1px solid var(--line);color:var(--mut);border-radius:9px;width:34px;height:34px;cursor:pointer;font-size:15px}
.ml{display:flex;flex-direction:column;gap:5px;font-size:12.5px;font-weight:700;color:var(--mut)}
.ml input,.ml select,.ml textarea{padding:11px 12px;border-radius:10px;border:1px solid var(--line);background:#15113a;color:#fff;font:inherit;font-size:15px;font-weight:500}
.ml textarea{resize:vertical;min-height:180px;line-height:1.5}
.ml input:focus,.ml select:focus,.ml textarea:focus{outline:none;border-color:var(--acc)}
.mfoot{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.mhint{flex:1;color:var(--mut);font-size:12.5px;min-width:160px}
.mfoot .btn[disabled]{opacity:.55;cursor:wait}
@media(max-width:600px){.mdlg{width:100vw;max-width:100vw;max-height:100dvh;height:100dvh;margin:0}.mform{border-radius:0;min-height:100%}.mfoot .btn{flex:1;justify-content:center}}
</style>
<script>
(function(){var D=document.getElementById('mailDlg');if(!D)return;var F=document.getElementById('mailForm'),S=document.getElementById('mailSubj'),M=document.getElementById('mailMsg'),T=document.getElementById('mailTpl'),cur={};
var sig='\n\nS pozdravem\nBlack Box\n+420 737 22 22 66';
function hi(){return 'Dobrý den'+(cur.name?', '+cur.name.trim():'')+',\n\n';}
function base(){return hi()+sig.replace(/^\n\n/,'');}
var TPL={
 poslan:function(){return['Váš dárkový poukaz je na cestě'+(cur.voucher?' ('+cur.voucher+')':''),hi()+'dárkový poukaz'+(cur.voucher?' č. '+cur.voucher:'')+' jsme Vám dnes odeslali poštou. Měl by dorazit během 1–3 pracovních dnů.\n\nTermín návštěvy si obdarovaný domluví telefonem na +420 737 22 22 66.'+sig];},
 platba:function(){return['Připomínka platby'+(cur.no?' – faktura '+cur.no:''),hi()+'dovolujeme si Vám připomenout platbu'+(cur.price?' ve výši '+cur.price:'')+'.'+(cur.vs?'\nVariabilní symbol: '+cur.vs:'')+'\n\nPokud jste již zaplatili, považujte prosím tuto zprávu za bezpředmětnou. Děkujeme.'+sig];},
 termin:function(){return['Domluva termínu – Black Box',hi()+'rádi bychom s Vámi domluvili termín návštěvy. Napište nám prosím, který den a čas by Vám vyhovoval, nebo zavolejte na +420 737 22 22 66.'+sig];},
 dekujeme:function(){return['Děkujeme za návštěvu Black Box',hi()+'děkujeme, že jste nás navštívili. Doufáme, že se Vám u nás líbilo, a budeme se těšit příště.'+sig];}
};
var dirty=false;S.addEventListener('input',function(){dirty=true});M.addEventListener('input',function(){dirty=true});
function touched(){return dirty&&(S.value.trim()!==''||M.value.trim()!==base().trim());}
T.addEventListener('change',function(){if(!T.value)return;if(touched()&&!confirm('Přepsat rozepsanou zprávu šablonou?')){T.value='';return;}var r=TPL[T.value]();S.value=r[0];M.value=r[1];dirty=false;M.focus();M.setSelectionRange(0,0);});
function open(a){cur={id:a.dataset.id,to:a.dataset.to,name:a.dataset.name,voucher:a.dataset.voucher,no:a.dataset.no,vs:a.dataset.vs,price:a.dataset.price};
 F.querySelector('input[name=id]').value=cur.id;document.getElementById('mailTo').textContent=(cur.name?cur.name+' · ':'')+cur.to;
 T.value='';S.value='';M.value=base();dirty=false;var b=document.getElementById('mailSend');b.disabled=false;b.textContent='Odeslat e-mail';
 if(D.showModal)D.showModal();else D.setAttribute('open','');setTimeout(function(){S.focus()},50);}
function close(){if(touched()&&!confirm('Zahodit rozepsaný e-mail?'))return;if(D.close)D.close();else D.removeAttribute('open');}
document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a.js-mail');if(!a)return;e.preventDefault();e.stopPropagation();open(a);},true);
document.getElementById('mailX').onclick=close;document.getElementById('mailCancel').onclick=close;
D.addEventListener('cancel',function(e){e.preventDefault();close();});
D.addEventListener('click',function(e){if(e.target===D)close();});
F.addEventListener('submit',function(e){if(!S.value.trim()||!M.value.trim()){e.preventDefault();alert('Vyplňte předmět i zprávu.');return;}
 if(!confirm('Odeslat e-mail na '+cur.to+'?')){e.preventDefault();return;}var b=document.getElementById('mailSend');b.disabled=true;b.textContent='Odesílám…';dirty=false;});
})();
</script>
HTML;

$out .= '</div>';
page('Objednávky', $out);
