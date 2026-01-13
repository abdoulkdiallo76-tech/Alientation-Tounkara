<?php
require_once 'config/database.php';

$page_title = 'Test Sessions';
require_once 'includes/header.php';

// Test de création de table
echo "<div class='container'>";
echo "<h1>Test du système de sessions</h1>";

// 1. Vérifier la connexion PDO
if ($pdo) {
    echo "<div class='alert alert-success'>✅ Connexion PDO réussie</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Erreur de connexion PDO</div>";
    exit;
}

// 2. Créer la table si elle n'existe pas
if (createSessionTable()) {
    echo "<div class='alert alert-success'>✅ Table user_sessions créée/vérifiée</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Erreur création table</div>";
}

// 3. Vérifier si la table existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "<div class='alert alert-success'>✅ Table user_sessions existe</div>";
        
        // Afficher la structure
        echo "<h3>Structure de la table:</h3>";
        $stmt = $pdo->query("DESCRIBE user_sessions");
        echo "<table class='table table-bordered'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Compter les enregistrements
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_sessions");
        $count = $stmt->fetch()['count'];
        echo "<div class='alert alert-info'>📊 Nombre d'enregistrements: $count</div>";
        
        // Afficher les derniers enregistrements
        if ($count > 0) {
            echo "<h3>Derniers enregistrements:</h3>";
            $stmt = $pdo->query("SELECT * FROM user_sessions ORDER BY session_date DESC LIMIT 5");
            echo "<table class='table table-bordered'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Action</th><th>Date</th><th>IP</th></tr>";
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['action'] . "</td>";
                echo "<td>" . $row['session_date'] . "</td>";
                echo "<td>" . $row['ip_address'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>❌ Table user_sessions n'existe pas</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Erreur vérification table: " . $e->getMessage() . "</div>";
}

// 4. Test d'enregistrement si utilisateur connecté
if (isset($_SESSION['user_id'])) {
    echo "<h3>Test d'enregistrement</h3>";
    
    if (logSession('login', $_SESSION['user_id'])) {
        echo "<div class='alert alert-success'>✅ Session de test enregistrée</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Échec enregistrement session</div>";
    }
} else {
    echo "<div class='alert alert-warning'>⚠️ Utilisateur non connecté - impossible de tester l'enregistrement</div>";
}

// 5. Afficher les logs d'erreurs PHP
echo "<h3>Logs d'erreurs récents:</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $logs = file_get_contents($error_log);
    $recent_logs = array_slice(explode("\n", $logs), -10);
    echo "<pre class='bg-light p-3'>";
    foreach ($recent_logs as $log) {
        if (strpos($log, 'session') !== false) {
            echo htmlspecialchars($log) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<div class='alert alert-info'>📝 Fichier de log non accessible</div>";
}

echo "<div class='mt-3'>";
echo "<a href='index.php' class='btn btn-primary'>Retour au tableau de bord</a>";
echo " <a href='sessions.php' class='btn btn-info'>Voir les sessions</a>";
echo "</div>";

echo "</div>";
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
