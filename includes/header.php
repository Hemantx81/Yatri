<?php
// Start session to access user login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current script name to determine the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YATRI - Online Bus Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Global Header Styling */
        .navbar {
            background-color: #004d99;
            color: white;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: bold;
            color: white;
        }

        .navbar-brand:hover {
            color: #cce7ff;
            text-decoration: none;
        }

        .navbar-nav .nav-link {
            color: white;
            font-size: 0.9rem;
            padding: 6px 12px;
            transition: color 0.3s ease, background-color 0.3s ease, border 0.3s ease;
            border-radius: 15px;
            border: 2px solid transparent;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white;
            background-color: #007bff;
            border-color: #007bff;
        }

        /* Reduced space for the active link */
        .navbar-nav .nav-link.active {
            border-width: 1px;
            padding: 4px 10px;
        }

        .btn-login {
            background-color: #28a745;
            color: white;
            border-radius: 15px;
            padding: 4px 12px;
            font-size: 0.85rem;
            border: none;
            margin-left: 20px;
            /* Added margin to create space */
        }

        .btn-login:hover {
            background-color: #218838;
            color: white;
        }

        .btn-admin {
            background-color: #ff6600;
            color: white;
            border-radius: 15px;
            padding: 4px 12px;
            font-size: 0.85rem;
            border: none;
            margin-right: 10px;
        }

        .btn-admin:hover {
            background-color: #e65c00;
            color: white;
        }

        .nav-container {
            margin-top: 68px;
        }

        .navbar-nav.ml-auto {
            margin-left: auto;
            /* This will push the login button to the far right */
        }

        /* Improved Responsiveness for Navbar */
        @media (max-width: 992px) {
            .navbar-brand {
                font-size: 1.4rem;
            }

            .navbar-nav .nav-link {
                font-size: 1rem;
                padding: 6px 12px;
            }

            .btn-admin,
            .btn-login {
                font-size: 0.8rem;
                padding: 4px 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="btn-admin" href="admin/admin_login.php">Admin</a>
            <a class="navbar-brand ms-2" href="index.php">YATRI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'search.php' ? 'active' : '' ?>" href="search.php">Search</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'user_dashboard.php' ? 'active' : '' ?>" href="user_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item ml-auto">
                            <a class="nav-link btn-login" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ml-auto">
                            <a class="nav-link btn-login" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="nav-container"></div>


</body>

</html>