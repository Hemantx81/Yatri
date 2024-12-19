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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_POST['admin_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password if changing
    if (!empty($old_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = 'New passwords do not match.';
            header("Location: admin_profile.php");
            exit();
        }

        // Verify the old password
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if (!password_verify($old_password, $admin['password'])) {
            $_SESSION['error_message'] = 'Current password is incorrect.';
            header("Location: admin_profile.php");
            exit();
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = null; // No password change
    }

    // Update the admin details
    if ($hashed_password) {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $admin_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Profile updated successfully.';
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Error updating profile. Please try again later.';
        header("Location: admin_profile.php");
        exit();
    }
}
