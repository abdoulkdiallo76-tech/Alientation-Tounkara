<?php
require_once 'config/database.php';

echo "<h2>🧹 Nettoyage de la Base de Données</h2>";

// Supprimer la table bank_transfers si elle existe
try {
    $stmt = $pdo->query("DROP TABLE IF EXISTS bank_transfers");
    echo "✅ Table 'bank_transfers' supprimée<br>";
} catch (PDOException $e) {
    echo "❌ Erreur suppression table bank_transfers: " . $e->getMessage() . "<br>";
}

// Supprimer les colonnes ajoutées à cash_sessions
$columns_to_remove = [
    'transfer_to_bank',
    'bank_reference', 
    'transfer_time',
    'expected_amount',
    'difference',
    'total_sales',
    'total_expenses'
];

foreach ($columns_to_remove as $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM cash_sessions LIKE '$column'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            $pdo->query("ALTER TABLE cash_sessions DROP COLUMN $column");
            echo "✅ Colonne '$column' supprimée de cash_sessions<br>";
        } else {
            echo "ℹ️ Colonne '$column' n'existe pas dans cash_sessions<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur suppression colonne $column: " . $e->getMessage() . "<br>";
    }
}

// Vérifier la structure finale de cash_sessions
try {
    $stmt = $pdo->query("DESCRIBE cash_sessions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<br><h3>📋 Structure finale de cash_sessions:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Erreur vérification structure: " . $e->getMessage() . "<br>";
}

// Vérifier que les tables essentielles existent
$essential_tables = ['users', 'cash_sessions', 'sales'];

echo "<br><h3>🔍 Vérification des tables essentielles:</h3>";
foreach ($essential_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✅ Table '$table' existe<br>";
        } else {
            echo "❌ Table '$table' manquante<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur vérification table $table: " . $e->getMessage() . "<br>";
    }
}

echo "<br><h3>🎯 Nettoyage terminé!</h3>";
echo "<p>L'application est revenue à une configuration simple avec:</p>";
echo "<ul>";
echo "<li>✅ Gestion de caisse simple (ouverture/fermeture)</li>";
echo "<li>✅ Saisie du montant initial</li>";
echo "<li>✅ Historique des sessions</li>";
echo "<li>❌ Plus de transferts bancaires complexes</li>";
echo "<li>❌ Plus de calculs automatiques avancés</li>";
echo "</ul>";

echo "<br><div class='text-center'>";
echo "<a href='cash_management.php' class='btn btn-primary me-2'>Tester la gestion de caisse</a>";
echo "<a href='index.php' class='btn btn-secondary'>Retour à l'accueil</a>";
echo "</div>";
?>
