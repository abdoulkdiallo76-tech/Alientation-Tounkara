<?php
// Test pour vérifier l'incohérence hasOpenCashSession
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Test Incohérence hasOpenCashSession</h1>";

// 1. Test direct de hasOpenCashSession()
echo "<h2>1. Test Direct hasOpenCashSession()</h2>";
try {
    $result1 = hasOpenCashSession();
    echo "<p>hasOpenCashSession(): " . ($result1 ? 'true' : 'false') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur hasOpenCashSession(): " . $e->getMessage() . "</p>";
}

// 2. Test manuel avec la même logique
echo "<h2>2. Test Manuel Même Logique</h2>";
try {
    if (!isLoggedIn()) {
        echo "<p>Utilisateur non connecté</p>";
    } else {
        echo "<p>Utilisateur connecté: " . $_SESSION['user_id'] . "</p>";
        
        $stmt = $pdo->prepare("SELECT id FROM cash_sessions WHERE cashier_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $result2 = $stmt->fetch() !== false;
        
        echo "<p>Test manuel: " . ($result2 ? 'true' : 'false') . "</p>";
        
        // Afficher la session trouvée
        if ($result2) {
            $session = getCurrentCashSession();
            if ($session) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><td>" . $session['id'] . "</td></tr>";
                echo "<tr><th>Cashier ID</th><td>" . $session['cashier_id'] . "</td></tr>";
                echo "<tr><th>Status</th><td>" . $session['status'] . "</td></tr>";
                echo "<tr><th>Opening Time</th><td>" . $session['opening_time'] . "</td></tr>";
                echo "</table>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur test manuel: " . $e->getMessage() . "</p>";
}

// 3. Comparaison
echo "<h2>3. Comparaison</h2>";
if (isset($result1) && isset($result2)) {
    if ($result1 === $result2) {
        echo "<p style='color: green;'>✅ Résultats cohérents: " . ($result1 ? 'true' : 'false') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Incohérence détectée!</p>";
        echo "<p>hasOpenCashSession(): " . ($result1 ? 'true' : 'false') . "</p>";
        echo "<p>Test manuel: " . ($result2 ? 'true' : 'false') . "</p>";
    }
}

// 4. Simulation de la logique pos.php
echo "<h2>4. Simulation Logique pos.php</h2>";
echo "<pre>";
echo "require_once 'config/database.php';";
echo "requireLogin();";
echo "";
echo "if (!hasOpenCashSession()) {";
echo "    header('Location: cash_management.php');";
echo "    exit();";
echo "}";
echo "</pre>";

echo "<h3>Résultat de la simulation:</h3>";
try {
    $result3 = hasOpenCashSession();
    if (!$result3) {
        echo "<p style='color: orange;'>⚠️ pos.php redirigerait vers cash_management.php</p>";
        echo "<p><a href='cash_management.php' class='btn btn-warning'>Aller vers cash_management.php</a></p>";
    } else {
        echo "<p style='color: green;'>✅ pos.php ne devrait pas rediriger</p>";
        echo "<p><a href='pos.php' class='btn btn-primary'>Tester pos.php</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur simulation: " . $e->getMessage() . "</p>";
}

// 5. Test direct de pos.php avec output buffering
echo "<h2>5. Test Direct pos.php (sans redirection)</h2>";
echo "<p>Test de ce que pos.php essaie de faire...</p>";

// Capturer la sortie pour éviter la redirection
ob_start();
try {
    // Simuler le début de pos.php
    $page_title = 'Caisse';
    require_once 'config/database.php';
    requireLogin();
    
    if (!hasOpenCashSession()) {
        echo "REDIRECTION_VERS_CASH_MANAGEMENT";
    } else {
        echo "PAS_DE_REDIRECTION";
    }
    
    $output = ob_get_clean();
    echo "<p>Résultat: " . $output . "</p>";
    
    if (strpos($output, 'REDIRECTION_VERS_CASH_MANAGEMENT') !== false) {
        echo "<p style='color: orange;'>⚠️ pos.php essaie de rediriger</p>";
    } else {
        echo "<p style='color: green;'>✅ pos.php ne redirige pas</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>Erreur test pos.php: " . $e->getMessage() . "</p>";
}

// 6. Solutions
echo "<h2>6. Solutions</h2>";
echo "<div class='alert alert-info'>";
echo "<h6>Si incohérence détectée:</h6>";
echo "<ul>";
echo "<li><strong>Option 1:</strong> Corriger la fonction hasOpenCashSession()</li>";
echo "<li><strong>Option 2:</strong> Modifier pos.php pour utiliser la logique manuelle</li>";
echo "<li><strong>Option 3:</strong> Vérifier les sessions PHP</li>";
echo "</ul>";

echo "<h6>Si pas d'incohérence:</h6>";
echo "<ul>";
echo "<li><strong>Vérifier le cache navigateur</strong></li>";
echo "<li><strong>Vérifier les erreurs JavaScript</strong></li>";
echo "<li><strong>Tester dans un autre navigateur</strong></li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='pos.php'>Tester pos.php directement</a></p>";
echo "<p><a href='cash_management.php'>Gestion de caisse</a></p>";
echo "<p><a href='debug_ventes_button.php'>Retour diagnostic bouton</a></p>";
?>
