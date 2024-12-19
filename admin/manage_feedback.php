<?php
// Include required files and start session
session_start();
require_once '../includes/config.php';
// require_once '../includes/functions.php';

// Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header('Location: ../login.php');
//     exit();
// }

// Pagination configuration
$feedback_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $feedback_per_page;

// Search and filter logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Base query
$query = "SELECT feedback.id, users.name AS user_name, buses.bus_name, feedback.rating, feedback.comment, feedback.created_at
          FROM feedback
          JOIN users ON feedback.user_id = users.id
          JOIN buses ON feedback.bus_id = buses.id";

// Apply search and filter conditions
$conditions = [];
$params = [];
if ($search) {
    $conditions[] = "(users.name LIKE ? OR buses.bus_name LIKE ? OR feedback.comment LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}
if ($filter_rating > 0) {
    $conditions[] = "feedback.rating = ?";
    $params[] = $filter_rating;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Add sorting and pagination
$query .= " ORDER BY feedback.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $feedback_per_page;

// Prepare and execute the query
$stmt = $conn->prepare($query);
if ($stmt) {
    $types = str_repeat('s', count($params) - 2) . 'ii'; // Define parameter types
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $feedback = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// Count total feedback for pagination
$count_query = "SELECT COUNT(*) FROM feedback JOIN users ON feedback.user_id = users.id JOIN buses ON feedback.bus_id = buses.id";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $conditions);
}
$count_stmt = $conn->prepare($count_query);
if ($count_stmt) {
    $types = str_repeat('s', count($params) - 2); // Exclude LIMIT params
    $count_params = array_slice($params, 0, -2);
    if (!empty($count_params)) {
        $count_stmt->bind_param($types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_feedback = $count_result->fetch_row()[0];
    $total_pages = ceil($total_feedback / $feedback_per_page);
    $count_stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $delete_query = "DELETE FROM feedback WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    if ($delete_stmt) {
        $delete_stmt->bind_param('i', $delete_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        header("Location: manage_feedback.php?success=Feedback deleted successfully");
        exit();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        <h2 class="mb-4">Manage Feedback</h2>

        <!-- Search and Filter Form -->
        <form class="row g-3 mb-4" method="GET" action="manage_feedback.php">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="rating">
                    <option value="0">Filter by Rating</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $filter_rating == $i ? 'selected' : '' ?>><?= $i ?> Star</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="manage_feedback.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Feedback Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User Name</th>
                    <th>Bus Name</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($feedback)): ?>
                    <?php foreach ($feedback as $fb): ?>
                        <tr>
                            <td><?= htmlspecialchars($fb['user_name']) ?></td>
                            <td><?= htmlspecialchars($fb['bus_name']) ?></td>
                            <td><?= $fb['rating'] ?> Star</td>
                            <td><?= htmlspecialchars($fb['comment']) ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($fb['created_at'])) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="delete_id" value="<?= $fb['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No feedback found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rating=<?= $filter_rating ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>