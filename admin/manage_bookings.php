<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Pagination settings
$limit = 10; // Number of bookings per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

// Build query dynamically based on search and filter
$whereClause = "1=1"; // Default clause
if (!empty($search)) {
    $whereClause .= " AND (users.name LIKE '%$search%' OR users.email LIKE '%$search%' OR routes.source LIKE '%$search%' OR routes.destination LIKE '%$search%')";
}
if (!empty($filter)) {
    $whereClause .= " AND bookings.payment_status = '$filter'";
}

// Fetch total bookings count for pagination
$totalBookingsQuery = "SELECT COUNT(*) AS total 
    FROM bookings 
    INNER JOIN users ON bookings.user_id = users.id 
    INNER JOIN routes ON bookings.route_id = routes.id
    WHERE $whereClause";
$totalBookingsResult = $conn->query($totalBookingsQuery);
$totalBookings = $totalBookingsResult->fetch_assoc()['total'];

// Fetch bookings with pagination
$bookingsQuery = "SELECT bookings.id, users.name AS user_name, users.email AS user_email, 
                         routes.source, routes.destination, bookings.seats_booked, bookings.payment_status, bookings.created_at 
                  FROM bookings 
                  INNER JOIN users ON bookings.user_id = users.id 
                  INNER JOIN routes ON bookings.route_id = routes.id
                  WHERE $whereClause
                  ORDER BY bookings.created_at DESC
                  LIMIT $limit OFFSET $offset";
$bookingsResult = $conn->query($bookingsQuery);

// Handle booking deletion and seat availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Fetch the booking details
    $bookingQuery = $conn->prepare("SELECT seats_booked, route_id FROM bookings WHERE id = ?");
    $bookingQuery->bind_param('i', $booking_id);
    $bookingQuery->execute();
    $bookingResult = $bookingQuery->get_result();

    if ($bookingResult->num_rows > 0) {
        $booking = $bookingResult->fetch_assoc();
        $seats_booked = explode(',', $booking['seats_booked']);
        $route_id = $booking['route_id'];

        // Update seats in the seat_availability table to "available"
        $updateSeatsQuery = $conn->prepare("UPDATE seat_availability 
                                            SET status = 'available' 
                                            WHERE route_id = ? AND seat_number = ?");
        foreach ($seats_booked as $seat_number) {
            $updateSeatsQuery->bind_param('ii', $route_id, $seat_number);
            $updateSeatsQuery->execute();
        }

        // Delete the booking from the bookings table
        $deleteBookingQuery = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $deleteBookingQuery->bind_param('i', $booking_id);
        if ($deleteBookingQuery->execute()) {
            $_SESSION['success_msg'] = "Booking deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to delete booking.";
        }
    }
    header("Location: manage_bookings.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include("../includes/sidebar.php"); ?>
    <div class="container mt-5">
        <h1>Manage Bookings</h1>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_msg'] ?></div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_msg'] ?></div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <!-- Search and Filter -->
        <form method="GET" class="d-flex mb-3">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by user, email, source, destination" value="<?= htmlspecialchars($search) ?>">
            <select name="filter" class="form-select me-2">
                <option value="">All Status</option>
                <option value="Pending" <?= $filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Completed" <?= $filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Failed" <?= $filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Bookings Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Seats</th>
                    <th>Payment Status</th>
                    <th>Booking Time</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($bookingsResult->num_rows > 0): ?>
                    <?php while ($booking = $bookingsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['id']) ?></td>
                            <td><?= htmlspecialchars($booking['user_name']) ?></td>
                            <td><?= htmlspecialchars($booking['user_email']) ?></td>
                            <td><?= htmlspecialchars($booking['source']) ?></td>
                            <td><?= htmlspecialchars($booking['destination']) ?></td>
                            <td><?= htmlspecialchars($booking['seats_booked']) ?></td>
                            <td><?= htmlspecialchars($booking['payment_status']) ?></td>
                            <td><?= htmlspecialchars($booking['created_at']) ?></td>
                            <td>
                                <?php if ($booking['payment_status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>No Action</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No bookings found</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= ceil($totalBookings / $limit); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</body>

</html>