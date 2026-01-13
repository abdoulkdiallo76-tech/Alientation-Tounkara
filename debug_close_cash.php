<?php
// Diagnostic spécifique pour la fermeture de caisse
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Diagnostic Fermeture de Caisse</h1>";

// 1. Vérifier si une session est ouverte
echo "<h2>1. Vérification Session Ouverte</h2>";
try {
    $session = getCurrentCashSession();
    if ($session) {
        echo "<p style='color: green;'>✅ Session trouvée</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><td>" . $session['id'] . "</td></tr>";
        echo "<tr><th>Cashier ID</th><td>" . $session['cashier_id'] . "</td></tr>";
        echo "<tr><th>Opening Time</th><td>" . $session['opening_time'] . "</td></tr>";
        echo "<tr><th>Opening Amount</th><td>" . $session['opening_amount'] . "</td></tr>";
        echo "<tr><th>Status</th><td>" . $session['status'] . "</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Aucune session ouverte trouvée</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur getCurrentCashSession: " . $e->getMessage() . "</p>";
}

// 2. Vérifier la table sales
echo "<h2>2. Vérification Table Sales</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'sales'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: green;'>✅ Table 'sales' existe</p>";
        
        // Vérifier la structure
        $stmt = $pdo->query("DESCRIBE sales");
        $columns = $stmt->fetchAll();
        echo "<p>Colonnes trouvées:</p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
        
        // Compter les ventes
        if ($session) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE cashier_id = ? AND sale_date >= ?");
            $stmt->execute([$session['cashier_id'], $session['opening_time']]);
            $count = $stmt->fetch()['count'];
            echo "<p>Ventes dans cette session: " . $count . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table 'sales' n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur vérification table sales: " . $e->getMessage() . "</p>";
}

// 3. Vérifier la table expenses
echo "<h2>3. Vérification Table Expenses</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'expenses'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: green;'>✅ Table 'expenses' existe</p>";
        
        // Vérifier la structure
        $stmt = $pdo->query("DESCRIBE expenses");
        $columns = $stmt->fetchAll();
        echo "<p>Colonnes trouvées:</p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
        
        // Compter les dépenses
        if ($session) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE user_id = ? AND expense_date >= ?");
            $stmt->execute([$session['cashier_id'], $session['opening_time']]);
            $count = $stmt->fetch()['count'];
            echo "<p>Dépenses dans cette session: " . $count . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Table 'expenses' n'existe pas (optionnelle)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur vérification table expenses: " . $e->getMessage() . "</p>";
}

// 4. Test de la requête de calcul des totaux
echo "<h2>4. Test Requête Calcul Totaux</h2>";
if ($session) {
    try {
        // La requête exacte utilisée dans closeCashSession
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(final_amount), 0) as total_sales,
            COALESCE(SUM(amount), 0) as total_expenses
            FROM (
                SELECT final_amount, 0 as amount FROM sales 
                WHERE cashier_id = ? AND sale_date >= ?
                UNION ALL
                SELECT 0, amount FROM expenses 
                WHERE user_id = ? AND expense_date >= ?
            ) as combined");
        
        $stmt->execute([
            $session['cashier_id'], 
            $session['opening_time'],
            $session['cashier_id'], 
            $session['opening_time']
        ]);
        $totals = $stmt->fetch();
        
        echo "<p style='color: green;'>✅ Requête calcul réussie</p>";
        echo "<p>Total ventes: " . $totals['total_sales'] . "</p>";
        echo "<p>Total dépenses: " . $totals['total_expenses'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur requête calcul: " . $e->getMessage() . "</p>";
        echo "<p>Détails: " . $e->getTraceAsString() . "</p>";
        
        // Test sans expenses si la table n'existe pas
        echo "<h3>Test sans expenses</h3>";
        try {
            $stmt = $pdo->prepare("SELECT 
                COALESCE(SUM(final_amount), 0) as total_sales,
                0 as total_expenses
                FROM sales 
                WHERE cashier_id = ? AND sale_date >= ?");
            
            $stmt->execute([
                $session['cashier_id'], 
                $session['opening_time']
            ]);
            $totals = $stmt->fetch();
            
            echo "<p style='color: green;'>✅ Requête simplifiée réussie</p>";
            echo "<p>Total ventes: " . $totals['total_sales'] . "</p>";
            
        } catch (Exception $e2) {
            echo "<p style='color: red;'>❌ Erreur requête simplifiée: " . $e2->getMessage() . "</p>";
        }
    }
}

// 5. Test de la requête de mise à jour
echo "<h2>5. Test Requête Mise à Jour</h2>";
if ($session) {
    try {
        $test_closing_amount = $session['opening_amount'] + 1000; // Test avec 1000 FCFA de plus
        $test_total_sales = 1000;
        $test_total_expenses = 0;
        $test_expected_amount = $session['opening_amount'] + $test_total_sales - $test_total_expenses;
        $test_difference = $test_closing_amount - $test_expected_amount;
        
        $stmt = $pdo->prepare("UPDATE cash_sessions SET 
            closing_time = NOW(),
            closing_amount = ?,
            total_sales = ?,
            total_expenses = ?,
            expected_amount = ?,
            difference = ?,
            status = 'closed',
            notes = 'Test de diagnostic'
            WHERE id = ?");
        
        $result = $stmt->execute([
            $test_closing_amount,
            $test_total_sales,
            $test_total_expenses,
            $test_expected_amount,
            $test_difference,
            $session['id']
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Mise à jour test réussie</p>";
            
            // Réouvrir la session pour ne pas la fermer réellement
            $stmt = $pdo->prepare("UPDATE cash_sessions SET 
                closing_time = NULL,
                closing_amount = NULL,
                total_sales = 0,
                total_expenses = 0,
                expected_amount = NULL,
                difference = NULL,
                status = 'open',
                notes = NULL
                WHERE id = ?");
            $stmt->execute([$session['id']]);
            
            echo "<p style='color: blue;'>🔄 Session réouverte pour test</p>";
        } else {
            echo "<p style='color: red;'>❌ Échec mise à jour test</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur mise à jour: " . $e->getMessage() . "</p>";
        echo "<p>Détails: " . $e->getTraceAsString() . "</p>";
    }
}

// 6. Solutions recommandées
echo "<h2>6. Solutions Recommandées</h2>";
echo "<div class='alert alert-info'>";
echo "<h6>Si vous voyez des erreurs:</h6>";

// Vérifier si la table expenses existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'expenses'");
    $expenses_exists = $stmt->fetch();
    
    if (!$expenses_exists) {
        echo "<h5>⚠️ Table expenses manquante</h5>";
        echo "<p>La fonction closeCashSession essaie de calculer les dépenses mais la table expenses n'existe pas.</p>";
        echo "<p><strong>Solution:</strong> Créer la table expenses ou modifier la fonction pour ignorer les dépenses.</p>";
        
        echo "<h6>Option 1: Créer la table expenses</h6>";
        echo "<pre>";
        echo "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    expense_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        echo "</pre>";
        
        echo "<h6>Option 2: Modifier la fonction (plus simple)</h6>";
        echo "<p>Je peux modifier la fonction closeCashSession pour ignorer les dépenses si la table n'existe pas.</p>";
    }
} catch (Exception $e) {
    echo "<p>Erreur vérification expenses: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<p><a href='cash_management.php'>Retour à la gestion de caisse</a></p>";
echo "<p><a href='debug_database.php'>Diagnostic général</a></p>";
?>
