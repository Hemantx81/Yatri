<?php
// admin/add_bus.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../includes/config.php");

$bus_name = $total_seats = "";
$edit_mode = false;

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $bus_id = $_GET['edit'];
    $query = "SELECT * FROM buses WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bus = $result->fetch_assoc();
    $bus_name = $bus['bus_name'];
    $total_seats = $bus['total_seats'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_name = trim($_POST['bus_name']);
    $total_seats = trim($_POST['total_seats']);
    $error = "";

    if (empty($bus_name) || empty($total_seats)) {
        $error = "Bus Name and Total Seats are required.";
    }

    if (empty($error)) {
        if ($edit_mode) {
            $query = "UPDATE buses SET bus_name = ?, total_seats = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $bus_name, $total_seats, $bus_id);
        } else {
            $query = "INSERT INTO buses (bus_name, total_seats) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $bus_name, $total_seats);
        }
        $stmt->execute();
        header("Location: manage_buses.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Bus' : 'Add Bus'; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="my-4"><?php echo $edit_mode ? 'Edit Bus' : 'Add Bus'; ?></h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="add_bus.php<?php echo $edit_mode ? '?edit=' . $bus_id : ''; ?>">
            <div class="form-group">
                <label for="bus_name">Bus Name</label>
                <input type="text" name="bus_name" id="bus_name" class="form-control" value="<?php echo htmlspecialchars($bus_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="total_seats">Total Seats</label>
                <input type="number" name="total_seats" id="total_seats" class="form-control" value="<?php echo htmlspecialchars($total_seats); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Bus' : 'Add Bus'; ?></button>
        </form>
    </div>
</body>

</html>