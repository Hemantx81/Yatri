<?php
session_start();

// Destroy session variables and logout the admin
session_unset(); // Remove session variables
session_destroy(); // Destroy the session

// Redirect to the login page
header('Location: admin_login.php');
exit();
