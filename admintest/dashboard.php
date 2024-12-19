<?php
// admin/dashboard.php
session_start();

// Ensure only admins can access the admin panel
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../includes/config.php");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="my-4">Admin Dashboard</h1>
        <div class="row">
            <div class="col-md-4">
                <a href="manage_buses.php" class="btn btn-primary btn-block">Manage Buses</a>
            </div>
            <div class="col-md-4">
                <a href="add_route.php" class="btn btn-success btn-block">Add Route</a>
            </div>
            <div class="col-md-4">
                <a href="view_feedback.php" class="btn btn-warning btn-block">View Feedback</a>
            </div>
        </div>
    </div>
</body>

</html>