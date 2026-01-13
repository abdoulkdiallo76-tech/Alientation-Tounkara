<?php
require_once 'config/database.php';

// Création de la table des localités
try {
    // Vérifier si la table existe déjà
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'locations'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        $sql = "CREATE TABLE locations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            code VARCHAR(20) NOT NULL UNIQUE,
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "Table 'locations' créée avec succès<br>";
    }
    
    // Ajouter la colonne location_id à la table users si elle n'existe pas
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'location_id'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        $sql = "ALTER TABLE users ADD COLUMN location_id INT NULL AFTER role";
        $pdo->exec($sql);
        
        // Ajouter la contrainte de clé étrangère
        $sql = "ALTER TABLE users ADD CONSTRAINT fk_users_location 
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
        $pdo->exec($sql);
        
        echo "Colonne 'location_id' ajoutée à la table 'users'<br>";
    }
    
    // Ajouter la colonne location_id à la table sales si elle n'existe pas
    $stmt = $pdo->prepare("SHOW COLUMNS FROM sales LIKE 'location_id'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        $sql = "ALTER TABLE sales ADD COLUMN location_id INT NULL AFTER cashier_id";
        $pdo->exec($sql);
        
        // Ajouter la contrainte de clé étrangère
        $sql = "ALTER TABLE sales ADD CONSTRAINT fk_sales_location 
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
        $pdo->exec($sql);
        
        echo "Colonne 'location_id' ajoutée à la table 'sales'<br>";
    }
    
    // Ajouter la colonne location_id à la table products si elle n'existe pas
    $stmt = $pdo->prepare("SHOW COLUMNS FROM products LIKE 'location_id'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        $sql = "ALTER TABLE products ADD COLUMN location_id INT NULL AFTER stock_quantity";
        $pdo->exec($sql);
        
        // Ajouter la contrainte de clé étrangère
        $sql = "ALTER TABLE products ADD CONSTRAINT fk_products_location 
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
        $pdo->exec($sql);
        
        echo "Colonne 'location_id' ajoutée à la table 'products'<br>";
    }
    
    // Insérer des localités de démonstration
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM locations");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        $locations = [
            ['Siège Principal', 'SIEGE', '123 Avenue Principale, Dakar', '+221 33 123 45 67', 'contact@alimentation-tounkara.sn'],
            ['Boutique Plateau', 'PLATEAU', '45 Rue du Commerce, Dakar', '+221 33 234 56 78', 'plateau@alimentation-tounkara.sn'],
            ['Boutique Mermoz', 'MERMOZ', '78 Avenue Cheikh Anta Diop, Dakar', '+221 33 345 67 89', 'mermoz@alimentation-tounkara.sn'],
            ['Boutique Yoff', 'YOFF', '90 Route de la Corniche, Dakar', '+221 33 456 78 90', 'yoff@alimentation-tounkara.sn'],
            ['Boutique Ouakam', 'OUAKAM', '12 Rue des Pêcheurs, Dakar', '+221 33 567 89 01', 'ouakam@alimentation-tounkara.sn']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO locations (name, code, address, phone, email) VALUES (?, ?, ?, ?, ?)");
        foreach ($locations as $location) {
            $stmt->execute($location);
        }
        
        echo "5 localités de démonstration insérées<br>";
    }
    
    echo "<br><strong>Configuration des localités terminée avec succès!</strong>";
    
} catch(PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
