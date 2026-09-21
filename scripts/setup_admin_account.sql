-- Compte admin par défaut (local / import phpMyAdmin)
-- Email : admin@yayematy.com
-- Mot de passe : Y@yeMaty2026!Admin

CREATE DATABASE IF NOT EXISTS `yayematy_boutique`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `yayematy_boutique`;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `date_creation` DATETIME NOT NULL,
  `derniere_connexion` DATETIME NULL DEFAULT NULL,
  `statut` ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
  `role` ENUM('admin', 'utilisateur', 'livreur') NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin` (`nom`, `prenom`, `email`, `password`, `date_creation`, `statut`, `role`)
VALUES (
  'Admin',
  'YayeMaty',
  'admin@yayematy.com',
  '$2y$10$vc4nAhKzLwek9Dziww83GupFcyJZXPrQVljQq6dN9pOJnRNsYQDtC',
  NOW(),
  'actif',
  'admin'
)
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `statut` = 'actif',
  `role` = 'admin';
