<?php
// Start session and include config file
session_start();
include("../includes/config.php");

// Pagination setup
$limit = 10; // Number of users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $limit;

// Search filter (by name, email, or phone)
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch users based on search and pagination
$query = "SELECT id, name, email, phone FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? LIMIT ?, ?";
$stmt = $conn->prepare($query);
$search_term = '%' . $search . '%';
$stmt->bind_param("sssii", $search_term, $search_term, $search_term, $start_from, $limit);
$stmt->execute();
$users = $stmt->get_result();

// Fetch total number of users (for pagination)
$count_query = "SELECT COUNT(*) AS total FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_users = $count_result['total'];
$total_pages = ceil($total_users / $limit);

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Delete user from the database
    $delete_query = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "User deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete the user.";
    }
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .dashboard-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .dashboard-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px;
        }

        .dashboard-card h1 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .table th,
        .table td {
            text-align: center;
        }

        .pagination {
            justify-content: center;
        }
    </style>
</head>

<body>
    <?php include("../includes/sidebar.php"); ?>
    <div class="container my-5">
        <!-- Success or Error message display -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <h1>Manage Users</h1>
                <form action="manage_users.php" method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone" value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td>
                                    <a href="manage_users.php?delete_id=<?= $user['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-info">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="manage_users.php?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</body>

</html>