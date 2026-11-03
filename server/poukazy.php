<?php
/* Black Box – přehledná správa poukazů (1 Čeká na zaplacení → 2 Odeslat poukaz → 3 Zaplaceno)
 * Samostatná stránka. Data čte přes objednavky/lib/app.php, akce provádí původní sprava.php
 * (stejné přihlášení, stejná CSRF ochrana, stejné e-maily). sprava.php se nemění.
 */
declare(strict_types=1);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$BASE = __DIR__ . '/objednavky';
$cfg = require $BASE . '/config.php';
require_once $BASE . '/lib/app.php';
$DATA = $BASE . '/data';

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('bbsprava');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $secure, 'samesite' => 'Strict']);
session_start();

$PW = (string) ($cfg['admin_password'] ?? '');
$self = strtok($_SERVER['REQUEST_URI'] ?? 'poukazy.php', '?') ?: 'poukazy.php';
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

function shell(string $title, string $body): void
{
    echo '<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
        . '<title>' . $title . '</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0d0b16;color:#eeeaf7;font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        . '.box{width:min(380px,90vw);background:#17142a;border:1px solid #2b2645;border-radius:16px;padding:30px;text-align:center}h1{font-size:20px;margin:0 0 6px}p{color:#9d97b3;margin:0 0 18px}'
        . 'input{width:100%;box-sizing:border-box;padding:13px 14px;border-radius:10px;border:1px solid #3a3360;background:#0d0b16;color:#fff;font:inherit}button{margin-top:12px;width:100%;padding:13px;border:0;border-radius:10px;background:#8d6bff;color:#fff;font:700 15px inherit;font-family:inherit;cursor:pointer}'
        . '.err{color:#ff7b8f;margin-top:14px}</style></head><body>' . $body . '</body></html>';
    exit;
}

if ($PW === '') {
    shell('Správa vypnutá', '<div class="box"><h1>Správa je vypnutá</h1><p>V objednavky/config.php chybí admin_password.</p></div>');
}

if (isset($_GET['odhlasit'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $self);
    exit;
}

/* ---- přihlášení (stejné heslo i ochrana proti hádání jako sprava.php) ---- */
if (empty($_SESSION['bb_admin'])) {
    if (isset($_GET['data'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo '{"login":true}';
        exit;
    }
    $lockFile = $DATA . '/prihlaseni.json';
    $ipKey = sha1($_SERVER['REMOTE_ADDR'] ?? '0');
    $locks = is_file($lockFile) ? (json_decode((string) file_get_contents($lockFile), true) ?: []) : [];
    $fails = array_values(array_filter($locks[$ipKey] ?? [], fn($t) => $t > time() - 900));
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
    shell('Přihlášení', '<form class="box" method="post"><h1>Správa poukazů</h1><p>Black Box</p>'
        . '<input type="password" name="heslo" placeholder="Heslo" autocomplete="current-password" autofocus required><button type="submit">Přihlásit</button>'
        . ($err ? '<div class="err">' . $e($err) . '</div>' : '') . '</form>');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ---- data pro stránku (JSON) ---- */
if (isset($_GET['data'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $GROUP = ['ceka' => 'pay', 'k_odeslani' => 'post', 'platny' => 'done', 'pouzity' => 'used', 'storno' => 'storno'];
        $items = [];
        foreach (bb_list_orders($DATA) as $o) {
            $c = $o['customer'] ?? [];
            $st = (string) ($o['status'] ?? '');
            $paid = in_array($st, ['zaplaceno', 'vyrizeno'], true);
            $hasV = bb_has_voucher($o);
            $posta = bb_delivery($o) === 'posta';
            $state = bb_voucher_state($o);
            $sent = !empty($o['voucher_sent_at']) ? (int) $o['voucher_sent_at'] : (!$posta && $paid ? (int) ($o['paid_at'] ?? 0) : 0);
            $items[] = [
                'id' => (string) ($o['id'] ?? ''),
                'g' => $GROUP[$state] ?? 'pay',
                'name' => (string) (($c['name'] ?? '') ?: ($c['email'] ?? '')),
                'email' => (string) ($c['email'] ?? ''),
                'phone' => (string) ($c['phone'] ?? ''),
                'addr' => (string) ($c['addr'] ?? ''),
                'company' => (string) ($c['company'] ?? ''),
                'no' => (string) ($o['no'] ?? ''),
                'vs' => (string) ($o['vs'] ?? ''),
                'code' => (string) ($o['voucher'] ?? ''),
                'gold' => ($o['kind'] ?? '') === 'gold',
                'price' => (float) ($o['product']['price'] ?? 0),
                'noInvoice' => $st === 'bez_faktury',
                'paid' => $paid,
                'hasV' => $hasV,
                'posta' => $posta,
                'created' => (int) ($o['created'] ?? 0),
                'due' => (int) ($o['due'] ?? 0),
                'paidAt' => (int) ($o['paid_at'] ?? 0),
                'sentAt' => $sent,
                'usedAt' => (int) ($o['used_at'] ?? 0),
                'usedNote' => (string) ($o['used_note'] ?? ''),
            ];
        }
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        echo json_encode(['items' => $items, 'csrf' => $_SESSION['csrf'], 'flash' => $flash, 'now' => time()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $ex) {
        http_response_code(500);
        echo json_encode(['error' => 'Objednávky se nepodařilo načíst: ' . $ex->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?><!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Poukazy · Black Box</title>
<style>
:root{
  --bg:#0d0b16;--panel:#16132a;--card:#1b1830;--line:#2b2645;--line2:#3a3360;
  --txt:#f1eef9;--mut:#9d97b3;--dim:#6f6987;
  --pay:#f5a524;--post:#3ea8ff;--done:#2fcf8a;--late:#ff5d73;--gold:#e7c47c;--acc:#8d6bff;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font:15px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit}
button,input{font:inherit;color:inherit}
.wrap{max-width:1180px;margin:0 auto;padding:22px 20px 60px}

/* hlavička */
.top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}
.brand{font-weight:800;letter-spacing:.18em;font-size:14px}
.brand small{display:block;font-weight:500;letter-spacing:.04em;color:var(--mut);font-size:12.5px;margin-top:2px}
.toplinks{display:flex;gap:6px;flex-wrap:wrap}
.lnk{padding:8px 12px;border:1px solid var(--line);border-radius:9px;text-decoration:none;color:var(--mut);font-size:13px;background:transparent;cursor:pointer}
.lnk:hover{color:var(--txt);border-color:var(--line2)}

/* ===== hlavní přepínač – 3 kroky ===== */
.steps{display:grid;grid-template-columns:1fr 1fr 1fr;gap:26px;margin-bottom:18px}
.step{--c:var(--mut);position:relative;text-align:left;cursor:pointer;background:var(--panel);border:2px solid var(--line);border-radius:16px;padding:16px 18px 15px;transition:border-color .15s,background .15s,transform .15s}
.step:hover{border-color:color-mix(in srgb,var(--c) 55%,var(--line))}
.step:not(:last-child)::after{content:"";position:absolute;right:-21px;top:50%;width:14px;height:14px;margin-top:-7px;border-top:3px solid var(--line2);border-right:3px solid var(--line2);transform:rotate(45deg);border-radius:2px}
.step.s-pay{--c:var(--pay)}.step.s-post{--c:var(--post)}.step.s-done{--c:var(--done)}
.step .hd{display:flex;align-items:center;gap:10px}
.step .num{flex:none;width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:14px;background:color-mix(in srgb,var(--c) 18%,transparent);color:var(--c)}
.step .ttl{font-weight:750;font-size:16px}
.step .cnt{display:flex;align-items:baseline;gap:10px;margin-top:10px}
.step .cnt b{font-size:38px;line-height:1;font-weight:800;color:var(--c);font-variant-numeric:tabular-nums}
.step .cnt span{color:var(--mut);font-size:13.5px;font-variant-numeric:tabular-nums}
.step .note{margin-top:8px;font-size:13px;color:var(--mut);min-height:19px}
.step .note .bad{color:var(--late);font-weight:700}
.step.on{background:color-mix(in srgb,var(--c) 13%,var(--panel));border-color:var(--c);box-shadow:0 8px 30px -12px color-mix(in srgb,var(--c) 60%,transparent)}
.step.on .num{background:var(--c);color:#0d0b16}
.step.zero:not(.on) .cnt b{color:var(--dim)}
.step.zero:not(.on){opacity:.75}

/* lišta pod přepínačem */
.bar{display:flex;align-items:center;gap:10px;margin:0 0 14px;flex-wrap:wrap}
.search{flex:1;min-width:220px;position:relative}
.search input{width:100%;padding:12px 14px 12px 40px;border-radius:11px;border:1px solid var(--line);background:var(--panel);outline:none}
.search input:focus{border-color:var(--acc)}
.search::before{content:"";position:absolute;left:15px;top:50%;width:12px;height:12px;margin-top:-8px;border:2px solid var(--dim);border-radius:50%}
.search::after{content:"";position:absolute;left:27px;top:50%;width:6px;height:2px;margin-top:5px;background:var(--dim);transform:rotate(45deg)}
.side{display:flex;gap:6px}
.side button{padding:9px 12px;border-radius:10px;border:1px solid var(--line);background:transparent;color:var(--mut);cursor:pointer;font-size:13px}
.side button b{color:var(--txt);margin-left:4px}
.side button.on{background:var(--line);color:var(--txt);border-color:var(--line2)}

.head{display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin:6px 2px 10px}
.head h2{margin:0;font-size:18px}
.head span{color:var(--mut);font-size:13px}

/* ===== karta objednávky ===== */
.card{--c:var(--mut);display:grid;grid-template-columns:minmax(200px,1.15fr) minmax(230px,1.2fr) minmax(280px,1.45fr);gap:18px;align-items:center;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--c);border-radius:14px;padding:15px 18px;margin-bottom:10px}
.card.g-pay{--c:var(--pay)}.card.g-post{--c:var(--post)}.card.g-done{--c:var(--done)}.card.g-used,.card.g-storno{--c:var(--dim)}
.card.late{--c:var(--late);background:linear-gradient(90deg,rgba(255,93,115,.08),var(--card) 45%)}
.card.g-used,.card.g-storno{opacity:.7}
.who .nm{font-weight:750;font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.who .meta{display:flex;align-items:center;gap:8px;margin-top:4px;flex-wrap:wrap;font-size:13px;color:var(--mut)}
.tag{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11.5px;font-weight:700;letter-spacing:.02em;background:rgba(141,107,255,.16);color:#c2b2ff}
.tag.gold{background:rgba(231,196,124,.15);color:var(--gold)}
.price{font-weight:750;color:var(--txt);font-variant-numeric:tabular-nums}
.grp{display:inline-block;margin-top:6px;font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:6px;background:color-mix(in srgb,var(--c) 15%,transparent);color:var(--c)}

/* časová osa – čára se vybarví jen mezi dvěma HOTOVÝMI kroky */
.tl{list-style:none;margin:0;padding:0;display:grid;grid-auto-flow:column;grid-auto-columns:1fr}
.tl li{position:relative;text-align:center;padding-top:18px;min-width:0}
.tl li::before{content:"";position:absolute;top:3px;left:50%;width:12px;height:12px;margin-left:-6px;border-radius:50%;box-sizing:border-box;border:2px solid var(--line2);background:var(--card);z-index:1}
.tl li+li::after{content:"";position:absolute;top:8px;right:50%;width:100%;height:2px;background:var(--line2)}
.tl li.d::before{background:var(--c);border-color:var(--c)}
.tl li.ln::after{background:var(--c)}
.tl li.c::before{border-color:var(--c);box-shadow:0 0 0 4px color-mix(in srgb,var(--c) 22%,transparent)}
.tl li.x::before{border-color:var(--late)}
.tl b{display:block;font-size:11.5px;font-weight:650;color:var(--dim);white-space:nowrap}
.tl small{display:block;font-size:12px;color:var(--dim);font-variant-numeric:tabular-nums;white-space:nowrap}
.tl li.d b{color:var(--txt)}.tl li.d small{color:var(--mut)}
.tl li.c b{color:var(--c)}.tl li.c small{color:var(--mut)}
.tl li.x b,.tl li.x small{color:var(--late);font-weight:700}

/* akce */
.act{display:flex;flex-direction:column;gap:8px;min-width:0}
.info{font-size:13px;color:var(--mut)}
.info b{color:var(--txt);font-weight:650}
.info .bad{color:var(--late);font-weight:700}
.addr{font-size:13px;line-height:1.4;padding:7px 10px;border-radius:9px;border:1px dashed color-mix(in srgb,var(--post) 45%,transparent);background:rgba(62,168,255,.06)}
.btns{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;border-radius:10px;border:1px solid var(--line2);background:transparent;color:var(--txt);text-decoration:none;font-size:13.5px;font-weight:650;cursor:pointer;white-space:nowrap}
.btn:hover{border-color:var(--mut)}
.btn.main{background:var(--c);border-color:var(--c);color:#0d0b16;font-weight:800}
.btn.main:hover{filter:brightness(1.08)}
.btn.sm{padding:8px 11px;font-size:12.5px}
.btn[disabled]{opacity:.5;cursor:wait}
.code{flex:1;min-width:120px;padding:10px 12px;border-radius:10px;border:1px solid var(--post);background:#0f0d1c;outline:none;font-weight:750;letter-spacing:.08em;text-transform:uppercase}
.chk{display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--mut);cursor:pointer}
.chk input{accent-color:var(--post)}
.more{font-size:12.5px;color:var(--dim);text-decoration:none}
.more:hover{color:var(--txt)}
.use{display:none;gap:8px;width:100%}
.use.open{display:flex}
.use input{flex:1;min-width:120px;padding:9px 11px;border-radius:10px;border:1px solid var(--line2);background:#0f0d1c;outline:none}

.empty{text-align:center;padding:50px 20px;border:1px dashed var(--line2);border-radius:14px;color:var(--mut)}
.empty b{display:block;font-size:17px;color:var(--txt);margin-bottom:4px}
.toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%);max-width:min(560px,92vw);padding:13px 18px;border-radius:12px;background:#24203d;border:1px solid var(--line2);box-shadow:0 12px 40px rgba(0,0,0,.45);z-index:50;font-size:14px}
.toast.ok{border-color:var(--done)}.toast.err{border-color:var(--late)}
.loading{padding:60px;text-align:center;color:var(--mut)}

/* ===== tablet ===== */
@media (max-width:1000px){
  .card{grid-template-columns:1fr 1fr;gap:14px}
  .card .act{grid-column:1/-1}
}
/* ===== mobil ===== */
@media (max-width:680px){
  .wrap{padding:14px 12px 50px}
  .steps{gap:8px}
  .step{padding:11px 8px 10px;border-radius:13px;text-align:center}
  .step:not(:last-child)::after{display:none}
  .step .hd{flex-direction:column;gap:5px}
  .step .num{width:24px;height:24px;font-size:12.5px}
  .step .ttl{font-size:12.5px;line-height:1.2}
  .step .cnt{justify-content:center;margin-top:6px}
  .step .cnt b{font-size:28px}
  .step .cnt span,.step .note{display:none}
  .card{grid-template-columns:1fr;padding:14px}
  .top{align-items:flex-start}.brand{white-space:nowrap;font-size:13px}
  .toplinks .lnk{padding:7px 9px;font-size:12px}.toplinks .hide-m{display:none}
  .side{width:100%}.side button{flex:1}
  .btn.main{flex:1}
}
</style>
</head>
<body>
<div class="wrap" id="app"><div class="loading">Načítám objednávky…</div></div>
<script>
(()=>{'use strict';
const app=document.getElementById('app');
let items=[],csrf='',now=Date.now()/1000,view='pay',q='',busy=false;

const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const kc=n=>new Intl.NumberFormat('cs-CZ',{maximumFractionDigits:0}).format(n||0)+'\u00a0Kč';
const dm=t=>{if(!t)return '–';const d=new Date(t*1000);return d.getDate()+'. '+(d.getMonth()+1)+'.';};
const dmy=t=>{if(!t)return '–';const d=new Date(t*1000);return d.getDate()+'. '+(d.getMonth()+1)+'. '+d.getFullYear();};
const days=t=>Math.floor((now-t)/86400);
const dny=n=>n===1?'den':(n>=2&&n<=4?'dny':'dní');
const ago=t=>{if(!t)return '';const n=days(t);return n<=0?'dnes':n===1?'včera':'před '+n+' '+(n<5?'dny':'dny');};
const isLate=x=>x.g==='pay'&&!x.noInvoice&&x.due>0&&x.due<now;

const STEPS=[
  {k:'pay', n:1,t:'Čeká na zaplacení'},
  {k:'post',n:2,t:'Odeslat poukaz'},
  {k:'done',n:3,t:'Zaplaceno'}
];
const SIDE=[{k:'used',t:'Použité'},{k:'storno',t:'Storno'},{k:'all',t:'Vše'}];
const TITLE={pay:'Čeká na zaplacení',post:'Odeslat poukaz',done:'Zaplaceno – poukaz je u zákazníka',used:'Použité poukazy',storno:'Stornované objednávky',all:'Všechny objednávky'};
const GL={pay:'Čeká na zaplacení',post:'Odeslat poukaz',done:'Zaplaceno',used:'Použito',storno:'Storno'};

function inView(x,k){return k==='all'||x.g===k;}
function sorted(list,k){
  const by=(f)=>list.sort(f);
  if(k==='pay')return by((a,b)=>(isLate(b)-isLate(a))||(b.noInvoice-a.noInvoice)||((a.due||9e12)-(b.due||9e12)));
  if(k==='post')return by((a,b)=>(a.paidAt||a.created)-(b.paidAt||b.created));
  if(k==='done')return by((a,b)=>(b.paidAt||b.created)-(a.paidAt||a.created));
  if(k==='used')return by((a,b)=>b.usedAt-a.usedAt);
  return by((a,b)=>b.created-a.created);
}

function stepNote(k,list){
  if(!list.length)return 'Hotovo ✓ nic nečeká';
  if(k==='pay'){const l=list.filter(isLate).length;return l?'<span class="bad">'+l+'× po splatnosti</span>':'vše v termínu';}
  if(k==='post'){const o=Math.max(...list.map(x=>days(x.paidAt||x.created)));return o>0?'nejdéle čeká '+o+' '+dny(o):'zaplaceno dnes';}
  if(k==='done'){return 'čekají na uplatnění';}
  return '';
}

function timeline(x){
  const s=[{t:'Objednáno',ts:x.created,d:true}];
  s.push(x.paid?{t:'Zaplaceno',ts:x.paidAt,d:true}:{t:'Platba',ts:x.due,d:false,pre:x.due?'do ':'',late:isLate(x)});
  if(x.hasV){
    s.push({t:x.posta?'Odesláno':'E-mail',ts:x.sentAt,d:x.sentAt>0});
    s.push({t:'Použito',ts:x.usedAt,d:x.usedAt>0});
  }
  let cur=x.g==='storno';
  return '<ol class="tl">'+s.map((p,i)=>{
    let c=p.d?'d':(!cur?'c':'');
    if(!p.d)cur=true;
    if(p.late)c+=' x';
    if(i>0&&p.d&&s[i-1].d)c+=' ln';
    const sub=p.ts?(p.pre||'')+dm(p.ts):'–';
    return '<li class="'+c.trim()+'"><b>'+esc(p.t)+'</b><small>'+esc(sub)+'</small></li>';
  }).join('')+'</ol>';
}

function card(x,showGroup){
  const late=isLate(x);
  let info='',btns='';
  const tel=x.phone?'<a class="btn sm" href="tel:'+esc(x.phone.replace(/\s+/g,''))+'">☎ Zavolat</a>':'';
  const more='<a class="more" href="sprava.php?f=vse#o-'+encodeURIComponent(x.id)+'" target="_blank" rel="noopener">další možnosti ↗</a>';
  if(x.g==='pay'){
    if(x.noInvoice){
      info='<span class="bad">Faktura zatím nevystavena</span>';
      btns='<button class="btn main" data-a="vystavit" data-id="'+esc(x.id)+'">Vystavit fakturu</button>'+tel;
    }else{
      const n=late?days(x.due):0;
      info=(late?'<span class="bad">Po splatnosti'+(n>0?' '+n+' '+dny(n):'')+'</span>':'Splatnost <b>'+dmy(x.due)+'</b>')
          +(x.vs?' · VS <b>'+esc(x.vs)+'</b>':'');
      btns='<button class="btn main" data-a="zaplaceno" data-id="'+esc(x.id)+'">✓ Platba přišla</button>'+tel
          +(x.no?'<a class="btn sm" href="sprava.php?dl=faktura&id='+encodeURIComponent(x.id)+'" target="_blank" rel="noopener">Faktura</a>':'');
    }
  }else if(x.g==='post'){
    info='<div class="addr">'+esc(x.name)+'<br>'+esc(x.addr||'Adresa chybí – doplňte ji v původní správě')+'</div>'
        +'<div class="info" style="margin-top:6px">Zaplaceno <b>'+ago(x.paidAt)+'</b></div>';
    btns='<input class="code" data-code="'+esc(x.id)+'" value="'+esc(x.code)+'" placeholder="Číslo poukazu" maxlength="20" autocomplete="off">'
        +'<a class="btn sm" href="sprava.php?dl=poukaz&id='+encodeURIComponent(x.id)+'" target="_blank" rel="noopener">Tisk PDF</a>'
        +'<button class="btn main" data-a="posta" data-id="'+esc(x.id)+'">✓ Odesláno</button>'
        +'<label class="chk"><input type="checkbox" data-notify="'+esc(x.id)+'" checked> poslat zákazníkovi e-mail</label>';
  }else if(x.g==='done'){
    info=(x.code?'Poukaz <b>'+esc(x.code)+'</b> · ':'')+(x.posta?'poštou':'e-mailem')+(x.sentAt?' '+dm(x.sentAt):'');
    btns=(x.code?'<button class="btn main" data-open="'+esc(x.id)+'">Uplatnit</button>':'')
        +(x.hasV?'<a class="btn sm" href="sprava.php?dl=poukaz&id='+encodeURIComponent(x.id)+'" target="_blank" rel="noopener">Poukaz PDF</a>':'')
        +'<div class="use" data-use="'+esc(x.id)+'"><input data-note="'+esc(x.id)+'" maxlength="120" placeholder="Poznámka (např. termín 24. 10.)"><button class="btn main" data-a="pouzit" data-id="'+esc(x.id)+'">Potvrdit</button></div>';
  }else if(x.g==='used'){
    info='Uplatněno <b>'+dmy(x.usedAt)+'</b>'+(x.usedNote?' · '+esc(x.usedNote):'')+(x.code?' · '+esc(x.code):'');
  }else{
    info='Objednávka zrušena';
  }
  return '<article class="card g-'+x.g+(late?' late':'')+'">'
    +'<div class="who"><div class="nm">'+esc(x.name)+'</div>'
    +'<div class="meta"><span class="tag'+(x.gold?' gold':'')+'">'+(x.gold?'Gold Time':'Standard Time')+'</span><span class="price">'+kc(x.price)+'</span>'+(x.no?'<span>'+esc(x.no)+'</span>':'')+'</div>'
    +(showGroup?'<span class="grp">'+GL[x.g]+'</span>':'')+'</div>'
    +'<div>'+timeline(x)+'</div>'
    +'<div class="act"><div class="info">'+info+'</div>'+(btns?'<div class="btns">'+btns+'</div>':'')+more+'</div>'
    +'</article>';
}

function render(){
  const cnt=k=>items.filter(x=>inView(x,k));
  const ql=q.trim().toLocaleLowerCase('cs');
  let list,title,showGroup=false;
  if(ql){
    list=items.filter(x=>[x.name,x.email,x.phone,x.code,x.no,x.vs,x.addr].join(' ').toLocaleLowerCase('cs').includes(ql));
    list=sorted(list,'all');title='Výsledky hledání';showGroup=true;
  }else{
    list=sorted(cnt(view),view);title=TITLE[view];showGroup=view==='all';
  }
  const sum=list.reduce((n,x)=>n+x.price,0);
  app.innerHTML=
    '<div class="top"><div class="brand">BLACK BOX<small>Správa poukazů</small></div>'
    +'<div class="toplinks"><button class="lnk" id="reload">↻ Obnovit</button><a class="lnk hide-m" href="sprava.php" target="_blank" rel="noopener">Původní správa ↗</a><a class="lnk" href="?odhlasit=1">Odhlásit</a></div></div>'
    +'<nav class="steps" role="tablist">'+STEPS.map(s=>{const l=cnt(s.k);const v=l.reduce((n,x)=>n+x.price,0);
      return '<button class="step s-'+s.k+(view===s.k&&!ql?' on':'')+(l.length?'':' zero')+'" role="tab" aria-selected="'+(view===s.k)+'" data-v="'+s.k+'">'
        +'<div class="hd"><span class="num">'+s.n+'</span><span class="ttl">'+s.t+'</span></div>'
        +'<div class="cnt"><b>'+l.length+'</b><span>'+kc(v)+'</span></div>'
        +'<div class="note">'+stepNote(s.k,l)+'</div></button>';}).join('')+'</nav>'
    +'<div class="bar"><label class="search"><input id="q" type="search" placeholder="Hledat jméno, telefon, číslo poukazu, fakturu…" value="'+esc(q)+'"></label>'
    +'<div class="side">'+SIDE.map(s=>'<button class="'+(view===s.k&&!ql?'on':'')+'" data-v="'+s.k+'">'+s.t+'<b>'+cnt(s.k).length+'</b></button>').join('')+'</div></div>'
    +'<div class="head"><h2>'+esc(title)+'</h2><span>'+list.length+' ks · '+kc(sum)+'</span></div>'
    +(list.length?list.map(x=>card(x,showGroup)).join(''):
      '<div class="empty"><b>'+(ql?'Nic jsme nenašli':'Hotovo ✓')+'</b>'+(ql?'Zkuste jiné jméno nebo číslo.':'V této části teď nic nečeká.')+'</div>');
  bind();
}

function bind(){
  app.querySelectorAll('[data-v]').forEach(b=>b.onclick=()=>{view=b.dataset.v;q='';render();window.scrollTo({top:0,behavior:'smooth'});});
  const qi=app.querySelector('#q');
  qi.oninput=()=>{q=qi.value;const p=qi.selectionStart;render();const n=app.querySelector('#q');n.focus();n.setSelectionRange(p,p);};
  app.querySelector('#reload').onclick=()=>load(true);
  app.querySelectorAll('[data-open]').forEach(b=>b.onclick=()=>{const u=app.querySelector('[data-use="'+CSS.escape(b.dataset.open)+'"]');u.classList.toggle('open');if(u.classList.contains('open'))u.querySelector('input').focus();});
  app.querySelectorAll('[data-a]').forEach(b=>b.onclick=()=>act(b.dataset.a,b.dataset.id,b));
  app.querySelectorAll('.code').forEach(i=>i.onkeydown=e=>{if(e.key==='Enter'){e.preventDefault();act('posta',i.dataset.code,null);}});
}

function toast(msg,type){
  document.querySelectorAll('.toast').forEach(t=>t.remove());
  const t=document.createElement('div');t.className='toast '+(type||'');t.setAttribute('role','status');t.textContent=msg;
  document.body.appendChild(t);setTimeout(()=>t.remove(),7000);
}

async function load(manual){
  try{
    const r=await fetch('?data=1',{credentials:'same-origin',cache:'no-store'});
    if(r.status===401){location.reload();return null;}
    const d=await r.json();
    if(!r.ok||!Array.isArray(d.items))throw new Error(d.error||'Data se nepodařilo načíst.');
    items=d.items;csrf=d.csrf;now=d.now||Date.now()/1000;
    render();
    if(manual)toast('Načteno aktuální.','ok');
    return d;
  }catch(err){
    if(!items.length)app.innerHTML='<div class="empty"><b>Správa se nenačetla</b>'+esc(err.message)+'</div>';
    else toast(err.message,'err');
    return null;
  }
}

async function act(a,id,btn){
  if(busy)return;
  const x=items.find(i=>i.id===id);if(!x)return;
  const fd=new FormData();
  fd.set('csrf',csrf);fd.set('id',id);fd.set('a',a);fd.set('f','vse');
  let ask='';
  if(a==='zaplaceno')ask='Potvrdit platbu '+kc(x.price)+' od '+x.name+'?'+(x.hasV&&!x.posta?'\n\nZákazníkovi se zároveň pošle poukaz e-mailem.':'');
  if(a==='vystavit')ask='Vystavit fakturu a poslat ji na '+x.email+'?';
  if(a==='posta'){
    const code=(app.querySelector('[data-code="'+CSS.escape(id)+'"]')?.value||'').trim();
    if(!code){toast('Nejdřív napište číslo poukazu.','err');app.querySelector('[data-code="'+CSS.escape(id)+'"]')?.focus();return;}
    fd.set('kod',code);fd.set('odeslano','1');
    if(app.querySelector('[data-notify="'+CSS.escape(id)+'"]')?.checked)fd.set('notify','1');
    ask='Poukaz '+code.toUpperCase()+' byl odeslán poštou\n'+x.name+', '+x.addr+'?';
  }
  if(a==='pouzit'){
    fd.set('pozn',app.querySelector('[data-note="'+CSS.escape(id)+'"]')?.value||'');
    ask='Uplatnit poukaz '+x.code+' ('+x.name+')?';
  }
  if(ask&&!confirm(ask))return;
  busy=true;app.querySelectorAll('[data-a]').forEach(b=>b.disabled=true);
  try{
    const r=await fetch('sprava.php',{method:'POST',body:fd,credentials:'same-origin',redirect:'manual'});
    if(r.type!=='opaqueredirect'&&!r.ok)throw new Error('Server vrátil chybu ('+r.status+'). Zkontrolujte stav v původní správě.');
    const d=await load(false);
    if(!d)throw new Error('Nepodařilo se ověřit výsledek. Zkontrolujte původní správu.');
    if(d.flash&&Array.isArray(d.flash))toast(d.flash[1],d.flash[0]==='ok'?'ok':'err');
    else{
      const y=items.find(i=>i.id===id);
      const ok=y&&(a==='zaplaceno'?y.paid:a==='posta'?y.g!=='post':a==='pouzit'?y.usedAt>0:a==='vystavit'?!y.noInvoice:false);
      toast(ok?'Uloženo ✓':'Změna se neprojevila – zkontrolujte původní správu.',ok?'ok':'err');
    }
  }catch(err){toast(err.message,'err');}
  finally{busy=false;app.querySelectorAll('[data-a]').forEach(b=>b.disabled=false);}
}

load(false);
})();
</script>
</body>
</html>
