<?php
// admin/manage_buses.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../includes/config.php");

$query = "SELECT * FROM buses";
$result = $conn->query($query);

if (isset($_GET['delete'])) {
    $bus_id = $_GET['delete'];
    $delete_query = "DELETE FROM buses WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    header("Location: manage_buses.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="my-4">Manage Buses</h1>
        <a href="add_bus.php" class="btn btn-success mb-3">Add New Bus</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Total Seats</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($bus = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                        <td><?php echo $bus['total_seats']; ?></td>
                        <td>
                            <a href="add_bus.php?edit=<?php echo $bus['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete=<?php echo $bus['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>