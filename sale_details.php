<?php
$page_title = 'Détails de la vente';
require_once 'includes/header.php';

$sale_id = intval($_GET['id'] ?? 0);

if ($sale_id <= 0) {
    header('Location: sales.php');
    exit();
}

try {
    // Récupérer les détails de la vente
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name as cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.cashier_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        header('Location: sales.php');
        exit();
    }
    
    // Récupérer les produits vendus
    $stmt = $pdo->prepare("
        SELECT sd.*, p.name as product_name, p.barcode 
        FROM sale_details sd 
        LEFT JOIN products p ON sd.product_id = p.id 
        WHERE sd.sale_id = ?
        ORDER BY sd.id ASC
    ");
    $stmt->execute([$sale_id]);
    $sale_details = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <i class="fas fa-receipt me-2"></i>Détails de la vente #<?php echo $sale['id']; ?>
            </h1>
            <div>
                <button onclick="printReceipt()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Imprimer
                </button>
                <a href="sales.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informations de la vente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
                        <p><strong>Vendeur:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
                        <p><strong>Méthode de paiement:</strong> 
                            <?php
                            $methods = [
                                'cash' => 'Espèces',
                                'card' => 'Carte bancaire',
                                'mobile' => 'Mobile Money'
                            ];
                            echo $methods[$sale['payment_method']] ?? $sale['payment_method'];
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($sale['customer_name']): ?>
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?></p>
                        <?php endif; ?>
                        <?php if ($sale['customer_phone']): ?>
                            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($sale['notes']): ?>
                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($sale['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Produits vendus</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code-barres</th>
                                <th>Produit</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sale_details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['barcode'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                <td class="text-end"><?php echo $detail['quantity']; ?></td>
                                <td class="text-end"><?php echo formatMoney($detail['unit_price']); ?></td>
                                <td class="text-end fw-bold"><?php echo formatMoney($detail['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <td colspan="4" class="fw-bold">Sous-total</td>
                                <td class="text-end fw-bold"><?php echo formatMoney($sale['total_amount']); ?></td>
                            </tr>
                            <?php if ($sale['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="4" class="fw-bold">Remise</td>
                                <td class="text-end fw-bold text-danger">-<?php echo formatMoney($sale['discount_amount']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <td colspan="4" class="fw-bold">Total à payer</td>
                                <td class="text-end fw-bold text-primary"><?php echo formatMoney($sale['final_amount']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Résumé</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Nombre d'articles:</span>
                        <span class="badge bg-info"><?php echo count($sale_details); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total quantités:</span>
                        <span class="badge bg-secondary"><?php echo array_sum(array_column($sale_details, 'quantity')); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Prix moyen:</span>
                        <span class="badge bg-primary"><?php echo formatMoney($sale['final_amount'] / array_sum(array_column($sale_details, 'quantity'))); ?></span>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <h6 class="mb-3">Montants</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total:</span>
                        <span><?php echo formatMoney($sale['total_amount']); ?></span>
                    </div>
                    <?php if ($sale['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Remise:</span>
                        <span class="text-danger">-<?php echo formatMoney($sale['discount_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold text-primary"><?php echo formatMoney($sale['final_amount']); ?></span>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <button onclick="printReceipt()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Imprimer le ticket
                    </button>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="fas fa-file-alt me-2"></i>Imprimer la page
                    </button>
                    <a href="sales.php" class="btn btn-outline-info">
                        <i class="fas fa-list me-2"></i>Voir toutes les ventes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Informations système -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Informations système</h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <p class="mb-1"><strong>ID Vente:</strong> #<?php echo $sale['id']; ?></p>
                    <p class="mb-1"><strong>Date création:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
                    <p class="mb-0"><strong>Caissier ID:</strong> <?php echo $sale['cashier_id']; ?></p>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Ticket imprimable (caché) -->
<div id="printableTicket" style="display: none;">
    <div style="font-family: monospace; font-size: 12px; width: 300px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="margin: 0;"><?php echo SITE_NAME; ?></h2>
            <p style="margin: 5px 0;">Système de Gestion</p>
            <p style="margin: 5px 0;">Tel: +225 XX XX XX XX</p>
        </div>
        
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
            <p style="margin: 5px 0;"><strong>Ticket #<?php echo $sale['id']; ?></strong></p>
            <p style="margin: 5px 0;">Date: <?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?></p>
            <p style="margin: 5px 0;">Vendeur: <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
            <?php if ($sale['customer_name']): ?>
                <p style="margin: 5px 0;">Client: <?php echo htmlspecialchars($sale['customer_name']); ?></p>
            <?php endif; ?>
        </div>
        
        <div style="margin: 10px 0;">
            <?php foreach ($sale_details as $detail): ?>
                <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                    <div>
                        <div><?php echo htmlspecialchars($detail['product_name']); ?></div>
                        <small><?php echo $detail['quantity']; ?> × <?php echo formatMoney($detail['unit_price']); ?></small>
                    </div>
                    <div><?php echo formatMoney($detail['total_price']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                <span>Sous-total:</span>
                <span><?php echo formatMoney($sale['total_amount']); ?></span>
            </div>
            <?php if ($sale['discount_amount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                    <span>Remise:</span>
                    <span>-<?php echo formatMoney($sale['discount_amount']); ?></span>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; margin: 5px 0; font-weight: bold;">
                <span>TOTAL:</span>
                <span><?php echo formatMoney($sale['final_amount']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                <span>Paiement:</span>
                <span><?php echo $methods[$sale['payment_method']] ?? $sale['payment_method']; ?></span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p style="margin: 5px 0;">Merci pour votre visite!</p>
            <p style="margin: 5px 0;">Au plaisir de vous revoir</p>
        </div>
    </div>
</div>

<?php
$page_script = "
function printReceipt() {
    const ticketContent = document.getElementById('printableTicket').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Ticket de vente #{$sale['id']}</title>
                <style>
                    body { margin: 0; padding: 10px; font-family: monospace; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                \` + ticketContent + \`
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
";
?>

<?php require_once 'includes/footer.php'; ?>
