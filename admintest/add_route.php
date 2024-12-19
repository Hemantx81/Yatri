<?php
// admin/add_route.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../includes/config.php");

$source = $destination = $departure_time = $arrival_time = $price = "";
$bus_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $departure_time = trim($_POST['departure_time']);
    $arrival_time = trim($_POST['arrival_time']);
    $price = trim($_POST['price']);
    $bus_id = $_POST['bus_id'];

    $error = "";

    if (empty($source) || empty($destination) || empty($departure_time) || empty($arrival_time) || empty($price)) {
        $error = "All fields are required.";
    }

    if (empty($error)) {
        $query = "INSERT INTO routes (bus_id, source, destination, departure_time, arrival_time, price) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issdsi", $bus_id, $source, $destination, $departure_time, $arrival_time, $price);
        $stmt->execute();
        header("Location: manage_routes.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Route</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="my-4">Add New Route</h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="add_route.php">
            <div class="form-group">
                <label for="bus_id">Bus</label>
                <select name="bus_id" id="bus_id" class="form-control">
                    <?php
                    $buses = $conn->query("SELECT * FROM buses");
                    while ($bus = $buses->fetch_assoc()):
                    ?>
                        <option value="<?php echo $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="source">Source</label>
                <input type="text" name="source" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="destination">Destination</label>
                <input type="text" name="destination" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="departure_time">Departure Time</label>
                <input type="datetime-local" name="departure_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="arrival_time">Arrival Time</label>
                <input type="datetime-local" name="arrival_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" name="price" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Route</button>
        </form>
    </div>
</body>

</html>