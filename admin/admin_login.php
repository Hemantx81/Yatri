<?php
session_start();
include('../includes/config.php'); // Database connection

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Query to check if the admin user exists
    $query = $conn->prepare("SELECT id, password FROM admins WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // Verify password using password_verify()
        if (password_verify($password, $admin['password'])) {
            // Store session and redirect to the admin panel
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: admin_dashboard.php'); // Redirect to admin dashboard
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Admin not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
        }

        .login-container {
            max-width: 450px;
            margin: 100px auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            height: auto;
            padding: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .register-btn {
            margin-top: 10px;
            display: flex;
            justify-content: center;
        }

        .register-btn a {
            text-decoration: none;
            color: #007bff;
        }

        .register-btn a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center text-primary">Admin Login</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group mt-3">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group mt-3">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-4">Login</button>
            </form>

            <!-- Register as Admin -->
            <div class="register-btn">
                <p class="text-center mt-3">Don’t have an account?
                    <a href="admin_register.php">Register as Admin</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>