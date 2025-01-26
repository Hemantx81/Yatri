<?php
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $routeId = intval($_POST['route_id']);

    if ($action === 'disable') {
        $query = "UPDATE routes SET status = 'disabled' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $routeId);
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Route disabled successfully.']);
        } else {
            echo json_encode(['message' => 'Failed to disable route.']);
        }
    }
}

// UPDATE Route (unchanged, as per your request)
if ($action === 'update') {
    $routeId = $_POST['route_id'];
    $busName = $_POST['bus_name'];
    $source = $_POST['source'];
    $destination = $_POST['destination'];
    $departureTime = $_POST['departure_time'];
    $arrivalTime = $_POST['arrival_time'];
    $price = $_POST['price'];

    $updateQuery = "
            UPDATE routes
            SET bus_id = (SELECT id FROM buses WHERE bus_name = ?),
                source = ?, 
                destination = ?, 
                departure_time = ?, 
                arrival_time = ?, 
                price = ?
            WHERE id = ?
        ";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssssi", $busName, $source, $destination, $departureTime, $arrivalTime, $price, $routeId);

    if ($stmt->execute()) {
        $response['message'] = 'Route updated successfully.';
    } else {
        $response['message'] = 'Error updating route: ' . $stmt->error;
    }
}


echo json_encode($response);
