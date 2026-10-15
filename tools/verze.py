#!/usr/bin/env python3
"""Aktualizuje ?v=… u CSS/JS v dev/index.html podle obsahu souborů (aby prohlížeč nebral starou verzi)."""
import re, hashlib, pathlib
ROOT = pathlib.Path(__file__).resolve().parent.parent / 'dev'
idx = ROOT / 'index.html'
s = idx.read_text(encoding='utf-8')
def ver(m):
    path = m.group(2).split('?')[0]
    return m.group(1) + path + '?v=' + hashlib.md5((ROOT / path).read_bytes()).hexdigest()[:8] + m.group(3)
s = re.sub(r'((?:href|src)=")((?:css|js)/[^"?]+(?:\?v=[^"]*)?)(")', ver, s)
idx.write_text(s, encoding='utf-8')
print('OK')
