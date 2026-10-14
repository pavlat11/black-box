# Black Box Luxury – web

- **LIVE:** `index.html` → https://pavlat11.github.io/black-box/
- **DEV (testovací verze):** `dev/index.html` → https://pavlat11.github.io/black-box/dev/

Postup: všechny úpravy se dělají nejdřív v `dev/index.html`. Až je schválené, zkopíruje se do `index.html` (live).
Dev verze má `noindex`, takže ji vyhledávače neindexují.

## Struktura DEV verze (od 14. 10. 2026)

Kód už není v jednom souboru:

- `dev/index.html` – jen HTML
- `dev/css/style.css` – všechny styly
- `dev/js/three.min.js` – knihovna three.js (3D animace Gold Time)
- `dev/js/hero-init.js` – malý skript úvodní fotky (načítá se hned)
- `dev/js/main.js` – veškerý vlastní JavaScript webu
- `dev/img/`, `dev/fonts/` – obrázky a písma

Při nahrání na live je potřeba zkopírovat celou složku (index.html + css/ js/ img/ fonts/).
