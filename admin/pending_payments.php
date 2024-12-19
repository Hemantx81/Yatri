<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Variables for pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Handle search/filter
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$filter_method = isset($_GET['method']) ? $_GET['method'] : '';
$where_clauses = ["payments.payment_status = 'Pending'"];
$params = [];

// Add search filter
if (!empty($search_query)) {
    $where_clauses[] = "(users.name LIKE ? OR routes.source LIKE ? OR routes.destination LIKE ?)";
    $search_term = "%" . $search_query . "%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

// Add payment method filter
if (!empty($filter_method)) {
    $where_clauses[] = "payments.payment_method = ?";
    $params[] = $filter_method;
}

// Build the WHERE clause
$where_sql = implode(" AND ", $where_clauses);

// Fetch pending payments with filters
$stmt = $conn->prepare("
    SELECT 
        payments.id, 
        payments.payment_amount, 
        payments.payment_method, 
        users.name AS user_name, 
        routes.source, 
        routes.destination, 
        payments.created_at
    FROM payments
    JOIN bookings ON payments.booking_id = bookings.id
    JOIN users ON bookings.user_id = users.id
    JOIN routes ON bookings.route_id = routes.id
    WHERE $where_sql
    ORDER BY payments.created_at DESC
    LIMIT $offset, $items_per_page
");
if (!empty($params)) {
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$pending_payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count total pending payments for pagination
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM payments
    JOIN bookings ON payments.booking_id = bookings.id
    JOIN users ON bookings.user_id = users.id
    JOIN routes ON bookings.route_id = routes.id
    WHERE $where_sql
");
if (!empty($params)) {
    $count_stmt->bind_param(str_repeat("s", count($params)), ...$params);
}
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();
$total_pages = ceil($total_items / $items_per_page);

// Update payment status if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = intval($_POST['payment_id']);
    $new_status = $_POST['new_status'];

    // Update the payment status in the database
    $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $payment_id);
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Payment status updated successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to update payment status.";
    }
    $stmt->close();
    header("Location: pending_payments.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.9.1/font/bootstrap-icons.min.css">
</head>

<body>
    <div class="container my-4">
        <h1>Pending Payments</h1>
        <hr>

        <!-- Success or Error Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_msg'] ?></div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_msg'] ?></div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search (User, Source, Destination)" value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="col-md-3">
                <select name="method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="Esewa" <?= $filter_method === 'Esewa' ? 'selected' : '' ?>>Esewa</option>
                    <option value="Khalti" <?= $filter_method === 'Khalti' ? 'selected' : '' ?>>Khalti</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <!-- Table of Pending Payments -->
        <?php if (!empty($pending_payments)): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Route</th>
                        <th>Amount (NPR)</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_payments as $payment): ?>
                        <tr>
                            <td><?= $payment['id'] ?></td>
                            <td><?= htmlspecialchars($payment['user_name']) ?></td>
                            <td><?= htmlspecialchars($payment['source'] . " to " . $payment['destination']) ?></td>
                            <td><?= number_format($payment['payment_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($payment['method']) ?></td>
                            <td><?= htmlspecialchars($payment['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                    <input type="hidden" name="new_status" value="Completed">
                                    <button class="btn btn-success btn-sm" type="submit">
                                        <i class="bi bi-check-circle"></i> Complete
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                    <input type="hidden" name="new_status" value="Failed">
                                    <button class="btn btn-danger btn-sm" type="submit">
                                        <i class="bi bi-x-circle"></i> Fail
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search_query) ?>&method=<?= htmlspecialchars($filter_method) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info">No pending payments found.</div>
        <?php endif; ?>
    </div>
</body>

</html>