<?php
// Diagnostic pour le bouton Ventes
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Diagnostic Bouton Ventes</h1>";

// 1. Vérifier la session utilisateur
echo "<h2>1. Vérification Session Utilisateur</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ Session utilisateur active</p>";
    echo "<p>ID utilisateur: " . $_SESSION['user_id'] . "</p>";
    
    // Récupérer les infos utilisateur
    try {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<table border='1' style='border-collapse: collapse;'>";
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
    echo "<p style='color: red;'>❌ Aucune session utilisateur active</p>";
    echo "<p><a href='login.php'>Se connecter</a></p>";
}

// 2. Vérifier les fonctions
echo "<h2>2. Vérification Fonctions</h2>";

// Test isCashier()
try {
    if (function_exists('isCashier')) {
        $is_cashier = isCashier();
        echo "<p style='color: " . ($is_cashier ? 'green' : 'orange') . ";'>✅ isCashier(): " . ($is_cashier ? 'true' : 'false') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction isCashier() n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur isCashier(): " . $e->getMessage() . "</p>";
}

// Test hasOpenCashSession()
try {
    if (function_exists('hasOpenCashSession')) {
        $has_open = hasOpenCashSession();
        echo "<p style='color: " . ($has_open ? 'green' : 'orange') . ";'>✅ hasOpenCashSession(): " . ($has_open ? 'true' : 'false') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction hasOpenCashSession() n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur hasOpenCashSession(): " . $e->getMessage() . "</p>";
}

// 3. Vérifier la session de caisse
echo "<h2>3. Vérification Session de Caisse</h2>";
try {
    $session = getCurrentCashSession();
    if ($session) {
        echo "<p style='color: green;'>✅ Session de caisse trouvée</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><td>" . $session['id'] . "</td></tr>";
        echo "<tr><th>Cashier ID</th><td>" . $session['cashier_id'] . "</td></tr>";
        echo "<tr><th>Opening Time</th><td>" . $session['opening_time'] . "</td></tr>";
        echo "<tr><th>Opening Amount</th><td>" . $session['opening_amount'] . "</td></tr>";
        echo "<tr><th>Status</th><td>" . $session['status'] . "</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Aucune session de caisse ouverte</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur getCurrentCashSession(): " . $e->getMessage() . "</p>";
}

// 4. Simulation du code du header
echo "<h2>4. Simulation Code Header</h2>";
echo "<h3>Logique actuelle:</h3>";
echo "<pre>";
echo "<?php if (isCashier()): ?>";
echo "    <?php if (hasOpenCashSession()): ?>";
echo "        <a href='pos.php'>Ventes</a>";
echo "    <?php else: ?>";
echo "        <a href='cash_management.php'>Ouvrir Caisse</a>";
echo "    <?php endif; ?>";
echo "<?php else: ?>";
echo "    <!-- Navigation admin -->";
echo "    <a href='pos.php'>Ventes</a>";
echo "<?php endif; ?>";
echo "</pre>";

echo "<h3>Résultat de la simulation:</h3>";
try {
    if (function_exists('isCashier') && function_exists('hasOpenCashSession')) {
        $is_cashier = isCashier();
        $has_open = hasOpenCashSession();
        
        echo "<p>isCashier(): " . ($is_cashier ? 'true' : 'false') . "</p>";
        echo "<p>hasOpenCashSession(): " . ($has_open ? 'true' : 'false') . "</p>";
        
        if ($is_cashier) {
            if ($has_open) {
                echo "<p style='color: green;'>🔗 Le bouton devrait pointer vers: <strong>pos.php</strong></p>";
                echo "<p><a href='pos.php' class='btn btn-primary'>Tester pos.php</a></p>";
            } else {
                echo "<p style='color: orange;'>🔗 Le bouton devrait pointer vers: <strong>cash_management.php</strong></p>";
                echo "<p><a href='cash_management.php' class='btn btn-warning'>Ouvrir la caisse</a></p>";
            }
        } else {
            echo "<p style='color: blue;'>🔗 Admin - Le bouton devrait pointer vers: <strong>pos.php</strong></p>";
            echo "<p><a href='pos.php' class='btn btn-primary'>Tester pos.php</a></p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur simulation: " . $e->getMessage() . "</p>";
}

// 5. Vérifier les fichiers cibles
echo "<h2>5. Vérification Fichiers Cibles</h2>";

$files_to_check = ['pos.php', 'cash_management.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file existe</p>";
    } else {
        echo "<p style='color: red;'>❌ $file n'existe pas</p>";
    }
}

// 6. Vérifier les erreurs JavaScript
echo "<h2>6. Débogage JavaScript</h2>";
echo "<p>Pour vérifier s'il y a des erreurs JavaScript:</p>";
echo "<ol>";
echo "<li>Ouvrez les outils de développement (F12)</li>";
echo "<li>Allez dans l'onglet 'Console'</li>";
echo "<li>Cliquez sur le bouton 'Ventes'</li>";
echo "<li>Regardez s'il y a des erreurs JavaScript</li>";
echo "</ol>";

// 7. Test direct des liens
echo "<h2>7. Test Direct des Liens</h2>";
echo "<div class='btn-group'>";
echo "<a href='pos.php' class='btn btn-primary me-2'>Tester pos.php</a>";
echo "<a href='cash_management.php' class='btn btn-warning me-2'>Tester cash_management.php</a>";
echo "<a href='sales.php' class='btn btn-info'>Tester sales.php</a>";
echo "</div>";

// 8. Solutions possibles
echo "<h2>8. Solutions Possibles</h2>";
echo "<div class='alert alert-info'>";
echo "<h6>Si le bouton ne réagit pas:</h6>";
echo "<ul>";
echo "<li><strong>Vérifiez les erreurs JavaScript</strong> dans la console (F12)</li>";
echo "<li><strong>Vérifiez que pos.php existe</strong> et est accessible</li>";
echo "<li><strong>Vérifiez que la session est bien active</strong></li>";
echo "<li><strong>Vérifiez que hasOpenCashSession() fonctionne</strong></li>";
echo "<li><strong>Essayez les liens directs</strong> ci-dessus</li>";
echo "</ul>";

echo "<h6>Si pos.php redirige vers cash_management.php:</h6>";
echo "<ul>";
echo "<li>C'est normal si aucune session de caisse n'est ouverte</li>";
echo "<li>Ouvrez d'abord la caisse, puis utilisez le bouton Ventes</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
echo "<p><a href='cash_management.php'>Gestion de caisse</a></p>";
echo "<p><a href='pos.php'>Point de vente</a></p>";
?>
