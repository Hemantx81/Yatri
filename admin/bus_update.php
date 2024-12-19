<?php
include('../includes/config.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $seats = $_POST['seats'];
    $is_ac = isset($_POST['is_ac']) ? 1 : 0; // Checkbox for AC availability
    $is_wifi = isset($_POST['is_wifi']) ? 1 : 0; // Checkbox for Wi-Fi availability
    $imagePath = '';

    if (!empty($_FILES['image']['name'])) {
        // Handle file upload
        $targetDir = "../assets/images/";
        $imagePath = $targetDir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        $imagePath = substr($imagePath, 3); // Remove the "../" prefix for saving into the database
    }

    // Update query
    if ($imagePath) {
        $sql = "UPDATE buses SET bus_name = ?, total_seats = ?, image_path = ?, is_ac = ?, is_wifi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisiii', $name, $seats, $imagePath, $is_ac, $is_wifi, $id);
    } else {
        $sql = "UPDATE buses SET bus_name = ?, total_seats = ?, is_ac = ?, is_wifi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siiii', $name, $seats, $is_ac, $is_wifi, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bus updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update bus']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
