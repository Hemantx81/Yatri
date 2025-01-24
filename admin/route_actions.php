<?php
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $routeId = $_POST['route_id'];

    if ($action === 'delete') {
        // Ensure the route is not already inactive before deletion
        $query = "SELECT departure_time FROM routes WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $routeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $departureTime = $result['departure_time'];
            if (strtotime($departureTime) < time()) {
                echo json_encode(['message' => 'This route is already inactive.']);
                exit;
            }

            // Proceed with deleting the route and associated seat availability
            $deleteQuery = "DELETE FROM seat_availability WHERE route_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param("i", $routeId);
            $deleteStmt->execute();

            $routeDeleteQuery = "DELETE FROM routes WHERE id = ?";
            $routeDeleteStmt = $conn->prepare($routeDeleteQuery);
            $routeDeleteStmt->bind_param("i", $routeId);
            $routeDeleteStmt->execute();

            echo json_encode(['message' => 'Route and associated seat availability deleted successfully.']);
        } else {
            echo json_encode(['message' => 'Route not found.']);
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
