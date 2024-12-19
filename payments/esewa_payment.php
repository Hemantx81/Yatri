<?php
session_start();
include('../includes/config.php');

// Check if booking_id is set
if (!isset($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];

// Fetch booking details
$query = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$query->bind_param('i', $booking_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("<script>alert('Booking not found.'); window.location.href = '../dashboard.php';</script>");
}

$booking = $result->fetch_assoc();

// Simulate payment success
$transaction_id = "ES" . time(); // Generate a mock transaction ID
$payment_amount = $booking['total_amount'];

// Insert into payments table with 'Pending' status
$payment_query = $conn->prepare("
    INSERT INTO payments (booking_id, payment_method, transaction_id, payment_amount, payment_status)
    VALUES (?, 'eSewa', ?, ?, 'Pending')
");
$payment_query->bind_param('isd', $booking_id, $transaction_id, $payment_amount);
$payment_query->execute();

// Update booking payment status to 'Pending'
$update_query = $conn->prepare("UPDATE bookings SET payment_status = 'Pending' WHERE id = ?");
$update_query->bind_param('i', $booking_id);
$update_query->execute();

// Redirect to the payment success page
echo "<script>alert('Payment Successful with eSewa! Awaiting admin approval.'); window.location.href = 'payment_success.php?booking_id=$booking_id';</script>";
