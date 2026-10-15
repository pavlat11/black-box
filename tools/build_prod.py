#!/usr/bin/env python3
"""Sestaví produkční verzi webu: dev/ + server/ -> výstupní složka.
Použití: python3 tools/build_prod.py <vystup> [--bez-serveru]
- odstraní [DEV] z titulku, zapne indexaci, přidá canonical
- nekopíruje config.php (hesla jsou jen na serveru) ani test-souboru.php
"""
import shutil, sys, re, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent
out = pathlib.Path(sys.argv[1]).resolve()
web_only = '--bez-serveru' in sys.argv
DOMAIN = 'https://www.black-box-luxury.cz/'

if out.exists():
    shutil.rmtree(out)
shutil.copytree(ROOT / 'dev', out)
if not web_only:
    skip = {'.gitignore', 'test-souboru.php', 'config.php'}
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

idx = out / 'index.html'
s = idx.read_text(encoding='utf-8')
s = s.replace('<title>[DEV] ', '<title>')
s, n = re.subn(r'<meta name="robots" content="[^"]*">', '<meta name="robots" content="index, follow">\n<link rel="canonical" href="' + DOMAIN + '">', s, count=1)
assert n == 1, 'robots meta nenalezen'
assert '[DEV]' not in s and 'noindex' not in s
idx.write_text(s, encoding='utf-8')
print('OK', out)
