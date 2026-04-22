-- =====================================================
-- UniClubs - Database Schema
-- Database: club_manager
-- =====================================================

CREATE DATABASE IF NOT EXISTS `club_manager` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `club_manager`;

-- =====================================================
-- Table: users
-- =====================================================
DROP TABLE IF EXISTS `inscriptions_evenements`;
DROP TABLE IF EXISTS `evenements`;
DROP TABLE IF EXISTS `membres`;
DROP TABLE IF EXISTS `clubs`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prenom` VARCHAR(100) NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `mot_de_passe` VARCHAR(255) NOT NULL,
    `role` ENUM('etudiant', 'admin_club') NOT NULL DEFAULT 'etudiant',
    `filiere` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: clubs
-- =====================================================
CREATE TABLE `clubs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `categorie` VARCHAR(100) NOT NULL DEFAULT 'autre',
    `emoji` VARCHAR(10) DEFAULT '📌',
    `couleur_gradient` VARCHAR(255) DEFAULT 'linear-gradient(135deg, #e8f0fc, #c5d8ff)',
    `admin_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: membres (memberships)
-- =====================================================
CREATE TABLE `membres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `club_id` INT NOT NULL,
    `statut` ENUM('en_attente', 'accepte', 'refuse') NOT NULL DEFAULT 'en_attente',
    `date_demande` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_membership` (`user_id`, `club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: evenements (events)
-- =====================================================
CREATE TABLE `evenements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `club_id` INT NOT NULL,
    `lieu` VARCHAR(255) NOT NULL,
    `date_debut` DATETIME NOT NULL,
    `date_fin` DATETIME NOT NULL,
    `max_participants` INT NOT NULL DEFAULT 50,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: inscriptions_evenements (event registrations)
-- =====================================================
CREATE TABLE `inscriptions_evenements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `evenement_id` INT NOT NULL,
    `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evenement_id`) REFERENCES `evenements`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_registration` (`user_id`, `evenement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Indexes for performance
-- =====================================================
CREATE INDEX `idx_clubs_admin` ON `clubs`(`admin_id`);
CREATE INDEX `idx_membres_user` ON `membres`(`user_id`);
CREATE INDEX `idx_membres_club` ON `membres`(`club_id`);
CREATE INDEX `idx_membres_statut` ON `membres`(`statut`);
CREATE INDEX `idx_evenements_club` ON `evenements`(`club_id`);
CREATE INDEX `idx_evenements_date` ON `evenements`(`date_debut`);
CREATE INDEX `idx_inscriptions_user` ON `inscriptions_evenements`(`user_id`);
CREATE INDEX `idx_inscriptions_event` ON `inscriptions_evenements`(`evenement_id`);
