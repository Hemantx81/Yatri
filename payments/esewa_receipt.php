<?php
session_start();
include('../includes/config.php');
require("../fpdf/fpdf.php");

// Check if booking_id is set
if (!isset($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];

// Fetch payment details
$query = $conn->prepare("SELECT b.*, p.payment_method, p.transaction_id, p.payment_amount, p.payment_status
                        FROM bookings b
                        JOIN payments p ON b.id = p.booking_id
                        WHERE b.id = ?");
$query->bind_param('i', $booking_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("<script>alert('Payment details not found.'); window.location.href = '../dashboard.php';</script>");
}

$payment = $result->fetch_assoc();

// Create PDF using TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(93, 43, 128); // Khalti Purple

// Title
$pdf->Cell(0, 10, 'Payment Receipt for Booking ID: ' . $payment['booking_id'], 0, 1, 'C');
$pdf->Ln(10);

// Payment details
$pdf->SetTextColor(0, 0, 0); // Black text
$pdf->Cell(50, 10, 'Transaction ID:', 0, 0);
$pdf->Cell(0, 10, $payment['transaction_id'], 0, 1);

$pdf->Cell(50, 10, 'Payment Method:', 0, 0);
$pdf->Cell(0, 10, $payment['payment_method'], 0, 1);

$pdf->Cell(50, 10, 'Payment Amount:', 0, 0);
$pdf->Cell(0, 10, 'NPR ' . number_format($payment['payment_amount'], 2), 0, 1);

$pdf->Cell(50, 10, 'Payment Status:', 0, 0);
$pdf->Cell(0, 10, $payment['payment_status'], 0, 1);

// Output the PDF as a download
$pdf->Output('Payment_Receipt_' . $payment['transaction_id'] . '.pdf', 'D');
exit;
