<?php
$page_title = 'View Invoice';
// Use the admin bootstrap
require_once __DIR__ . '/../app/bootstrap.php';

// Authenticate admin
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id === 0) {
    die("No invoice specified.");
}

// Fetch invoice details (admin can view any invoice)
$stmt = $conn->prepare(
    "SELECT i.*, t.reference, t.gateway, t.description as transaction_description
     FROM invoices i
     LEFT JOIN transactions t ON i.transaction_id = t.id
     WHERE i.id = ?"
);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found.");
}

// Fetch user details for the invoice
$user_stmt = $conn->prepare("SELECT username, email, phone_number FROM users WHERE id = ?");
$user_stmt->bind_param("i", $invoice['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Include the admin header
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Invoice #<?php echo $invoice['id']; ?></h3>
        <div class="float-end">
            <a href="../view-invoice.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-user"></i> View as User</a>
            <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h6 class="mb-3">From:</h6>
                <div>
                    <strong><?php echo SITE_NAME; ?></strong>
                </div>
                <div>Email: <?php echo get_settings()['admin_email'] ?? 'admin@example.com'; ?></div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h6 class="mb-3">To:</h6>
                <div>
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                </div>
                <div>Email: <?php echo htmlspecialchars($user['email']); ?></div>
                <div>Phone: <?php echo htmlspecialchars($user['phone_number']); ?></div>
            </div>
        </div>

        <div class="table-responsive-sm">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="center">#</th>
                        <th>Item</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="center">1</td>
                        <td class="left strong"><?php echo htmlspecialchars($invoice['description'] ?? $invoice['transaction_description'] ?? 'Wallet Funding'); ?></td>
                        <td class="text-end"><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-4 col-sm-5 ms-auto">
                <table class="table table-clear">
                    <tbody>
                        <tr>
                            <td class="left">
                                <strong>Subtotal</strong>
                            </td>
                            <td class="text-end"><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="left">
                                <strong>VAT (<?php echo (get_settings()['vat_percentage'] ?? 0); ?>%)</strong>
                            </td>
                            <td class="text-end"><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['vat_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="left">
                                <strong>Total</strong>
                            </td>
                            <td class="text-end">
                                <strong><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['total_amount'], 2); ?></strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h6>Payment Details</h6>
                <p>
                    <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $invoice['gateway'])); ?><br>
                    <strong>Reference:</strong> <?php echo htmlspecialchars($invoice['reference']); ?><br>
                </p>
            </div>
             <div class="col-md-6 text-md-end">
                <p>
                    <strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?><br>
                    <?php if($invoice['due_date']): ?>
                    <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?><br>
                    <?php endif; ?>
                    <strong>Status:</strong> <span class="badge <?php echo $invoice['status'] == 'paid' ? 'bg-success' : ($invoice['status'] == 'unpaid' ? 'bg-warning' : 'bg-danger'); ?>"><?php echo ucfirst($invoice['status']); ?></span>
                </p>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <form action="ajax/manage_invoice.php" method="POST" class="d-inline">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">

            <?php if ($invoice['status'] !== 'paid'): ?>
                <button type="submit" name="action" value="approve" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Approve
                </button>
            <?php endif; ?>

            <?php if ($invoice['status'] !== 'cancelled'): ?>
                <button type="submit" name="action" value="reject" class="btn btn-warning">
                    <i class="fas fa-times-circle"></i> Reject
                </button>
            <?php endif; ?>

            <button type="submit" name="action" value="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.');">
                <i class="fas fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

<?php
// Include the admin footer
include 'includes/footer.php';
?>
