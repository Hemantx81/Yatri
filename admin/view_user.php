<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Check if the user is logged in and is an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: login.php"); // Redirect to login if not logged in or not an admin
//     exit();
// }

// Check if the user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: manage_users.php");
    exit();
}

// Fetch user details from the database
$user_id = $_GET['id'];
$query = "SELECT id, name, email,  created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if the user exists
if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: manage_users.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>View User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .dashboard-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .dashboard-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px;
        }

        .dashboard-card h1 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .user-details {
            margin-bottom: 30px;
        }

        .user-details p {
            font-size: 1.1rem;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <!-- Success or Error message display -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <h1>User Details</h1>
                <div class="user-details">
                    <p><strong>ID:</strong> <?= htmlspecialchars($user['id']) ?></p>
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <!-- <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p> -->
                    <p><strong>Account Created At:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
                </div>

                <!-- Back to Manage Users -->
                <a href="manage_users.php" class="btn btn-secondary">Back to Manage Users</a>
            </div>
        </div>
    </div>
</body>

</html>