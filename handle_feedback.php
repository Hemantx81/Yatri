<?php
// Handle feedback submission
session_start();
include("includes/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_id = $_POST['bus_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id']; // Assuming the user is logged in and the user ID is stored in the session

    // Check if rating is between 1 and 5
    if ($rating < 1 || $rating > 5) {
        // Redirect back with an error message
        header("Location: index.php?feedback_error=1");
        exit();
    }

    // Insert feedback into the database
    $query = "INSERT INTO feedback (user_id, bus_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiis", $user_id, $bus_id, $rating, $comment);

    if ($stmt->execute()) {
        // Redirect back to the homepage with a success message
        header("Location: index.php?feedback_success=1");
    } else {
        // Redirect back with an error message
        header("Location: index.php?feedback_error=1");
    }
}
