<?php
include("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'];
    $paymentStatus = $_POST['payment_status'];

    $stmt = $conn->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $paymentStatus, $bookingId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
    }
}
