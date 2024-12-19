<?php
session_start();
include("includes/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch booking details
$query = "SELECT b.id, r.source, r.destination, r.departure_time, b.seats_booked, b.payment_status, b.total_amount 
          FROM bookings b
          JOIN routes r ON b.route_id = r.id
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or access denied.';
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Booking Details</h2>
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
                        <th>Total Amount</th>
                        <td><?= htmlspecialchars($booking['total_amount']) ?> NPR</td>
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
                </table>

                <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>

</html>