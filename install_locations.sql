-- Script SQL pour créer le système de localités
-- Copiez-collez ce script dans phpMyAdmin

-- 1. Créer la table locations
CREATE TABLE IF NOT EXISTS `locations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `code` varchar(20) NOT NULL,
    `address` text DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `code` (`code`),
    KEY `idx_name` (`name`),
    KEY `idx_code` (`code`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ajouter la colonne location_id à la table users
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `location_id` int(11) DEFAULT NULL AFTER `role`;

-- 3. Ajouter la colonne location_id à la table sales  
ALTER TABLE `sales` ADD COLUMN IF NOT EXISTS `location_id` int(11) DEFAULT NULL AFTER `cashier_id`;

-- 4. Ajouter la colonne location_id à la table products
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `location_id` int(11) DEFAULT NULL AFTER `stock_quantity`;

-- 5. Ajouter les contraintes de clé étrangère (si elles n'existent pas déjà)
-- Pour users
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE TABLE_SCHEMA = 'alimentation_tounkara' 
     AND TABLE_NAME = 'users' 
     AND CONSTRAINT_NAME = 'fk_users_location') > 0,
    'SELECT 1',
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Pour sales
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE TABLE_SCHEMA = 'alimentation_tounkara' 
     AND TABLE_NAME = 'sales' 
     AND CONSTRAINT_NAME = 'fk_sales_location') > 0,
    'SELECT 1',
    'ALTER TABLE `sales` ADD CONSTRAINT `fk_sales_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Pour products
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE TABLE_SCHEMA = 'alimentation_tounkara' 
     AND TABLE_NAME = 'products' 
     AND CONSTRAINT_NAME = 'fk_products_location') > 0,
    'SELECT 1',
    'ALTER TABLE `products` ADD CONSTRAINT `fk_products_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Insérer des localités de démonstration (si la table est vide)
INSERT IGNORE INTO `locations` (`name`, `code`, `address`, `phone`, `email`) VALUES
('Siège Principal', 'SIEGE', '123 Avenue Principale, Dakar', '+221 33 123 45 67', 'contact@alimentation-tounkara.sn'),
('Boutique Plateau', 'PLATEAU', '45 Rue du Commerce, Dakar', '+221 33 234 56 78', 'plateau@alimentation-tounkara.sn'),
('Boutique Mermoz', 'MERMOZ', '78 Avenue Cheikh Anta Diop, Dakar', '+221 33 345 67 89', 'mermoz@alimentation-tounkara.sn'),
('Boutique Yoff', 'YOFF', '90 Route de la Corniche, Dakar', '+221 33 456 78 90', 'yoff@alimentation-tounkara.sn'),
('Boutique Ouakam', 'OUAKAM', '12 Rue des Pêcheurs, Dakar', '+221 33 567 89 01', 'ouakam@alimentation-tounkara.sn');

-- 7. Assigner une localité par défaut aux utilisateurs existants
UPDATE `users` SET `location_id` = 1 WHERE `location_id` IS NULL AND `is_active` = 1;

-- 8. Vérification
SELECT 'Installation terminée!' as message;
SELECT COUNT(*) as nb_locations FROM locations;
SELECT COUNT(*) as nb_users_with_location FROM users WHERE location_id IS NOT NULL;
