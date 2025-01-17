<?php
session_start();
include("includes/config.php");

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch user booking history with pagination
$limit = 5; // Number of bookings per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$booking_query = "SELECT b.id, r.source, r.destination, r.departure_time, b.seats_booked, b.payment_status, b.created_at, b.route_id, bs.bus_name 
                  FROM bookings b
                  JOIN routes r ON b.route_id = r.id
                  JOIN buses bs ON r.bus_id = bs.id
                  WHERE b.user_id = ?
                  ORDER BY b.created_at DESC
                  LIMIT ? OFFSET ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$bookings = $stmt->get_result();

// Get total bookings count for pagination
$count_query = "SELECT COUNT(*) AS total_bookings FROM bookings WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_bookings = $count_stmt->get_result()->fetch_assoc()['total_bookings'];
$total_pages = ceil($total_bookings / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>

        <!-- Buttons Section -->
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Go to Home</a>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">Update Profile</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <!-- Booking History -->
        <h2 class="mt-5">Booking History</h2>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure Time</th>
                    <th>Seats Booked</th>
                    <th>Payment Status</th>
                    <th>Bus Name</th>
                    <th>User Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings->num_rows > 0): ?>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr id="booking-row-<?= $booking['id'] ?>">
                            <td><?= htmlspecialchars($booking['id']) ?></td>
                            <td><?= htmlspecialchars($booking['source']) ?></td>
                            <td><?= htmlspecialchars($booking['destination']) ?></td>
                            <td><?= htmlspecialchars($booking['departure_time']) ?></td>
                            <td><?= htmlspecialchars($booking['seats_booked']) ?></td>
                            <td><?= htmlspecialchars($booking['payment_status']) ?></td>
                            <td><?= htmlspecialchars($booking['bus_name']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <?php
                                $booking_time = strtotime($booking['created_at']);
                                $current_time = time();
                                $time_difference = $current_time - $booking_time;

                                if ($booking['payment_status'] === 'Pending' && $time_difference <= 7200): // Less than 2 hours
                                ?>
                                    <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?= $booking['id'] ?>)">Cancel</button>
                                <?php elseif ($booking['payment_status'] === 'Completed'): ?>
                                    <a href="download_ticket.php?booking_id=<?= $booking['id'] ?>" class="btn btn-success btn-sm">Download Ticket</a>
                                <?php else: ?>
                                    <span class="text-muted">No actions available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="update_profile.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Change Password">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelBooking(bookingId) {
            if (confirm("Are you sure you want to cancel this booking?")) {
                fetch(`cancel_booking.php?id=${bookingId}`, {
                        method: 'GET'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            location.reload(); // Refresh the page to reflect changes
                        } else {
                            alert("Failed to cancel booking: " + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>

</html>