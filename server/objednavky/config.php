<?php
/*
 * BLACK BOX LUXURY – nastavení objednávek dárkových poukazů
 * ---------------------------------------------------------
 * Tady upravujete vše, co se objevuje na faktuře a v e-mailech.
 * Po úpravě stačí soubor znovu nahrát na FTP.
 */
return [

    // ===== Dodavatel (vystavovatel faktury) =================================
    'seller' => [
        'name'     => 'Black Box Lounge s.r.o.',
        'street'   => 'Příčná 1892/4, Nové Město',
        'city'     => '110 00 Praha 1',
        'ico'      => '29990041',
        'dic'      => '',                       // např. 'CZ29990041' – prázdné = nezobrazí se
        'register' => '',                       // např. 'Zapsáno v OR u Městského soudu v Praze, oddíl C, vložka …'
        'premises' => 'Provozovna: Purkyňova 2961, 390 02 Tábor',
    ],
    'vat_note' => 'Nejsme plátci DPH.',         // pokud jste plátci, napište sem '' a vyplňte DIČ

    // ===== Platba ============================================================
    // !!! BEZ ČÍSLA ÚČTU SE FAKTURA NEODEŠLE (objednávka přijde jen vám e-mailem) !!!
    'bank_account' => '',                       // např. '123456789/0100' nebo '19-123456789/0800'
    'bank_name'    => '',                       // např. 'Komerční banka' (nepovinné)
    'due_days'     => 7,                        // splatnost faktury ve dnech
    'invoice_title'=> 'Faktura',                // nadpis dokladu
    'invoice_prefix' => '',                     // číslo faktury = prefix + rok + pořadí, např. 20260001

    // ===== Poukazy a ceny (kontroluje server, ne prohlížeč) ==================
    'products' => [
        'standard' => ['name' => 'Dárkový poukaz Black Box – Standard',  'detail' => 'Privátní blok 2 hodiny pro pár',                      'price' => 2000],
        'gold'     => ['name' => 'Dárkový poukaz Black Box – Gold Time', 'detail' => 'Pátek nebo sobota 22–24 h, celá budova jen pro vás', 'price' => 2900],
    ],

    // ===== E-maily ===========================================================
    // Kam chodí objednávky a kopie (faktury, osobní odběr, potvrzení plateb):
    'invoice_copy_to' => 'pavlatp@email.cz',
    'pickup_to'       => 'pavlatp@email.cz',
    'reply_to'        => 'pavlatp@email.cz',          // když zákazník na e-mail odpoví, přijde to sem
    'contact_email'   => 'blackboxluxury@email.cz',   // kontakt zobrazený v e-mailech a na faktuře
    'phone'           => '+420 737 22 22 66',
    'web'             => 'https://www.black-box-luxury.cz',

    // Odesílání přes SMTP (doporučeno – e-maily nepadají do spamu).
    // Pro schránku na email.cz / seznam.cz: host smtp.seznam.cz, port 465, ssl,
    // přihlášení = celá e-mailová adresa + heslo k ní.
    // Když necháte 'pass' prázdné, použije se funkce mail() hostingu.
    'smtp' => [
        'host'   => 'smtp.seznam.cz',
        'port'   => 465,
        'secure' => 'ssl',
        'user'   => 'blackboxluxury@email.cz',
        'pass'   => '',
    ],
    // Odesílatel, pokud se NEposílá přes SMTP (musí být na vaší doméně):
    'from_fallback' => 'objednavky@black-box-luxury.cz',
    'from_name'     => 'Black Box Luxury',

    // ===== Databáze MySQL (objednávky a číslování faktur) ======================
    // Údaje najdete v administraci hostingu (Endora → Databáze).
    // Tabulky se vytvoří samy při první objednávce. Když 'name' necháte prázdné,
    // objednávky se ukládají do souborů ve složce objednavky/data.
    'db' => [
        'host' => 'localhost',
        'port' => '',
        'name' => '',            // název databáze
        'user' => '',            // uživatel databáze
        'pass' => '',            // heslo databáze
    ],

    // ===== Správa objednávek (stránka /sprava.php) ==========================
    // Heslo pro přihlášení do správy. Prázdné = správa je vypnutá.
    'admin_password' => '',

    // ===== Ochrana proti spamu ===============================================
    'max_orders_per_ip_per_hour' => 5,
];
