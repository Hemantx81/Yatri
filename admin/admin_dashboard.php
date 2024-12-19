<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Check if the user is logged in and is an admin
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// Fetch statistics
$total_buses = $conn->query("SELECT COUNT(*) AS total FROM buses")->fetch_assoc()['total'];
$total_routes = $conn->query("SELECT COUNT(*) AS total FROM routes")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_feedback = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(payment_amount) AS total FROM payments WHERE payment_status = 'Completed'")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE payment_status = 'Pending'")->fetch_assoc()['total'];
$completed_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f4f4f9;
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            width: 250px;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease-in-out;
            background-color: #ffffff;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .card h5 {
            font-weight: bold;
        }

        .card p {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .card .card-body {
            padding: 1.5rem;
        }

        .card-body a {
            text-decoration: none;
            color: inherit;
        }

        .bg-primary {
            background-color: #007bff !important;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-dark {
            background-color: #343a40 !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include("../includes/sidebar.php"); ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="mb-4">Admin Dashboard</h1>
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="view_buses.php" class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-bus"></i>
                        <h5>Total Buses</h5>
                        <p><?= $total_buses ?></p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="manage_routes.php" class="card text-white bg-success">
                    <div class="card-body text-center">
                        <i class="fas fa-route"></i>
                        <h5>Total Routes</h5>
                        <p><?= $total_routes ?></p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="manage_users.php" class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-users"></i>
                        <h5>Total Users</h5>
                        <p><?= $total_users ?></p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="view_feedback.php" class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-comment-dots"></i>
                        <h5>Feedback</h5>
                        <p><?= $total_feedback ?></p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign"></i>
                        <h5>Total Revenue</h5>
                        <p>NPR <?= number_format($total_revenue, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <a href="pending_payments.php" class="card text-white bg-info">
                    <div class="card-body text-center">
                        <i class="fas fa-clock"></i>
                        <h5>Pending Payments</h5>
                        <p><?= $pending_payments ?></p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="manage_bookings.php" class="card text-white bg-secondary">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle"></i>
                        <h5>Completed Bookings</h5>
                        <p><?= $completed_bookings ?></p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>

</html>