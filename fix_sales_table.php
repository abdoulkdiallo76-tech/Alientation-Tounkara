<?php
require_once 'config/database.php';

echo "Vérification et mise à jour de la structure de la table sales...\n";

try {
    // Vérifier si la colonne cash_session_id existe
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'cash_session_id'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        echo "La colonne cash_session_id n'existe pas. Ajout de la colonne...\n";
        
        // Ajouter la colonne cash_session_id
        $sql = "ALTER TABLE sales ADD COLUMN cash_session_id INT NULL AFTER cashier_id";
        $pdo->exec($sql);
        
        // Ajouter la clé étrangère
        $sql = "ALTER TABLE sales ADD CONSTRAINT fk_sales_cash_session 
                FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id) 
                ON DELETE SET NULL";
        $pdo->exec($sql);
        
        echo "✅ Colonne cash_session_id ajoutée avec succès\n";
    } else {
        echo "✅ La colonne cash_session_id existe déjà\n";
    }
    
    // Afficher la structure complète
    echo "\nStructure actuelle de la table sales:\n";
    $stmt = $pdo->query('DESCRIBE sales');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

?>
