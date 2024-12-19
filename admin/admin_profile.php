<?php
session_start();
include('../includes/config.php');

// Fetch current admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name, email FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Display session messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            font-size: 1rem;
            height: auto;
            padding: 10px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-primary {
            width: 100%;
        }

        .change-password-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
        }

        .btn-secondary {
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="profile-container">
            <h2 class="form-header">Admin Profile</h2>

            <form action="update_profile.php" method="POST">
                <input type="hidden" name="admin_id" value="<?= $admin_id ?>">

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>

                <!-- Change Password Section -->
                <div class="change-password-section">
                    <h5>Change Password</h5>
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Current Password</label>
                        <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Enter current password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Update Profile</button>
                <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Go to Dashboard</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>