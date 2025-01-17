<?php
include("includes/config.php");

// Fetch all buses with their routes and departure times
$query_all_buses = "SELECT b.id AS bus_id, b.bus_name, b.image_path, r.source, r.destination, r.price, r.departure_time 
                    FROM buses b 
                    INNER JOIN routes r ON b.id = r.bus_id";
$result_all_buses = $conn->query($query_all_buses);
$buses = $result_all_buses->fetch_all(MYSQLI_ASSOC);

// Fetch all feedback with ratings
$query_all_feedback = "SELECT f.bus_id, f.rating FROM feedback f";
$result_all_feedback = $conn->query($query_all_feedback);
$feedback = $result_all_feedback->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode(['buses' => $buses, 'feedback' => $feedback]);
