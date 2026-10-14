-- Black Box Luxury – tabulky pro objednávky poukazů
-- Tabulky se vytvoří samy při první objednávce. Tento soubor je jen pro ruční import v phpMyAdminu.

CREATE TABLE IF NOT EXISTS `bb_objednavky` (
  `id` varchar(40) NOT NULL,
  `zpusob` varchar(10) NOT NULL COMMENT 'faktura / odber',
  `poukaz` varchar(10) NOT NULL COMMENT 'standard / gold',
  `stav` varchar(15) NOT NULL COMMENT 'ceka, nova, bez_faktury, zaplaceno, vyrizeno, storno',
  `cislo_faktury` varchar(20) DEFAULT NULL,
  `kod_poukazu` varchar(20) DEFAULT NULL,
  `cena` int(11) NOT NULL,
  `pro_koho` varchar(100) NOT NULL DEFAULT '',
  `venovani` varchar(200) NOT NULL DEFAULT '',
  `jmeno` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `telefon` varchar(50) NOT NULL,
  `adresa` varchar(255) NOT NULL DEFAULT '',
  `firma` varchar(160) NOT NULL DEFAULT '',
  `ico` varchar(12) NOT NULL DEFAULT '',
  `vytvoreno` datetime NOT NULL,
  `splatnost` date DEFAULT NULL,
  `zaplaceno` datetime DEFAULT NULL,
  `upraveno` datetime NOT NULL,
  `data` mediumtext NOT NULL COMMENT 'kompletní objednávka (JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cislo_faktury` (`cislo_faktury`),
  UNIQUE KEY `kod_poukazu` (`kod_poukazu`),
  KEY `k_stav` (`stav`),
  KEY `k_vytvoreno` (`vytvoreno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci COMMENT='Black Box – objednávky dárkových poukazů';

CREATE TABLE IF NOT EXISTS `bb_cislovani` (
  `rok` int(11) NOT NULL,
  `posledni` int(11) NOT NULL,
  PRIMARY KEY (`rok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Black Box – poslední číslo faktury v roce';

CREATE TABLE IF NOT EXISTS `bb_nastaveni` (
  `klic` varchar(40) NOT NULL,
  `hodnota` text NOT NULL,
  `upraveno` datetime NOT NULL,
  PRIMARY KEY (`klic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Black Box – nastavení ze správy (ceník)';

-- výchozí ceník (lze pak měnit ve správě → Ceník)
INSERT IGNORE INTO `bb_nastaveni` (`klic`, `hodnota`, `upraveno`) VALUES ('ceny', '{"standard":1990,"gold":2490}', NOW());
