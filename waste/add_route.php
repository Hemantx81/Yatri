<?php
session_start();
include('../includes/config.php'); // Database connection
include('../includes/header.php'); // Header

// Fetch all buses for the dropdown
$buses_query = $conn->query("SELECT id, bus_name FROM buses ORDER BY bus_name ASC");
$buses = $buses_query->fetch_all(MYSQLI_ASSOC);

$error = '';  // Initialize error variable
$success = ''; // Initialize success variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = intval($_POST['bus_id']);
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $departure_time = trim($_POST['departure_time']);
    $arrival_time = trim($_POST['arrival_time']);
    $price = floatval($_POST['price']);

    // Server-side validation: Check if arrival time is later than departure time
    if (empty($bus_id) || empty($source) || empty($destination) || empty($departure_time) || empty($arrival_time) || $price <= 0) {
        $error = "Please provide valid route details.";
    } elseif (strtotime($departure_time) < time()) { // Departure time should not be in the past
        $error = "Departure time cannot be in the past.";
    } elseif (strtotime($arrival_time) <= strtotime($departure_time)) { // Arrival time should be before departure time
        $error = "Arrival time must be before or equal to the departure time.";
    } else {
        // If validation passes, insert data into the database
        $query = $conn->prepare("INSERT INTO routes (bus_id, source, destination, departure_time, arrival_time, price) VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("issssd", $bus_id, $source, $destination, $departure_time, $arrival_time, $price);

        if ($query->execute()) {
            $success = "Route added successfully!";
        } else {
            $error = "Failed to add the route. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Add Route</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Client-side validation to check if arrival time is greater than departure time and if departure time is in the past
        function validateTimes() {
            const departureTime = document.getElementById('departure_time').value;
            const arrivalTime = document.getElementById('arrival_time').value;
            const currentTime = new Date().toISOString().slice(0, 16); // Get current time in the same format as datetime-local

            // Check if departure time is in the past
            if (departureTime < currentTime) {
                alert("Departure time cannot be in the past.");
                return false; // Prevent form submission
            }

            // Check if arrival time is greater than departure time
            if (new Date(arrivalTime) > new Date(departureTime)) {
                alert("Arrival time must be before or equal to the departure time.");
                return false; // Prevent form submission
            }

            return true; // Allow form submission
        }
    </script>
</head>

<body>
    <div class="container">
        <h2>Add New Route</h2>

        <!-- Display success or error messages -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateTimes()"> <!-- Trigger JavaScript validation -->
            <div class="form-group">
                <label for="bus_id">Select Bus:</label>
                <select name="bus_id" class="form-control" required>
                    <option value="">-- Select Bus --</option>
                    <?php foreach ($buses as $bus): ?>
                        <option value="<?php echo $bus['id']; ?>"><?php echo $bus['bus_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="source">Source:</label>
                <input type="text" name="source" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="destination">Destination:</label>
                <input type="text" name="destination" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="departure_time">Departure Time:</label>
                <input type="datetime-local" name="departure_time" id="departure_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="arrival_time">Arrival Time:</label>
                <input type="datetime-local" name="arrival_time" id="arrival_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" name="price" step="0.01" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Add Route</button>
        </form>
    </div>
</body>

</html>