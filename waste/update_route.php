<?php
session_start();
include('../includes/config.php'); // Database connection

header('Content-Type: application/json');  // Ensure JSON response header

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $route_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $bus_id = isset($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
    $source = isset($_POST['source']) ? trim($_POST['source']) : null;
    $destination = isset($_POST['destination']) ? trim($_POST['destination']) : null;
    $departure_time = isset($_POST['departure_time']) ? trim($_POST['departure_time']) : null;
    $arrival_time = isset($_POST['arrival_time']) ? trim($_POST['arrival_time']) : null;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;

    // Validate inputs
    if (!$route_id || !$bus_id || !$source || !$destination || !$departure_time || !$arrival_time || !$price) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Additional validation for price
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
        exit;
    }

    // Validate date format (ensure departure and arrival time are in valid datetime format)
    $date_format = 'Y-m-d H:i:s';
    if (!DateTime::createFromFormat($date_format, $departure_time) || !DateTime::createFromFormat($date_format, $arrival_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use Y-m-d H:i:s']);
        exit;
    }

    // Update the route
    $stmt = $conn->prepare("UPDATE routes SET bus_id = ?, source = ?, destination = ?, departure_time = ?, arrival_time = ?, price = ? WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement']);
        exit;
    }

    // Bind parameters and execute
    $stmt->bind_param("isssdsi", $bus_id, $source, $destination, $departure_time, $arrival_time, $price, $route_id);

    if ($stmt->execute()) {
        // Fetch updated route details
        $stmt = $conn->prepare("SELECT routes.*, buses.bus_name FROM routes JOIN buses ON routes.bus_id = buses.id WHERE routes.id = ?");
        $stmt->bind_param("i", $route_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $route = $result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Route updated successfully',
            'route' => $route
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update route']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
