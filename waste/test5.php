<?php
// Start session
session_start();
include("includes/config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Validate form inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if the email is already registered
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = bin2hex(random_bytes(16)); // Generate verification code
            $verified = false;

            // Insert user into database
            $insert_query = "INSERT INTO users (name, email, password, verification_code, verified) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssssi", $name, $email, $hashed_password, $verification_code, $verified);

            if ($stmt->execute()) {
                // Send verification email
                $verification_link = "http://yourdomain.com/verify.php?code=$verification_code";
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.yourmailserver.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your-email@example.com';
                    $mail->Password = 'your-email-password';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('your-email@example.com', 'YATRI');
                    $mail->addAddress($email);

                    $mail->Subject = 'Verify Your Email';
                    $mail->Body = "Hi $name,\n\nClick the link below to verify your email:\n$verification_link\n\nThank you,\nYATRI Team";

                    $mail->send();
                    $success = "Registration successful! Please check your email to verify your account.";
                } catch (Exception $e) {
                    $error = "Error sending verification email. Please try again.";
                }
            } else {
                $error = "Failed to register. Please try again.";
            }
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
    <title>Register - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .register-form {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .register-form h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .register-form .form-group {
            margin-bottom: 15px;
            position: relative;
        }

        .register-form .form-group input {
            padding-right: 35px;
        }

        .register-form .form-group .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .register-form .btn {
            width: 100%;
            padding: 10px;
        }

        .error {
            color: red;
            font-size: 0.9rem;
        }

        .success {
            color: green;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <form method="POST" action="register.php" class="register-form">
            <h1>Register</h1>
            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <div class="form-group">
                <input type="text" name="name" id="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=]).{8,}"
                    title="Password must be at least 8 characters long, with uppercase, lowercase, a number, and a special character."
                    required>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <p class="text-center mt-3">
                Already have an account? <a href="login.php">Login</a>
            </p>
        </form>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>