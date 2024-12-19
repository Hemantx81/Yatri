<?php
// Start the session
session_start();

// Destroy the session to log out the user
session_unset();
session_destroy();

// Redirect to the homepage or login page
header("Location: index.php");
exit();
