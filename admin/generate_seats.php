<?php
session_start();
include_once '../includes/config.php';

// Check if bus_id and route_id are provided
if (isset($_GET['bus_id']) && isset($_GET['route_id'])) {
    $bus_id = intval($_GET['bus_id']);
    $route_id = intval($_GET['route_id']);

    // Fetch total seats for the bus
    $seat_query = $conn->prepare("SELECT total_seats FROM buses WHERE id = ?");
    $seat_query->bind_param("i", $bus_id);
    $seat_query->execute();
    $result = $seat_query->get_result();
    $bus_data = $result->fetch_assoc();

    if ($bus_data) {
        $total_seats = $bus_data['total_seats'];

        // Insert seat availability for each seat in the new route
        $insert_seat_query = $conn->prepare("INSERT INTO seat_availability (bus_id, route_id, seat_number, status) 
                                             VALUES (?, ?, ?, 'available')");

        // Loop through each seat and insert
        for ($seat_number = 1; $seat_number <= $total_seats; $seat_number++) {
            $insert_seat_query->bind_param("iii", $bus_id, $route_id, $seat_number);
            $insert_seat_query->execute();
        }

        $_SESSION['success_message'] = "Seats successfully generated for the new route!";
    } else {
        $_SESSION['error_message'] = "Bus not found!";
    }
} else {
    $_SESSION['error_message'] = "Invalid request!";
}

header("Location: add_route.php");
exit;
