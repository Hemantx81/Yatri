<?php
// admin/view_feedback.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../includes/config.php");

// Fetch feedback data from the database
$query = "SELECT f.id, f.comment, f.rating, f.created_at, u.name AS user_name, b.bus_name 
          FROM feedback f
          JOIN users u ON f.user_id = u.id
          JOIN buses b ON f.bus_id = b.id
          ORDER BY f.created_at DESC";
$result = $conn->query($query);

// Delete feedback functionality
if (isset($_GET['delete'])) {
    $feedback_id = $_GET['delete'];
    $delete_query = "DELETE FROM feedback WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    header("Location: view_feedback.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="my-4">User Feedback</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Bus</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Feedback Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($feedback = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($feedback['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['bus_name']); ?></td>
                        <td><?php echo $feedback['rating']; ?>/5</td>
                        <td><?php echo htmlspecialchars($feedback['comment']); ?></td>
                        <td><?php echo $feedback['created_at']; ?></td>
                        <td>
                            <a href="?delete=<?php echo $feedback['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this feedback?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>