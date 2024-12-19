<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding: 20px;
            transition: left 0.3s ease;
            z-index: 1000;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            margin: 15px 0;
            display: block;
            padding: 12px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #495057;
            cursor: pointer;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .sidebar .nav-link i {
            margin-right: 15px;
        }

        .bar-icon {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 24px;
            background-color: #343a40;
            color: white;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1100;
        }

        .content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .content.sidebar-open {
            margin-left: 250px;
        }

        .sidebar .nav-link.active {
            background-color: #007bff;
        }
    </style>
</head>

<body>
    <!-- Bar Icon for Toggling Sidebar -->
    <div class="bar-icon" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="admin_dashboard.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <i class="fas fa-tachometer-alt me-2"></i>
            <span class="fs-4">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li>
                <a href="admin_dashboard.php" class="nav-link text-white">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="add_bus.php" class="nav-link text-white">
                    <i class="fas fa-bus me-2"></i> Manage Buses
                </a>
            </li>
            <li>
                <a href="manage_routes.php" class="nav-link text-white">
                    <i class="fas fa-route me-2"></i> Manage Routes
                </a>
            </li>
            <li>
                <a href="manage_users.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
            </li>
            <li>
                <a href="view_feedback.php" class="nav-link text-white">
                    <i class="fas fa-comments me-2"></i> Feedback
                </a>
            </li>
            <li>
                <a href="manage_bookings.php" class="nav-link text-white">
                    <i class="fas fa-check-circle me-2"></i> Manage Bookings
                </a>
            </li>
            <li>
                <a href="pending_payments.php" class="nav-link text-white">
                    <i class="fas fa-wallet me-2"></i> Pending Payments
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2"></i>
                <strong>Admin</strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="admin_profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content" id="content">
        <div class="container mt-5">
            <!-- Your page content here -->
        </div>
    </div>

    <script>
        const sidebar = document.getElementById("sidebar");
        const toggleSidebar = document.getElementById("toggleSidebar");
        const content = document.getElementById("content");

        toggleSidebar.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            content.classList.toggle("sidebar-open");
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>