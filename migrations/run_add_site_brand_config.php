<?php
/**
 * Table configuration logo du site (admin).
 * Usage : php migrations/run_add_site_brand_config.php
 */

require_once __DIR__ . '/../conn/conn.php';

function migration_exec(PDO $db, string $sql, string $label): void
{
    try {
        $db->exec($sql);
        echo "+ $label\n";
    } catch (PDOException $e) {
        echo "— $label : " . $e->getMessage() . "\n";
    }
}

migration_exec($db, "
CREATE TABLE IF NOT EXISTS `site_brand_config` (
  `id` int NOT NULL DEFAULT 1,
  `logo_path` varchar(255) DEFAULT NULL,
  `logo_alt` varchar(255) DEFAULT NULL,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'table site_brand_config');

try {
    $stmt = $db->query('SELECT COUNT(*) FROM site_brand_config WHERE id = 1');
    if ((int) $stmt->fetchColumn() === 0) {
        $db->exec("INSERT INTO site_brand_config (id, logo_path, logo_alt) VALUES (1, '/image/yaye_maty_logo.png', 'YAYEMATY MARKET — Votre marché local')");
        echo "+ ligne par défaut site_brand_config\n";
    } else {
        echo "— site_brand_config id=1 déjà présent\n";
    }
} catch (PDOException $e) {
    echo "— insert site_brand_config : " . $e->getMessage() . "\n";
}

echo "Migration site_brand_config terminée.\n";
