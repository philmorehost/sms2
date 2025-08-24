<?php
require_once 'app/bootstrap.php';
require_once 'app/vendor/fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to download receipts.");
}
$user_id = $_SESSION['user_id'];

$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$transaction_id) {
    die("Invalid transaction ID.");
}

// Fetch transaction details, ensuring the user owns it (unless they are an admin)
$sql = "SELECT t.*, u.username, u.email FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ?";
if (!is_admin()) {
    $sql .= " AND t.user_id = ?";
}

$stmt = $conn->prepare($sql);
if (is_admin()) {
    $stmt->bind_param("i", $transaction_id);
} else {
    $stmt->bind_param("ii", $transaction_id, $user_id);
}
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    die("Transaction not found or you do not have permission to view it.");
}

// --- PDF Generation ---
class PDF extends FPDF
{
    // Page header
    function Header()
    {
        $settings = get_settings();
        $logo_path = !empty($settings['site_logo']) ? $settings['site_logo'] : null;
        if ($logo_path && file_exists($logo_path)) {
            $this->Image($logo_path, 10, 6, 30);
        }
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Transaction Receipt', 0, 0, 'C');
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Receipt details table
    function ReceiptTable($header, $data)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(50, 7, $header, 1);
        $this->SetFont('');
        $this->Cell(0, 7, $data, 1);
        $this->Ln();
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Receipt for Transaction #' . $transaction['id'], 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->ReceiptTable('Transaction ID:', $transaction['id']);
$pdf->ReceiptTable('Date:', date('Y-m-d H:i:s', strtotime($transaction['created_at'])));
$pdf->ReceiptTable('Username:', $transaction['username']);
$pdf->ReceiptTable('Email:', $transaction['email']);
$pdf->ReceiptTable('Description:', $transaction['description']);
$pdf->ReceiptTable('Amount:', get_currency_symbol() . number_format($transaction['amount'], 2));
if ($transaction['total_amount'] != $transaction['amount']) {
    $pdf->ReceiptTable('Amount Paid:', get_currency_symbol() . number_format($transaction['total_amount'], 2));
}
$pdf->ReceiptTable('Gateway:', ucfirst($transaction['gateway']));
$pdf->ReceiptTable('Status:', ucfirst($transaction['status']));

if($transaction['vtu_is_refunded']){
    $pdf->ReceiptTable('Refund Status:', 'Refunded');
}

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');

$pdf->Output('D', 'receipt_' . $transaction['id'] . '.pdf');
?>
