<?php
require_once 'config/database.php';

$page_title = 'Test Redirection Caissier';
echo "<h1>Test Redirection Caissier</h1>";

// Simuler la connexion d'un caissier
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'Caissier';
$_SESSION['full_name'] = 'Caissier TOUNKARA';
$_SESSION['user_role'] = 'cashier';
$_SESSION['login_time'] = date('Y-m-d H:i:s');

echo "<h2>Session simulée</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>Test des fonctions</h2>";
echo "<p>getUserRole(): " . getUserRole() . "</p>";
echo "<p>isCashier(): " . (isCashier() ? 'true' : 'false') . "</p>";

echo "<h2>Test de redirection</h2>";
echo "<p>Rôle en session: '" . $_SESSION['user_role'] . "'</p>";
echo "<p>Comparaison: 'cashier' === 'cashier'</p>";
echo "<p>Résultat comparaison: " . var_export($_SESSION['user_role'] === 'cashier', true) . "</p>";

if (trim($_SESSION['user_role']) === 'cashier') {
    echo "<p style='color: green;'>✅ Rôle caissier détecté (avec trim)</p>";
    echo "<p>Redirection vers: cashier_dashboard.php</p>";
    
    // Forcer la redirection
    session_write_close();
    header('Location: cashier_dashboard.php');
    exit();
} else {
    echo "<p style='color: red;'>❌ Rôle caissier non détecté</p>";
    echo "<p>Rôle actuel: '" . $_SESSION['user_role'] . "'</p>";
    echo "<p>Longueur: " . strlen($_SESSION['user_role']) . "</p>";
    echo "<p>Hex: " . bin2hex($_SESSION['user_role']) . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Aller à la page de connexion</a></p>";
echo "<p><a href='cashier_dashboard.php'>Accéder directement à cashier_dashboard.php</a></p>";
?>
