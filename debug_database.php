<?php
// Page de diagnostic pour les erreurs de base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Base de Données</h1>";

// 1. Test de connexion
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ Fichier database.php chargé</p>";
    echo "<p>Base de données: " . DB_NAME . "</p>";
    echo "<p>Hôte: " . DB_HOST . "</p>";
    echo "<p>Port: " . DB_PORT . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur chargement database.php: " . $e->getMessage() . "</p>";
    exit();
}

// 2. Test de connexion PDO
echo "<h2>2. Test de connexion PDO</h2>";
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "<p style='color: green;'>✅ Connexion PDO établie</p>";
        echo "<p>Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
        echo "<p>Version MySQL: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Objet PDO non disponible</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur connexion PDO: " . $e->getMessage() . "</p>";
}

// 3. Test des tables
echo "<h2>3. Vérification des tables</h2>";
$tables_to_check = ['users', 'sales', 'products', 'cash_sessions', 'locations'];

foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p style='color: green;'>✅ Table '$table' existe</p>";
            
            // Vérifier la structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<small>Colonnes: " . implode(', ', array_column($columns, 'Field')) . "</small><br>";
        } else {
            echo "<p style='color: orange;'>⚠️ Table '$table' n'existe pas</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur vérification table '$table': " . $e->getMessage() . "</p>";
    }
}

// 4. Test des fonctions de caisse
echo "<h2>4. Test des fonctions de caisse</h2>";

// Test hasOpenCashSession
try {
    if (function_exists('hasOpenCashSession')) {
        $result = hasOpenCashSession();
        echo "<p style='color: green;'>✅ hasOpenCashSession(): " . ($result ? 'true' : 'false') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction hasOpenCashSession() n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur hasOpenCashSession(): " . $e->getMessage() . "</p>";
}

// Test getCurrentCashSession
try {
    if (function_exists('getCurrentCashSession')) {
        $result = getCurrentCashSession();
        echo "<p style='color: green;'>✅ getCurrentCashSession(): " . ($result ? 'session trouvée' : 'aucune session') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction getCurrentCashSession() n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur getCurrentCashSession(): " . $e->getMessage() . "</p>";
}

// Test verifyPassword
try {
    if (function_exists('verifyPassword')) {
        echo "<p style='color: green;'>✅ verifyPassword(): fonction existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction verifyPassword() n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur verifyPassword(): " . $e->getMessage() . "</p>";
}

// 5. Test de création de session
echo "<h2>5. Test de création de session de caisse</h2>";
try {
    if (isset($_SESSION['user_id'])) {
        echo "<p>ID utilisateur en session: " . $_SESSION['user_id'] . "</p>";
        
        // Test avec un montant fictif
        $test_result = openCashSession(1000, 'test_password');
        if (is_array($test_result)) {
            echo "<p style='color: blue;'>📋 Résultat test ouverture: " . $test_result['message'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Résultat inattendu</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Aucun utilisateur en session</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur test création session: " . $e->getMessage() . "</p>";
    echo "<p>Détails: " . $e->getTraceAsString() . "</p>";
}

// 6. Informations sur l'utilisateur actuel
echo "<h2>6. Informations utilisateur actuel</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><td>" . $user['id'] . "</td></tr>";
            echo "<tr><th>Username</th><td>" . htmlspecialchars($user['username']) . "</td></tr>";
            echo "<tr><th>Full Name</th><td>" . htmlspecialchars($user['full_name']) . "</td></tr>";
            echo "<tr><th>Role</th><td>" . htmlspecialchars($user['role']) . "</td></tr>";
            echo "<tr><th>Active</th><td>" . ($user['is_active'] ? 'Oui' : 'Non') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Utilisateur en session non trouvé en base</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur récupération utilisateur: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Aucune session utilisateur active</p>";
}

// 7. Test d'insertion simple
echo "<h2>7. Test d'insertion simple</h2>";
try {
    // Créer une table de test si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, test_value VARCHAR(50))");
    
    // Insérer une valeur de test
    $stmt = $pdo->prepare("INSERT INTO test_table (test_value) VALUES (?)");
    $result = $stmt->execute(['test_value_' . date('Y-m-d H:i:s')]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Insertion test réussie</p>";
        
        // Nettoyer
        $pdo->exec("DELETE FROM test_table WHERE test_value LIKE 'test_value_%'");
        echo "<p style='color: blue;'>🧹 Table de test nettoyée</p>";
    } else {
        echo "<p style='color: red;'>❌ Échec insertion test</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur insertion test: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Actions recommandées</h2>";
echo "<div class='alert alert-info'>";
echo "<h6>Si vous voyez des erreurs:</h6>";
echo "<ol>";
echo "<li>Vérifiez que la base de données '" . DB_NAME . "' existe</li>";
echo "<li>Vérifiez que l'utilisateur a les permissions nécessaires</li>";
echo "<li>Exécutez setup_cash_sessions.php si la table cash_sessions n'existe pas</li>";
echo "<li>Vérifiez les identifiants de connexion dans config/database.php</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='cash_management.php'>Retour à la gestion de caisse</a></p>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>
