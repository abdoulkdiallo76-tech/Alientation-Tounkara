<?php
// Page de diagnostic pour créer la table locations
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic et Installation des Localités</h1>";

// 1. Test de connexion
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    echo "<p>Base de données: " . DB_NAME . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
    exit();
}

// 2. Vérifier si la table existe
echo "<h2>2. Vérification de la table 'locations'</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p style='color: green;'>✅ La table 'locations' existe déjà</p>";
        
        // Afficher la structure
        echo "<h3>Structure de la table locations:</h3>";
        $stmt = $pdo->query("DESCRIBE locations");
        echo "<table border='1'><tr><th>Champ</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ La table 'locations' n'existe pas</p>";
        
        // Créer la table
        echo "<h2>3. Création de la table 'locations'</h2>";
        try {
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
            echo "<p style='color: green;'>✅ Table 'locations' créée avec succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur lors de la création: " . $e->getMessage() . "</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la vérification: " . $e->getMessage() . "</p>";
}

// 4. Vérifier les colonnes location_id dans les autres tables
echo "<h2>4. Vérification des colonnes 'location_id'</h2>";

$tables = ['users', 'sales', 'products'];
foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'location_id'");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Colonne 'location_id' existe dans $table</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Colonne 'location_id' manquante dans $table</p>";
            
            // Ajouter la colonne
            try {
                $after_column = ($table == 'users') ? 'role' : (($table == 'sales') ? 'cashier_id' : 'stock_quantity');
                $sql = "ALTER TABLE `$table` ADD COLUMN location_id INT NULL AFTER `$after_column`";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Colonne 'location_id' ajoutée à $table</p>";
                
                // Ajouter la contrainte foreign key
                try {
                    $constraint_name = "fk_{$table}_location";
                    $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$constraint_name` 
                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
                    $pdo->exec($sql);
                    echo "<p style='color: green;'>✅ Contrainte foreign key ajoutée à $table</p>";
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>⚠️ Contrainte FK non ajoutée: " . $e->getMessage() . "</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Erreur ajout colonne: " . $e->getMessage() . "</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur vérification colonne: " . $e->getMessage() . "</p>";
    }
}

// 5. Insérer des localités de démonstration
echo "<h2>5. Insertion des localités de démonstration</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations");
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
        
        echo "<p style='color: green;'>✅ 5 localités de démonstration insérées</p>";
    } else {
        echo "<p style='color: green;'>✅ Localités existent déjà ($count trouvées)</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur insertion localités: " . $e->getMessage() . "</p>";
}

// 6. Assigner une localité par défaut aux utilisateurs existants
echo "<h2>6. Assignation des localités aux utilisateurs</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE location_id IS NULL AND is_active = 1");
    $users_without_location = $stmt->fetch()['count'];
    
    if ($users_without_location > 0) {
        // Assigner la première localité disponible
        $stmt = $pdo->query("SELECT id FROM locations WHERE is_active = 1 LIMIT 1");
        $first_location = $stmt->fetch();
        
        if ($first_location) {
            $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE location_id IS NULL AND is_active = 1");
            $stmt->execute([$first_location['id']]);
            echo "<p style='color: green;'>✅ $users_without_location utilisateur(s) assigné(s) à la localité par défaut</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Aucune localité disponible pour l'assignation</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Tous les utilisateurs ont déjà une localité assignée</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur assignation localités: " . $e->getMessage() . "</p>";
}

// 7. Test des fonctions
echo "<h2>7. Test des fonctions de localité</h2>";
try {
    require_once 'config/database.php';
    
    // Test getAllLocations
    $locations = getAllLocations();
    echo "<p>✅ getAllLocations(): " . count($locations) . " localité(s) trouvée(s)</p>";
    
    // Test getUserLocation
    if (isLoggedIn()) {
        $user_location = getUserLocation();
        if ($user_location) {
            echo "<p>✅ getUserLocation(): " . htmlspecialchars($user_location['name']) . "</p>";
        } else {
            echo "<p>⚠️ getUserLocation(): Aucune localité assignée à l'utilisateur actuel</p>";
        }
    } else {
        echo "<p>ℹ️ getUserLocation(): Utilisateur non connecté</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur test fonctions: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Résumé</h2>";
echo "<p><a href='locations.php'>Gérer les localités</a></p>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
echo "<p><strong>Important:</strong> Supprimez ce fichier après l'installation pour des raisons de sécurité.</p>";
?>
