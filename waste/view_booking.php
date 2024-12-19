<?php
// Start session and include config file
session_start();
include("includes/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    die("Booking ID is required.");
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch booking details
$query = "SELECT b.id, r.source, r.destination, r.departure_time, b.seats_booked, b.payment_status, b.created_at, b.seat_numbers
          FROM bookings b
          JOIN routes r ON b.route_id = r.id
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>View Booking - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .card-header {
            font-size: 1.5rem;
        }

        .table td,
        .table th {
            text-align: center;
        }

        .btn-danger {
            font-size: 1.2rem;
            padding: 10px;
        }

        .btn {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header">
                <h3>Booking Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Route</th>
                        <td><?= htmlspecialchars($booking['source']) . ' → ' . htmlspecialchars($booking['destination']) ?></td>
                    </tr>
                    <tr>
                        <th>Departure Time</th>
                        <td><?= date('d-m-Y H:i', strtotime($booking['departure_time'])) ?></td>
                    </tr>
                    <tr>
                        <th>Seats Booked</th>
                        <td><?= htmlspecialchars($booking['seats_booked']) ?></td>
                    </tr>
                    <tr>
                        <th>Seat Numbers</th>
                        <td><?= htmlspecialchars($booking['seat_numbers']) ?></td>
                    </tr>
                    <tr>
                        <th>Payment Status</th>
                        <td>
                            <?php
                            if ($booking['payment_status'] == 'Completed') {
                                echo '<span class="text-success">Completed</span>';
                            } elseif ($booking['payment_status'] == 'Pending') {
                                echo '<span class="text-warning">Pending</span>';
                            } else {
                                echo '<span class="text-danger">Failed</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Booking Time</th>
                        <td><?= date('d-m-Y H:i', strtotime($booking['created_at'])) ?></td>
                    </tr>
                </table>
                <?php if ($booking['payment_status'] == 'Pending') : ?>
                    <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="btn btn-danger">Cancel Booking</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>