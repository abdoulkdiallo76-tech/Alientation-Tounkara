<?php
$page_title = 'Vente réussie';
require_once 'includes/header.php';

if (!isset($_SESSION['sale_success']) || !isset($_SESSION['sale_id'])) {
    header('Location: pos.php');
    exit();
}

$sale_id = $_SESSION['sale_id'];
unset($_SESSION['sale_success']);
unset($_SESSION['sale_id']);

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
    
    // Récupérer les produits vendus
    $stmt = $pdo->prepare("
        SELECT sd.*, p.name as product_name 
        FROM sale_details sd 
        LEFT JOIN products p ON sd.product_id = p.id 
        WHERE sd.sale_id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale_details = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-12">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            </div>
            <h1 class="h3 text-success">Vente réussie!</h1>
            <p class="text-muted">La vente a été traitée avec succès</p>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php else: ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ticket de vente #<?php echo $sale['id']; ?></h5>
                    <div>
                        <button onclick="printReceipt()" class="btn btn-sm btn-primary">
                            <i class="fas fa-print me-2"></i>Imprimer
                        </button>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-receipt me-2"></i>Ticket
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- En-tête du ticket -->
                <div class="text-center mb-4">
                    <h4><?php echo SITE_NAME; ?></h4>
                    <p class="text-muted">Système de Gestion</p>
                    <hr>
                    <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
                    <p class="mb-1"><strong>Vendeur:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
                    <?php if ($sale['customer_name']): ?>
                        <p class="mb-1"><strong>Client:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($sale['customer_phone']): ?>
                        <p class="mb-1"><strong>Téléphone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Produits vendus -->
                <div class="mb-4">
                    <h6>Produits</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-end">Qté</th>
                                <th class="text-end">Prix</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sale_details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                <td class="text-end"><?php echo $detail['quantity']; ?></td>
                                <td class="text-end"><?php echo formatMoney($detail['unit_price']); ?></td>
                                <td class="text-end"><?php echo formatMoney($detail['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Totaux -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Sous-total:</strong></p>
                        <?php if ($sale['discount_amount'] > 0): ?>
                            <p class="mb-1"><strong>Remise:</strong></p>
                        <?php endif; ?>
                        <p class="mb-1"><strong>Total:</strong></p>
                        <p class="mb-1"><strong>Méthode:</strong></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-1"><?php echo formatMoney($sale['total_amount']); ?></p>
                        <?php if ($sale['discount_amount'] > 0): ?>
                            <p class="mb-1 text-danger">-<?php echo formatMoney($sale['discount_amount']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1 fw-bold text-primary"><?php echo formatMoney($sale['final_amount']); ?></p>
                        <p class="mb-1">
                            <?php
                            $payment_methods = [
                                'cash' => 'Espèces',
                                'card' => 'Carte bancaire',
                                'mobile' => 'Mobile Money'
                            ];
                            echo $payment_methods[$sale['payment_method']] ?? $sale['payment_method'];
                            ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($sale['notes']): ?>
                    <div class="mb-4">
                        <h6>Notes</h6>
                        <p class="text-muted"><?php echo htmlspecialchars($sale['notes']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Pied du ticket -->
                <div class="text-center mt-4">
                    <hr>
                    <p class="mb-1 text-muted">Merci pour votre visite!</p>
                    <p class="mb-0 text-muted">Au plaisir de vous revoir</p>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <a href="pos.php" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>Nouvelle vente
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="sales.php" class="btn btn-primary w-100">
                            <i class="fas fa-list me-2"></i>Voir les ventes
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-home me-2"></i>Tableau de bord
                        </a>
                    </div>
                </div>
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
                <span><?php echo $payment_methods[$sale['payment_method']] ?? $sale['payment_method']; ?></span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p style="margin: 5px 0;">Merci pour votre visite!</p>
            <p style="margin: 5px 0;">Au plaisir de vous revoir</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$page_script = "
function printReceipt() {
    const ticketContent = document.getElementById('printableTicket').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(
        '<html>' +
            '<head>' +
                '<title>Ticket de vente #' + " . $sale['id'] . " + '</title>' +
                '<style>' +
                    'body { margin: 0; padding: 10px; font-family: monospace; }' +
                    '@media print { body { margin: 0; }' +
                '</style>' +
            '</head>' +
            '<body>' +
                ticketContent +
            '</body>' +
        '</html>'
    );
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Auto-redirection après 30 secondes
setTimeout(function() {
    if (confirm('Voulez-vous effectuer une nouvelle vente?')) {
        window.location.href = 'pos.php';
    }
}, 30000);
";
?>

<?php require_once 'includes/footer.php'; ?>
