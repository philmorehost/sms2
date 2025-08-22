<?php
$page_title = 'My Transactions';
require_once 'app/bootstrap.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Pagination Logic ---
$limit = 20; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of records for the user
$count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM transactions WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Fetch transactions for the current page
$transactions = [];
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Transactions</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" class="text-center">You have no transactions yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($txn['created_at'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $txn['type'])); ?></td>
                            <td><?php echo get_currency_symbol(); ?><?php echo number_format($txn['amount'], 2); ?></td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($txn['status']);
                                    $badge_class = 'bg-secondary';
                                    if ($status == 'completed') $badge_class = 'bg-success';
                                    if ($status == 'failed' || $status == 'cancelled') $badge_class = 'bg-danger';
                                    if ($status == 'pending') $badge_class = 'bg-warning';
                                    echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($txn['reference']); ?></td>
                            <td>
                                <?php if ($txn['invoice_id']): ?>
                                    <a href="view-invoice.php?id=<?php echo $txn['invoice_id']; ?>" class="btn btn-sm btn-outline-primary">View Invoice</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <!-- Pagination Controls -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
