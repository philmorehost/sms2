<?php
$page_title = 'View Invoice';
require_once 'app/bootstrap.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id === 0) {
    die("No invoice specified.");
}

// Fetch invoice details, ensuring it belongs to the logged-in user
$stmt = $conn->prepare("SELECT i.*, t.reference, t.gateway, t.description FROM invoices i LEFT JOIN transactions t ON i.transaction_id = t.id WHERE i.id = ? AND i.user_id = ?");
$stmt->bind_param("ii", $invoice_id, $user_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found or you do not have permission to view it.");
}

// Fetch user details for the invoice
$user_stmt = $conn->prepare("SELECT username, email, phone_number FROM users WHERE id = ?");
$user_stmt->bind_param("i", $invoice['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();


include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoice #<?php echo $invoice['id']; ?></h3>
        <div class="float-end">
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
                <div>Email: info@example.com</div>
                <div>Phone: +1 234 567 890</div>
            </div>
            <div class="col-sm-6">
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
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="center">1</td>
                        <td class="left strong"><?php echo htmlspecialchars($invoice['description'] ?? 'Wallet Funding'); ?></td>
                        <td class="right"><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-lg-4 col-sm-5 ml-auto">
                <table class="table table-clear">
                    <tbody>
                        <tr>
                            <td class="left">
                                <strong class="text-dark">Total</strong>
                            </td>
                            <td class="right">
                                <strong class="text-dark"><?php echo get_currency_symbol(); ?><?php echo number_format($invoice['total_amount'], 2); ?></strong>
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
                    <strong>Payment Method:</strong> <?php echo ucfirst($invoice['gateway']); ?><br>
                    <strong>Reference:</strong> <?php echo htmlspecialchars($invoice['reference']); ?><br>
                    <strong>Status:</strong> <span class="badge <?php echo $invoice['status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                </p>
            </div>
             <div class="col-md-6 text-md-end">
                <p>
                    <strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?><br>
                    <?php if($invoice['due_date']): ?>
                    <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?><br>
                    <?php endif; ?>
                </p>
            </div>
        </div>


    </div>
    <?php if ($invoice['status'] == 'unpaid'): ?>
    <div class="card-footer text-center">
        <h5>Complete Your Payment</h5>
        <p>Choose your preferred payment method below to pay for this invoice.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <!-- Paystack Button Form -->
            <form action="payment-gateway/paystack-invoice-init.php" method="POST">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                <input type="hidden" name="amount" value="<?php echo $invoice['total_amount']; ?>">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-credit-card"></i> Pay with Paystack</button>
            </form>
            <!-- Manual Transfer Button -->
            <a href="add-funds.php?invoice_id=<?php echo $invoice['id']; ?>#manual-content" class="btn btn-secondary btn-lg"><i class="fas fa-university"></i> Pay by Bank Transfer</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
