<?php
require_once 'config/database.php';

/**
 * Script de fermeture automatique des sessions de caisse en fin de journée
 * À exécuter via cron job à 23:55 chaque jour
 */

echo "Début de la fermeture automatique des sessions - " . date('Y-m-d H:i:s') . "\n";

try {
    // Récupérer toutes les sessions ouvertes du jour
    $stmt = $pdo->prepare("SELECT cs.*, u.full_name as cashier_name, u.username as cashier_username
                            FROM cash_sessions cs 
                            LEFT JOIN users u ON cs.cashier_id = u.id 
                            WHERE cs.status = 'open' 
                            AND DATE(cs.opening_time) = CURDATE()
                            AND cs.closing_time IS NULL");
    $stmt->execute();
    $open_sessions = $stmt->fetchAll();
    
    if (empty($open_sessions)) {
        echo "Aucune session ouverte trouvée pour aujourd'hui.\n";
        exit(0);
    }
    
    echo count($open_sessions) . " session(s) ouverte(s) trouvée(s).\n";
    
    foreach ($open_sessions as $session) {
        echo "Traitement de la session #" . $session['id'] . " - Caissier: " . $session['cashier_name'] . "\n";
        
        // Calculer le total des ventes de la journée
        $stmt_sales = $pdo->prepare("SELECT COALESCE(SUM(final_amount), 0) as total_sales, 
                                               COUNT(*) as nb_sales,
                                               COALESCE(SUM(discount_amount), 0) as total_discounts
                                               FROM sales 
                                               WHERE cash_session_id = ? 
                                               AND DATE(sale_date) = CURDATE()");
        $stmt_sales->execute([$session['id']]);
        $sales_summary = $stmt_sales->fetch();
        
        $total_sales = $sales_summary['total_sales'];
        $nb_sales = $sales_summary['nb_sales'];
        $total_discounts = $sales_summary['total_discounts'];
        
        // Estimer le montant final (fonds initial + ventes - remises)
        $estimated_final = $session['opening_amount'] + $total_sales - $total_discounts;
        
        // Fermer la session avec les informations automatiques
        $notes = "Fermeture automatique - " . $nb_sales . " vente(s) - Total: " . formatMoney($total_sales) . " - Remises: " . formatMoney($total_discounts);
        
        $stmt_close = $pdo->prepare("UPDATE cash_sessions SET 
                                    closing_time = NOW(),
                                    closing_amount = ?,
                                    status = 'closed',
                                    notes = ?
                                    WHERE id = ?");
        
        $result = $stmt_close->execute([
            $estimated_final,
            $notes,
            $session['id']
        ]);
        
        if ($result) {
            echo "✅ Session #" . $session['id'] . " fermée automatiquement\n";
            echo "   - Caissier: " . $session['cashier_name'] . "\n";
            echo "   - Ventes: " . $nb_sales . "\n";
            echo "   - Total ventes: " . formatMoney($total_sales) . "\n";
            echo "   - Montant final estimé: " . formatMoney($estimated_final) . "\n";
            
            // Envoyer une notification (optionnel - à implémenter selon vos besoins)
            // sendNotification($session['cashier_id'], 'Fermeture automatique de caisse', $notes);
            
        } else {
            echo "❌ Erreur lors de la fermeture de la session #" . $session['id'] . "\n";
        }
    }
    
    echo "Fermeture automatique terminée - " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Fonction pour envoyer des notifications (à implémenter selon vos besoins)
 */
function sendNotification($user_id, $title, $message) {
    // Implémentez ici votre système de notification
    // Email, SMS, notification interne, etc.
    echo "Notification envoyée à l'utilisateur $user_id: $title\n";
}

?>
