<?php
// Page d'installation des localités - Version simplifiée
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Installation du Système de Localités</h1>";

// 1. Vérifier la connexion
echo "<h2>1. Connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ Connexion réussie à la base: " . DB_NAME . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
    exit();
}

// 2. Créer la table locations
echo "<h2>2. Création de la table locations</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Table 'locations' créée avec succès</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Table 'locations' existe déjà</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur création table: " . $e->getMessage() . "</p>";
}

// 3. Ajouter les colonnes location_id
echo "<h2>3. Ajout des colonnes location_id</h2>";

$tables = ['users', 'sales', 'products'];
foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'location_id'");
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            $after_column = ($table == 'users') ? 'role' : (($table == 'sales') ? 'cashier_id' : 'stock_quantity');
            $sql = "ALTER TABLE `$table` ADD COLUMN location_id INT NULL AFTER `$after_column`";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Colonne 'location_id' ajoutée à $table</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Colonne 'location_id' existe déjà dans $table</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur ajout colonne $table: " . $e->getMessage() . "</p>";
    }
}

// 4. Insérer les localités démo
echo "<h2>4. Insertion des localités de démonstration</h2>";
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
        echo "<p style='color: blue;'>ℹ️ Localités existent déjà ($count trouvées)</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur insertion localités: " . $e->getMessage() . "</p>";
}

// 5. Assigner les utilisateurs
echo "<h2>5. Assignation des utilisateurs</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE location_id IS NULL AND is_active = 1");
    $users_without_location = $stmt->fetch()['count'];
    
    if ($users_without_location > 0) {
        $stmt = $pdo->query("SELECT id FROM locations WHERE is_active = 1 LIMIT 1");
        $first_location = $stmt->fetch();
        
        if ($first_location) {
            $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE location_id IS NULL AND is_active = 1");
            $stmt->execute([$first_location['id']]);
            echo "<p style='color: green;'>✅ $users_without_location utilisateur(s) assigné(s)</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Tous les utilisateurs ont déjà une localité</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur assignation: " . $e->getMessage() . "</p>";
}

// 6. Réactiver les fonctions de localité
echo "<h2>6. Réactivation des fonctions de localité</h2>";
try {
    // Lire le fichier database.php
    $database_file = __DIR__ . '/config/database.php';
    $content = file_get_contents($database_file);
    
    // Remplacer les fonctions désactivées par les vraies fonctions
    $new_functions = '// Fonctions de gestion des localités
function getUserLocation() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT l.* FROM locations l 
                               JOIN users u ON u.location_id = l.id 
                               WHERE u.id = ? AND l.is_active = 1");
        $stmt->execute([$_SESSION[\'user_id\']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getUserLocation: " . $e->getMessage());
        return null;
    }
}

function getUserLocationId() {
    $location = getUserLocation();
    return $location ? $location[\'id\'] : null;
}

function getUserLocationName() {
    $location = getUserLocation();
    return $location ? $location[\'name\'] : \'Non assigné\';
}

function canAccessLocation($location_id) {
    if (isAdmin()) {
        return true;
    }
    
    $user_location_id = getUserLocationId();
    return $user_location_id && $user_location_id == $location_id;
}

function getLocationFilter($table_alias = \'\') {
    $user_location_id = getUserLocationId();
    
    if (isAdmin()) {
        return \'\';
    }
    
    if ($user_location_id) {
        $prefix = $table_alias ? $table_alias . \'.\' : \'\';
        return " AND {$prefix}location_id = {$user_location_id}";
    }
    
    return \'\';
}

function getAllLocations() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM locations WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erreur getAllLocations: " . $e->getMessage());
        return [];
    }
}

function getLocationById($location_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ? AND is_active = 1");
        $stmt->execute([$location_id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Erreur getLocationById: " . $e->getMessage());
        return null;
    }
}

function updateUserLocation($user_id, $location_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
        return $stmt->execute([$location_id, $user_id]);
    } catch(PDOException $e) {
        error_log("Erreur updateUserLocation: " . $e->getMessage());
        return false;
    }
}

function canAccessSale($sale_id) {
    if (isAdmin()) {
        return true;
    }
    
    try {
        $user_location_id = getUserLocationId();
        if (!$user_location_id) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT location_id FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch();
        
        return $sale && $sale[\'location_id\'] == $user_location_id;
    } catch(PDOException $e) {
        error_log("Erreur canAccessSale: " . $e->getMessage());
        return false;
    }
}

function canAccessProduct($product_id) {
    if (isAdmin()) {
        return true;
    }
    
    try {
        $user_location_id = getUserLocationId();
        if (!$user_location_id) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT location_id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        return $product && $product[\'location_id\'] == $user_location_id;
    } catch(PDOException $e) {
        error_log("Erreur canAccessProduct: " . $e->getMessage());
        return false;
    }
}';
    
    // Remplacer la section des fonctions
    $pattern = '/\/\/ Fonctions de gestion des localités.*?function canAccessProduct.*?\{.*?\}/s';
    $new_content = preg_replace($pattern, $new_functions, $content);
    
    if (file_put_contents($database_file, $new_content)) {
        echo "<p style='color: green;'>✅ Fonctions de localité réactivées</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Impossible de réactiver automatiquement les fonctions (manuellement nécessaire)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur réactivation fonctions: " . $e->getMessage() . "</p>";
}

// 7. Test final
echo "<h2>7. Test final</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations");
    $locations_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE location_id IS NOT NULL");
    $users_with_location = $stmt->fetch()['count'];
    
    echo "<p style='color: green;'>✅ Installation terminée!</p>";
    echo "<p>📍 Localités créées: $locations_count</p>";
    echo "<p>👥 Utilisateurs avec localité: $users_with_location</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur test final: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Prochaines étapes</h2>";
echo "<p><a href='locations.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📍 Gérer les localités</a></p>";
echo "<p><a href='sales.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>💰 Voir les ventes</a></p>";
echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Accueil</a></p>";

echo "<br><p style='color: red; font-weight: bold;'>⚠️ Important: Supprimez ce fichier après l'installation pour des raisons de sécurité!</p>";
?>
