<?php
session_start();
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Check if the bus has dependent records in `routes` or `bookings`
    $check_routes = $conn->prepare("SELECT COUNT(*) AS count FROM routes WHERE bus_id = ?");
    $check_routes->bind_param('i', $id);
    $check_routes->execute();
    $routes_count = $check_routes->get_result()->fetch_assoc()['count'];

    if ($routes_count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Bus has associated routes. Delete the routes first.']);
        exit;
    }

    // Delete the bus
    $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bus deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete bus.']);
    }
}
