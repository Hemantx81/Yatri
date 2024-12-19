<?php
// Start session
session_start();
include("includes/config.php");

$verification_code = $_GET['code'] ?? '';
$error = "";
$success = "";

// Check if verification code is provided
if (empty($verification_code)) {
    $error = "Invalid verification link.";
} else {
    // Find the user with the given verification code
    $query = "SELECT * FROM users WHERE verification_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // User found, update the 'verified' status to true
        $update_query = "UPDATE users SET verified = ?, verification_code = NULL WHERE verification_code = ?";
        $stmt = $conn->prepare($update_query);
        $verified = true;
        $stmt->bind_param("is", $verified, $verification_code);
        if ($stmt->execute()) {
            $success = "Your email has been verified successfully. You can now login.";
        } else {
            $error = "An error occurred while verifying your email. Please try again later.";
        }
    } else {
        $error = "Invalid verification link or user already verified.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email Verification - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?>">
            <strong><?php echo $error ? 'Error: ' : 'Success: '; ?></strong>
            <?php echo $error ? $error : $success; ?>
        </div>
        <div class="text-center">
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
</body>

</html>