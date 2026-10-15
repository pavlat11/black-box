# Black Box Luxury – web

- **LIVE:** `index.html` → https://pavlat11.github.io/black-box/
- **DEV (testovací verze):** `dev/index.html` → https://pavlat11.github.io/black-box/dev/

Postup: všechny úpravy se dělají nejdřív v `dev/`. Až je schválené, live/produkční verze se sestaví skriptem:

- `python3 tools/build_prod.py <složka>` – produkční web pro Endoru (web + PHP, bez `config.php` a `test-souboru.php`, zapnutá indexace, canonical na www.black-box-luxury.cz)
- `python3 tools/build_prod.py <složka> --bez-serveru` – jen web (tak je nahraný v kořeni repa pro GitHub Pages)
Dev verze má `noindex`, takže ji vyhledávače neindexují.

## Struktura DEV verze (od 14. 10. 2026)

Kód už není v jednom souboru:

- `dev/index.html` – jen HTML
- `dev/css/style.css` – všechny styly
- `dev/js/three.min.js` – knihovna three.js (3D animace Gold Time)
- `dev/js/hero-init.js` – malý skript úvodní fotky (načítá se hned)
- `dev/js/main.js` – veškerý vlastní JavaScript webu
- `dev/img/`, `dev/fonts/` – obrázky a písma

Při nahrání na live je potřeba zkopírovat celou složku (index.html + css/ js/ img/ fonts/ + og-image.jpg, atmosfera-f.mp3). V `server/` jsou navíc `robots.txt`, `sitemap.xml` a `.htaccess` pro kořen webu.

## Správa (sprava.php, také adresa /sprava): Objednávky · Ceník · Oznámení · Statistiky

- **Oznámení** – text, který se ukáže nahoře na webu (lze zavřít, volitelné datum „zobrazovat do“).
- **Statistiky** – prodané poukazy a tržby (měsíc, rok, celkem, po měsících).

### Ceník (od 14. 10. 2026)

Ceny Standard / Gold Time se mění ve **správě**: `sprava.php` → záložka **Ceník**.
Ukládají se do databáze (tabulka `bb_nastaveni`, vytvoří se sama). Web si je načítá z `ceny.php`,
platí i pro objednávky poukazů a nové faktury. V HTML jsou jen výchozí ceny (1 990 / 2 490 Kč)
pro případ, že `ceny.php` není dostupný (např. GitHub Pages).

### Dárkové poukazy (od 15. 10. 2026)

- Poukaz je **univerzální (na doručitele)** – bez jména a věnování, na kartě „Black Box Luxury / relaxační zóna“.
- Platí se vždy **fakturou**. Osobní odběr zrušen. Doručení: **e-mailem** (PDF s kódem = číslo faktury, odejde automaticky po potvrzení platby) nebo **poštou** (zákazník vyplní adresu).
- Správa → Objednávky: nahoře **Ověřit / uplatnit poukaz** (zadáte číslo → platný / použitý / nezaplacený). Záložky K vyřízení · Platné poukazy · Použité · Vše.
- U poukazu poštou se po zaplacení přidělí **číslo poukazu** (musí být jedinečné) a zaškrtne **Poukaz odeslán poštou** (volitelně e-mail zákazníkovi).
- **Uplatnit – poukaz použit** označí poukaz jako použitý (s datem a poznámkou); stejný poukaz pak znovu uplatnit nejde.
