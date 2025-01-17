<?php
// Start session
session_start();
include("includes/config.php"); // Include database configuration

$error = "";
$success = "";
$login_attempts_limit = 5; // Set a limit for failed login attempts
$lockout_time = 2 * 60; // Lockout time in seconds (15 minutes)

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Limit login attempts
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $login_attempts_limit) {
        if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
            $error = "Too many failed login attempts. Please try again after " . round(($lockout_time - (time() - $_SESSION['last_attempt_time'])) / 60) . " minutes.";
        } else {
            unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);
        }
    }

    if (!$error) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Reset login attempts
                unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                // Redirect to dashboard
                header("Location: booking/book_ticket.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }

        // Increment login attempts
        if ($error) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_attempt_time'] = time();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <div class="login-container">
        <form method="POST" action="login.php" class="login-form">
            <h1>Login</h1>
            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <div class="form-group">
                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
            <p class="mt-3 text-center">
                Don't have an account? <a href="register.php">Register</a>
            </p>
        </form>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>