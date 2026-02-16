<?php
// admin/ajax/manage_invoice.php
require_once __DIR__ . '/../../app/bootstrap.php';

// Authenticate admin
if (!is_admin()) {
    // Return a JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

$invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($invoice_id === 0 || empty($action)) {
    // Redirect back with an error message
    $_SESSION['error_message'] = 'Invalid request.';
    header("Location: ../view-invoice.php?id=" . $invoice_id);
    exit();
}

$redirect_url = "../transactions.php"; // Default redirect

switch ($action) {
    case 'approve':
        // Change invoice status to 'paid'
        $stmt_approve = $conn->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
        $stmt_approve->bind_param("i", $invoice_id);
        if ($stmt_approve->execute()) {
            $_SESSION['success_message'] = "Invoice #$invoice_id has been approved.";
        } else {
            $_SESSION['error_message'] = "Failed to approve invoice.";
        }
        $stmt_approve->close();
        $redirect_url = "../view-invoice.php?id=" . $invoice_id;
        break;

    case 'reject':
        // Change invoice status to 'cancelled' or 'rejected'
        $stmt_reject = $conn->prepare("UPDATE invoices SET status = 'cancelled' WHERE id = ?");
        $stmt_reject->bind_param("i", $invoice_id);
        if ($stmt_reject->execute()) {
            $_SESSION['success_message'] = "Invoice #$invoice_id has been rejected.";
        } else {
            $_SESSION['error_message'] = "Failed to reject invoice.";
        }
        $stmt_reject->close();
        $redirect_url = "../view-invoice.php?id=" . $invoice_id;
        break;

    case 'delete':
        // Delete the invoice
        // For safety, you might want to check for related records or just mark it as deleted
        $stmt_delete = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt_delete->bind_param("i", $invoice_id);
        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Invoice #$invoice_id has been deleted.";
        } else {
            $_SESSION['error_message'] = "Failed to delete invoice.";
        }
        $stmt_delete->close();
        // After deletion, redirect to the main transactions/invoices list
        break;

    default:
        $_SESSION['error_message'] = 'Invalid action specified.';
        $redirect_url = "../view-invoice.php?id=" . $invoice_id;
        break;
}

$conn->close();

header("Location: " . $redirect_url);
exit();
?>
