<?php
include("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete booking']);
    }
}
