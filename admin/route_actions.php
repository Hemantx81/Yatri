<?php
include('../includes/config.php');

header('Content-Type: application/json');

$response = [];

// DELETE Route (with foreign key action set to NULL or DEFAULT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // DELETE Route
    if ($action === 'delete') {
        $routeId = $_POST['route_id'] ?? null;

        if ($routeId) {
            $deleteQuery = "DELETE FROM routes WHERE id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $routeId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['message'] = 'Route deleted successfully.';
                } else {
                    $response['message'] = 'No route found with the provided ID.';
                }
            } else {
                $response['message'] = 'Error deleting route: ' . $stmt->error;
            }
        } else {
            $response['message'] = 'Error: route_id is required.';
        }
    }
}

// Return the response as JSON
echo json_encode($response);

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
