<?php
// Start session
session_start();

// Include necessary files
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
    $whereClause .= " AND (users.name LIKE '%$search%' OR routes.source LIKE '%$search%' OR routes.destination LIKE '%$search%')";
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

// Fetch bookings with pagination and sorting
$bookingsQuery = "SELECT bookings.id, users.name AS user_name, routes.source, routes.destination, bookings.seats_booked, 
                         bookings.payment_status, bookings.created_at 
                  FROM bookings 
                  INNER JOIN users ON bookings.user_id = users.id 
                  INNER JOIN routes ON bookings.route_id = routes.id
                  WHERE $whereClause
                  ORDER BY bookings.created_at DESC
                  LIMIT $limit OFFSET $offset";
$bookingsResult = $conn->query($bookingsQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h1>Manage Bookings</h1>
        <form method="GET" class="d-flex mb-3">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by user, source, or destination" value="<?= htmlspecialchars($search) ?>">
            <select name="filter" class="form-select me-2">
                <option value="">All Status</option>
                <option value="Pending" <?= $filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Completed" <?= $filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Failed" <?= $filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
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
                            <td><?= htmlspecialchars($booking['source']) ?></td>
                            <td><?= htmlspecialchars($booking['destination']) ?></td>
                            <td><?= htmlspecialchars($booking['seats_booked']) ?></td>
                            <td><?= htmlspecialchars($booking['payment_status']) ?></td>
                            <td><?= htmlspecialchars($booking['created_at']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal"
                                    data-id="<?= $booking['id'] ?>"
                                    data-status="<?= $booking['payment_status'] ?>">Update</button>
                                <button class="btn btn-sm btn-danger delete-booking"
                                    data-id="<?= $booking['id'] ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No bookings found</td>
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

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="updateForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="bookingId">
                        <div class="mb-3">
                            <label for="paymentStatus" class="form-label">Payment Status</label>
                            <select name="payment_status" id="paymentStatus" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Populate the modal with data
        $('#updateModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const bookingId = button.data('id');
            const paymentStatus = button.data('status');

            $('#bookingId').val(bookingId);
            $('#paymentStatus').val(paymentStatus);
        });

        // Handle update form submission
        $('#updateForm').on('submit', function(event) {
            event.preventDefault();
            const formData = $(this).serialize();

            $.post('update_booking.php', formData, function(response) {
                alert(response.message);
                location.reload();
            }, 'json');
        });

        // Handle delete button click
        $('.delete-booking').on('click', function() {
            const bookingId = $(this).data('id');

            if (confirm('Are you sure you want to delete this booking?')) {
                $.post('delete_booking.php', {
                    booking_id: bookingId
                }, function(response) {
                    alert(response.message);
                    location.reload();
                }, 'json');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>