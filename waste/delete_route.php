<?php
session_start();
include('../includes/config.php'); // Database connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Route ID is required']);
        exit;
    }

    $route_id = intval($_POST['id']);

    // Delete the route
    $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
    $stmt->bind_param("i", $route_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Route deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete route']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
