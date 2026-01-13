<?php
require_once 'config/database.php';

$page_title = 'Debug Cashier Dashboard';
echo "<h1>Debug - Accès Espace Caissier</h1>";

// Vérifier si la session est démarrée
echo "<h2>1. État de la session</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Session active</p>";
} else {
    echo "<p style='color: red;'>❌ Session non active</p>";
}

// Vérifier les variables de session
echo "<h2>2. Variables de session</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

// Vérifier le rôle
echo "<h2>3. Vérification du rôle</h2>";
$userRole = getUserRole();
echo "<p>getUserRole(): " . $userRole . "</p>";
echo "<p>isCashier(): " . (isCashier() ? 'true' : 'false') . "</p>";
echo "<p>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</p>";
echo "<p>canAccessManagement(): " . (canAccessManagement() ? 'true' : 'false') . "</p>";

// Test de redirection
echo "<h2>4. Test de logique de redirection</h2>";
if (!isCashier()) {
    echo "<p style='color: red;'>❌ Redirection vers index.php (pas caissier)</p>";
    echo "<p><a href='index.php'>Aller à index.php</a></p>";
} else {
    echo "<p style='color: green;'>✅ Accès autorisé (caissier)</p>";
    echo "<p><a href='cashier_dashboard.php'>Accéder à cashier_dashboard.php</a></p>";
}

// Test de connexion base de données
echo "<h2>5. Test de base de données</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Base de données connectée</p>";
    echo "<p>Nombre d'utilisateurs: " . $result['count'] . "</p>";
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Erreur base de données: " . $e->getMessage() . "</p>";
}

// Test de l'utilisateur actuel
echo "<h2>6. Informations utilisateur actuel</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color: green;'>✅ Utilisateur trouvé</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><td>" . $user['id'] . "</td></tr>";
            echo "<tr><th>Username</th><td>" . $user['username'] . "</td></tr>";
            echo "<tr><th>Full Name</th><td>" . $user['full_name'] . "</td></tr>";
            echo "<tr><th>Role</th><td>" . $user['role'] . "</td></tr>";
            echo "<tr><th>Is Active</th><td>" . ($user['is_active'] ? 'Oui' : 'Non') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Utilisateur non trouvé en base</p>";
        }
    } catch(Exception $e) {
        echo "<p style='color: red;'>❌ Erreur requête: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ user_id non défini en session</p>";
}

echo "<h2>7. Actions possibles</h2>";
echo "<p><a href='login.php'>Se connecter</a></p>";
echo "<p><a href='logout.php'>Se déconnecter</a></p>";
echo "<p><a href='index.php'>Tableau de bord</a></p>";
echo "<p><a href='pos.php'>Caisse</a></p>";
echo "<p><a href='sales.php'>Historique des ventes</a></p>";

echo "<hr>";
echo "<h2>8. Recommandations</h2>";
echo "<ul>";
echo "<li>Si vous voyez 'Session non active', allez sur login.php</li>";
echo "<li>Si isCashier() retourne false, vérifiez le rôle en base de données</li>";
echo "<li>Si user_id n'est pas défini, reconnectez-vous</li>";
echo "</ul>";
?>
