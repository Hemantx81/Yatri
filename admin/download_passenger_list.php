<?php
require '../includes/config.php'; // Connection to the database
require '../fpdf/fpdf.php'; // Include FPDF library

$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    die('Route ID not provided.');
}

// Fetch bus and route details
$route_query = "
    SELECT r.source, r.destination, r.departure_time, r.arrival_time, 
           b.bus_name 
    FROM routes r
    JOIN buses b ON r.bus_id = b.id
    WHERE r.id = ?";
$stmt = $conn->prepare($route_query);
$stmt->bind_param("i", $route_id);
$stmt->execute();
$route_result = $stmt->get_result()->fetch_assoc();

// Fetch passenger details
$passenger_query = "
    SELECT u.name, u.email, u.phone, b.seats_booked, b.per_seat_price
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.route_id = ?";
$stmt = $conn->prepare($passenger_query);
$stmt->bind_param("i", $route_id);
$stmt->execute();
$passenger_result = $stmt->get_result();

// Create the PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Add title
$pdf->Cell(0, 10, 'Passenger List', 0, 1, 'C');

// Add bus details
$pdf->SetFont('Arial', '', 12);
$pdf->Ln(10);
$pdf->Cell(0, 10, 'Bus Name: ' . $route_result['bus_name'], 0, 1);
$pdf->Cell(0, 10, 'Source: ' . $route_result['source'], 0, 1);
$pdf->Cell(0, 10, 'Destination: ' . $route_result['destination'], 0, 1);
$pdf->Cell(0, 10, 'Departure Time: ' . $route_result['departure_time'], 0, 1);
$pdf->Ln(10);

// Add passenger table
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, 'Name', 1);
$pdf->Cell(50, 10, 'Email', 1);
$pdf->Cell(30, 10, 'Phone', 1);
$pdf->Cell(20, 10, 'Seats', 1);
$pdf->Cell(30, 10, 'Seat Price (NPR)', 1);
$pdf->Cell(30, 10, 'Total (NPR)', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
$total_amount = 0;

while ($row = $passenger_result->fetch_assoc()) {
    $row_total = $row['seats_booked'] * $row['per_seat_price'];
    $total_amount += $row_total;

    $pdf->Cell(40, 10, $row['name'], 1);
    $pdf->Cell(50, 10, $row['email'], 1);
    $pdf->Cell(30, 10, $row['phone'], 1);
    $pdf->Cell(20, 10, $row['seats_booked'], 1);
    $pdf->Cell(30, 10, 'Rs. ' . number_format($row['per_seat_price'], 2), 1);
    $pdf->Cell(30, 10, 'Rs. ' . number_format($row_total, 2), 1);
    $pdf->Ln();
}

// Add total amount row
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(170, 10, 'Total Amount:', 1, 0, 'R');
$pdf->Cell(30, 10, 'Rs. ' . number_format($total_amount, 2), 1, 0, 'C');

// Output the PDF
$pdf->Output('D', 'Passenger_List.pdf');
exit;
