<?php
require_once 'config/database.php';

$page_title = 'Test Sessions';
echo "<h1>Test de la page sessions.php</h1>";

// Vérifier la connexion
echo "<h2>1. Vérification de la connexion</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Session active</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Non défini') . "</p>";
    echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'Non défini') . "</p>";
    echo "<p>User Name: " . ($_SESSION['full_name'] ?? 'Non défini') . "</p>";
} else {
    echo "<p style='color: red;'>❌ Session non active</p>";
}

// Vérifier les fonctions
echo "<h2>2. Vérification des fonctions</h2>";
if (function_exists('requireLogin')) {
    echo "<p style='color: green;'>✅ requireLogin() existe</p>";
} else {
    echo "<p style='color: red;'>❌ requireLogin() n'existe pas</p>";
}

if (function_exists('canAccessManagement')) {
    echo "<p style='color: green;'>✅ canAccessManagement() existe</p>";
    $canAccess = canAccessManagement();
    echo "<p>canAccessManagement() retourne: " . ($canAccess ? 'true' : 'false') . "</p>";
} else {
    echo "<p style='color: red;'>❌ canAccessManagement() n'existe pas</p>";
}

// Test de récupération des sessions
echo "<h2>3. Test de récupération des sessions</h2>";
try {
    $sessions = getSessionHistory(null, 5);
    echo "<p style='color: green;'>✅ getSessionHistory() fonctionne</p>";
    echo "<p>Nombre de sessions: " . count($sessions) . "</p>";
    
    if (!empty($sessions)) {
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Action</th><th>Date</th></tr>";
        foreach ($sessions as $session) {
            echo "<tr>";
            echo "<td>" . $session['id'] . "</td>";
            echo "<td>" . $session['user_id'] . "</td>";
            echo "<td>" . $session['action'] . "</td>";
            echo "<td>" . $session['session_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune session trouvée</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Erreur getSessionHistory(): " . $e->getMessage() . "</p>";
}

// Test des utilisateurs
echo "<h2>4. Test des utilisateurs</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll();
    echo "<p style='color: green;'>✅ Requête utilisateurs réussie</p>";
    echo "<p>Nombre d'utilisateurs: " . count($users) . "</p>";
    
    if (!empty($users)) {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>" . $user['full_name'] . " (" . $user['username'] . ")</li>";
        }
        echo "</ul>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Erreur requête utilisateurs: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Actions possibles</h2>";
echo "<p><a href='index.php'>Aller à l'index</a></p>";
echo "<p><a href='login.php'>Aller à la connexion</a></p>";
echo "<p><a href='logout.php'>Se déconnecter</a></p>";

echo "<h2>6. Test de navigation</h2>";
echo "<p><a href='sessions.php' style='background: blue; color: white; padding: 10px;'>Tester sessions.php (original)</a></p>";
echo "<p><a href='test_sessions.php' style='background: green; color: white; padding: 10px;'>Tester test_sessions.php</a></p>";

echo "<hr>";
echo "<p><strong>Si vous voyez cette page, PHP fonctionne. Le problème est probablement:</strong></p>";
echo "<ul>";
echo "<li>Redirection automatique (caissier → index.php)</li>";
echo "<li>Erreur dans requireLogin() ou canAccessManagement()</li>";
echo "<li>Problème de permissions de rôle</li>";
echo "</ul>";
?>
