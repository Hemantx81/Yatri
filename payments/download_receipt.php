<?php
session_start();
include('../includes/config.php');
require_once('../vendor/autoload.php');  // Include TCPDF

// Check if booking_id is set
if (!isset($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];

// Fetch payment details
$query = $conn->prepare("
    SELECT b.*, p.payment_method, p.payment_amount, p.payment_status, p.transaction_id
    FROM bookings b
    JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ?
");
$query->bind_param('i', $booking_id);
$query->execute();
$result = $query->get_result();

// Check if the query returned results
if ($result->num_rows === 0) {
    die("<script>alert('Payment details not found.'); window.location.href = '../dashboard.php';</script>");
}

$payment = $result->fetch_assoc();

// Debugging: Check if payment data contains "booking_id"
if (!isset($payment['booking_id'])) {
    die("<script>alert('Payment data is incomplete or incorrect.'); window.location.href = '../dashboard.php';</script>");
}

// Create PDF using TCPDF
$pdf = new TCPDF();

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->SetTextColor(93, 43, 128); // Khalti Purple
$pdf->Cell(0, 10, 'Khalti Payment Receipt', 0, 1, 'C');

// Line break
$pdf->Ln(10);

// Payment details
$pdf->SetTextColor(0, 0, 0); // Black text
$pdf->Cell(50, 10, 'Booking ID:', 0, 0);
$pdf->Cell(0, 10, $payment['booking_id'], 0, 1);

$pdf->Cell(50, 10, 'Transaction ID:', 0, 0);
$pdf->Cell(0, 10, $payment['transaction_id'], 0, 1);

$pdf->Cell(50, 10, 'Payment Method:', 0, 0);
$pdf->Cell(0, 10, $payment['payment_method'], 0, 1);

$pdf->Cell(50, 10, 'Payment Amount:', 0, 0);
$pdf->Cell(0, 10, 'NPR ' . number_format($payment['payment_amount'], 2), 0, 1);

$pdf->Cell(50, 10, 'Payment Status:', 0, 0);
$pdf->Cell(0, 10, $payment['payment_status'], 0, 1);

// Line break
$pdf->Ln(10);

// Add a button style for download (not clickable in PDF, just for visual reference)
$pdf->SetTextColor(93, 43, 128); // Khalti Purple
$pdf->Cell(0, 10, 'Click here to download the receipt on your device (not clickable in PDF)', 0, 1, 'C');

// Output the PDF as a download
$pdf->Output('Khalti_Receipt_' . $payment['transaction_id'] . '.pdf', 'D');
exit;
