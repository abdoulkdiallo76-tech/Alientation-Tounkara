<?php
// Script pour créer la table des sessions de caisse
require_once 'config/database.php';

echo "<h1>Installation Table Sessions de Caisse</h1>";

try {
    // Créer la table cash_sessions
    $sql = "CREATE TABLE IF NOT EXISTS `cash_sessions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `cashier_id` int(11) NOT NULL,
        `location_id` int(11) DEFAULT NULL,
        `opening_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `closing_time` datetime DEFAULT NULL,
        `opening_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
        `closing_amount` decimal(10,2) DEFAULT NULL,
        `total_sales` decimal(10,2) DEFAULT 0.00,
        `total_expenses` decimal(10,2) DEFAULT 0.00,
        `expected_amount` decimal(10,2) DEFAULT NULL,
        `difference` decimal(10,2) DEFAULT NULL,
        `status` enum('open','closed') NOT NULL DEFAULT 'open',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_cashier_id` (`cashier_id`),
        KEY `idx_location_id` (`location_id`),
        KEY `idx_status` (`status`),
        KEY `idx_opening_time` (`opening_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Table cash_sessions créée avec succès</p>";
    
    // Vérifier si la table existe déjà
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cash_sessions");
    $count = $stmt->fetch()['count'];
    echo "<p>ℹ️ Nombre de sessions existantes: $count</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>
