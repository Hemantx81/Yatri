<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
// Disable caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch statistics
$total_buses = $conn->query("SELECT COUNT(*) AS total FROM buses")->fetch_assoc()['total'];
$total_routes = $conn->query("SELECT COUNT(*) AS total FROM routes")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_feedback = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(payment_amount) AS total FROM payments WHERE payment_status = 'Completed'")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) AS total FROM payments WHERE payment_status = 'Pending'")->fetch_assoc()['total'];
$completed_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];

// Fetch buses ready to depart
$buses_ready_to_depart_query = "
    SELECT 
        b.bus_name, r.source, r.destination, r.departure_time 
    FROM 
        buses b 
    JOIN 
        routes r ON b.id = r.bus_id 
    WHERE 
        r.departure_time >= NOW() AND r.departure_time <= DATE_ADD(NOW(), INTERVAL 2 HOUR)
";
$buses_ready_to_depart_result = $conn->query($buses_ready_to_depart_query);
$buses_ready_to_depart = [];
if ($buses_ready_to_depart_result->num_rows > 0) {
    while ($row = $buses_ready_to_depart_result->fetch_assoc()) {
        $buses_ready_to_depart[] = $row;
    }
}
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
                <a href="manage_feedback.php" class="card text-white bg-danger">
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
            <div class="col-md-4 mb-3">
                <a href="buses_ready.php" class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Buses Ready to Depart</h5>
                        <?php if (count($buses_ready_to_depart) > 0): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($buses_ready_to_depart as $bus): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($bus['bus_name']) ?></strong>
                                        (<?= htmlspecialchars($bus['source']) ?> → <?= htmlspecialchars($bus['destination']) ?> at <?= htmlspecialchars($bus['departure_time']) ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No buses ready to depart within the next 2 hours.</p>
                        <?php endif; ?>
                    </div>
                </a>
            </div>


        </div>
    </div>
</body>

</html>