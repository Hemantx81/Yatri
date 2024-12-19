<?php
session_start();
include_once "includes/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the booking ID from the URL
$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: user_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch booking details
$query = "SELECT b.user_id, b.seats_booked, b.payment_status, b.created_at, b.route_id 
          FROM bookings b 
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = "Booking not found.";
    header("Location: user_dashboard.php");
    exit();
}

// Ensure the booking payment status is "Pending"
if ($booking['payment_status'] !== 'Pending') {
    $_SESSION['error'] = "You can only cancel bookings with pending payment status.";
    header("Location: user_dashboard.php");
    exit();
}

// Ensure the booking is not older than 2 hours
$booking_time = strtotime($booking['created_at']);
$current_time = time();
if (($current_time - $booking_time) > 7200) { // 7200 seconds = 2 hours
    $_SESSION['error'] = "You can only cancel bookings within 2 hours of creation.";
    header("Location: user_dashboard.php");
    exit();
}

// Start cancellation process
$seat_numbers = explode(",", $booking['seats_booked']);
$route_id = $booking['route_id'];

// Update seat availability (set status to 'Available')
$placeholders = implode(',', array_fill(0, count($seat_numbers), '?'));
$update_seat_query = "UPDATE seat_availability 
                      SET status = 'Available', booking_time = NULL 
                      WHERE route_id = ? AND seat_number IN ($placeholders)";
$stmt = $conn->prepare($update_seat_query);

// Bind parameters dynamically
$types = 'i' . str_repeat('s', count($seat_numbers));
$params = array_merge([$route_id], $seat_numbers);
$stmt->bind_param($types, ...$params);
$stmt->execute();

// Remove the seat numbers from the frontend and database
$delete_ticket_query = "DELETE FROM seat_availability 
                        WHERE route_id = ? AND seat_number IN ($placeholders)";
$stmt = $conn->prepare($delete_ticket_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();

// Update the booking status to "Canceled"
$update_booking_query = "UPDATE bookings 
                         SET payment_status = 'Canceled' 
                         WHERE id = ?";
$stmt = $conn->prepare($update_booking_query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();

// Set success message and redirect
$_SESSION['message'] = "Booking has been successfully canceled and tickets removed.";
header("Location: user_dashboard.php");
exit();
