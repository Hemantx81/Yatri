<?php
session_start();
include("includes/config.php");
require("fpdf/fpdf.php");

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if a booking ID is provided
if (!isset($_GET['booking_id'])) {
    die("Invalid request. Booking ID is required.");
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Fetch booking details
$query = "SELECT b.id AS booking_id, b.seats_booked, b.payment_status, r.source, r.destination, 
                 r.departure_time, r.arrival_time, r.price, u.name AS user_name 
          FROM bookings b
          JOIN routes r ON b.route_id = r.id
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found or you do not have permission to access it.");
}

$booking = $result->fetch_assoc();

// Generate PDF using FPDF
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Bus Ticket', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Generated by Online Bus Ticket Reservation System', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// User Information
$pdf->Cell(0, 10, 'Passenger Name: ' . htmlspecialchars($booking['user_name']), 0, 1);
$pdf->Ln(5);

// Booking Details
$pdf->Cell(0, 10, 'Booking ID: ' . $booking['booking_id'], 0, 1);
$pdf->Cell(0, 10, 'Source: ' . htmlspecialchars($booking['source']), 0, 1);
$pdf->Cell(0, 10, 'Destination: ' . htmlspecialchars($booking['destination']), 0, 1);
$pdf->Cell(0, 10, 'Departure Time: ' . htmlspecialchars($booking['departure_time']), 0, 1);
$pdf->Cell(0, 10, 'Arrival Time: ' . htmlspecialchars($booking['arrival_time']), 0, 1);
$pdf->Cell(0, 10, 'Seats Booked: ' . $booking['seats_booked'], 0, 1);
$pdf->Cell(0, 10, 'Total Price: NPR ' . number_format($booking['price'] * $booking['seats_booked'], 2), 0, 1);
$pdf->Cell(0, 10, 'Payment Status: ' . htmlspecialchars($booking['payment_status']), 0, 1);
$pdf->Ln(10);

// Thank You Note
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, 'Thank you for choosing our service!', 0, 1, 'C');

// Output the PDF
$pdf->Output('D', 'Booking_' . $booking['booking_id'] . '.pdf');
exit();
