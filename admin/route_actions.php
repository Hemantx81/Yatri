<?php
session_start();
include('../includes/config.php');

$action = $_POST['action'] ?? $_GET['action'];

if ($action == 'update') {
    // Collect data from POST
    $route_id = $_POST['route_id'];
    $bus_id = $_POST['bus_id'];
    $source = $_POST['source'];
    $destination = $_POST['destination'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];

    // Check if all required fields are provided
    if (empty($route_id) || empty($bus_id) || empty($source) || empty($destination) || empty($departure_time) || empty($arrival_time) || empty($price)) {
        echo json_encode(['message' => 'All fields are required.']);
        exit();
    }

    // Update the route in the database
    $stmt = $conn->prepare("UPDATE routes SET bus_id = ?, source = ?, destination = ?, departure_time = ?, arrival_time = ?, price = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("issssdi", $bus_id, $source, $destination, $departure_time, $arrival_time, $price, $route_id);

        // Execute and check if the query was successful
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Route updated successfully.']);
        } else {
            echo json_encode(['message' => 'Error updating route. Please try again later.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['message' => 'Database error: Unable to prepare update statement.']);
    }
    exit();
} elseif ($action == 'delete') {
    $route_id = $_POST['route_id'];

    // Check if there are bookings for the route
    $stmt = $conn->prepare("SELECT COUNT(*) AS bookings_count FROM bookings WHERE route_id = ?");
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['bookings_count'];

    if ($count > 0) {
        echo json_encode(['message' => 'Cannot delete route. There are bookings associated with this route.']);
        exit();
    }

    // Delete the route
    $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
    $stmt->bind_param("i", $route_id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Route deleted successfully.']);
    } else {
        echo json_encode(['message' => 'Error deleting route. Please try again later.']);
    }
    $stmt->close();
    exit();
} else {
    echo json_encode(['message' => 'Invalid action.']);
}
