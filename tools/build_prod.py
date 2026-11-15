#!/usr/bin/env python3
"""Sestaví produkční verzi webu: dev/ + server/ -> výstupní složka.
Použití: python3 tools/build_prod.py <vystup> [--bez-serveru] [--dev] [--test]
- --test: testovací web pro https://blackboxx.cz/test/ (jako --dev + PHP část; bere ostrý config.php,
  návštěvy zapisuje do tabulky bb_navstevy_test, objednávky poukazů jsou vypnuté)
- --dev: testovací verze na hosting (ponechá [DEV] a noindex, jen přidá server/ a verze souborů)
- odstraní [DEV] z titulku, zapne indexaci, přidá canonical
- nekopíruje config.php (hesla jsou jen na serveru) ani test-souboru.php
"""
import shutil, sys, re, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
out = pathlib.Path(sys.argv[1]).resolve()
web_only = '--bez-serveru' in sys.argv
test_mode = '--test' in sys.argv
dev_mode = '--dev' in sys.argv or test_mode
DOMAIN = 'https://blackboxx.cz/'

if out.exists():
    shutil.rmtree(out)
shutil.copytree(ROOT / 'dev', out)
if not web_only:
    skip = {'.gitignore', 'test-souboru.php', 'config.php'} | ({'objednavka.php'} if test_mode else set())
    for p in (ROOT / 'server').rglob('*'):
        rel = p.relative_to(ROOT / 'server')
        if p.is_dir() or p.name in skip:
            continue
        if rel.parts[:2] == ('objednavky', 'data') and p.name != '.htaccess':
            continue
        if p.suffix == '.mtx.php' or p.name.endswith(('.cw.dat', '.cw127.php', '.mtx.php')):
            continue
        dst = out / rel
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(p, dst)

if test_mode and not web_only:
    (out / 'objednavky' / 'config.php').write_text(
        "<?php\n// TESTOVACÍ WEB (/test/): použije nastavení ostrého webu, ale návštěvy zapisuje zvlášť\n"
        "$c = require __DIR__ . '/../../objednavky/config.php';\n"
        "$c['stat_table'] = 'bb_navstevy_test';\n"
        "return $c;\n", encoding='utf-8')

idx = out / 'index.html'
s = idx.read_text(encoding='utf-8')
if not dev_mode:
    s = s.replace('<title>[DEV] ', '<title>')
    s, n = re.subn(r'<meta name="robots" content="[^"]*">', '<meta name="robots" content="index, follow">\n<link rel="canonical" href="' + DOMAIN + '">', s, count=1)
    assert n == 1, 'robots meta nenalezen'
    assert '[DEV]' not in s and 'noindex' not in s
import hashlib
def _ver(m):
    path = m.group(2).split('?')[0]
    f = out / path
    v = hashlib.md5(f.read_bytes()).hexdigest()[:8] if f.is_file() else '1'
    return m.group(1) + path + '?v=' + v + m.group(3)
# verze u CSS/JS → prohlížeč po nahrání vždy stáhne nové soubory (ne starou kopii z mezipaměti)
s = re.sub(r'((?:href|src)=")((?:css|js)/[^"?]+(?:\?v=[^"]*)?)(")', _ver, s)
idx.write_text(s, encoding='utf-8')
print('OK', out)
