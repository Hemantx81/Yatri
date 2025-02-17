<?php
session_start();
include_once '../includes/config.php';

// Initialize error and success message variables
$error_message = "";
$success_message = "";

// Fetch buses for dropdown
$buses_query = "SELECT DISTINCT id, bus_name FROM buses";
$buses_result = $conn->query($buses_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = trim($_POST['bus_id']);
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $departure_time = trim($_POST['departure_time']);
    $arrival_time = trim($_POST['arrival_time']);
    $price = trim($_POST['price']);

    // Validate input
    $current_date = date('Y-m-d\TH:i');
    if (
        empty($bus_id) || empty($source) || empty($destination) ||
        empty($departure_time) || empty($arrival_time) || empty($price)
    ) {
        $error_message = "All fields are required.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Please enter a valid price.";
    } elseif (strtotime($departure_time) < strtotime($current_date)) {
        $error_message = "Departure time must be greater than or equal to today's date.";
    } elseif (strtotime($arrival_time) <= strtotime($departure_time)) {
        $error_message = "Arrival time must be greater than departure time.";
    } elseif (strtotime($arrival_time) < strtotime($current_date)) {
        $error_message = "Arrival time must be greater than today's date.";
    } else {
        // Insert route into database
        $query = "INSERT INTO routes (bus_id, source, destination, departure_time, arrival_time, price) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssd", $bus_id, $source, $destination, $departure_time, $arrival_time, $price);

        if ($stmt->execute()) {
            // Get the last inserted route_id (this is the generated ID for the new route)
            $route_id = $stmt->insert_id;

            // Store success message in session
            $_SESSION['success_message'] = "Route added successfully!";

            // Redirect to avoid form resubmission and call generate_seats.php
            $generate_seats_url = "generate_seats.php?bus_id=$bus_id&route_id=$route_id";
            header("Location: $generate_seats_url");
            exit;
        } else {
            $error_message = "Failed to add route. Please try again.";
        }
    }
}

// Display success message from session if available
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear message after displaying
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Route</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: bold;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .form-control:focus {
            box-shadow: 0px 0px 5px #0d6efd;
            border-color: #0d6efd;
        }

        .alert {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center text-primary mb-4">Add New Route</h1>

        <!-- Error and success messages -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="add_route.php">
            <div class="mb-3">
                <label for="bus_id" class="form-label">Select Bus</label>
                <select id="bus_id" name="bus_id" class="form-control" required>
                    <option value="" selected disabled>Choose a bus</option>
                    <?php while ($bus = $buses_result->fetch_assoc()): ?>
                        <option value="<?php echo $bus['id']; ?>">
                            <?php echo $bus['bus_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="source" class="form-label">Source</label>
                <input type="text" id="source" name="source" class="form-control" placeholder="e.g., Kathmandu" required>
            </div>
            <div class="mb-3">
                <label for="destination" class="form-label">Destination</label>
                <input type="text" id="destination" name="destination" class="form-control" placeholder="e.g., Pokhara" required>
            </div>
            <div class="mb-3">
                <label for="departure_time" class="form-label">Departure Date & Time</label>
                <input type="datetime-local" id="departure_time" name="departure_time" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="arrival_time" class="form-label">Arrival Date & Time</label>
                <input type="datetime-local" id="arrival_time" name="arrival_time" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="duration" class="form-label">Duration (Hours)</label>
                <input type="text" id="duration" name="duration" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Ticket Price</label>
                <input type="number" id="price" name="price" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Route</button>
        </form>

        <!-- Button to manage routes -->
        <div class="text-center mt-3">
            <a href="manage_routes.php" class="btn btn-outline-secondary">Back to Manage Routes</a>
        </div>
    </div>

    <script>
        const departureInput = document.getElementById('departure_time');
        const arrivalInput = document.getElementById('arrival_time');
        const durationInput = document.getElementById('duration');

        const calculateDuration = () => {
            const departure = new Date(departureInput.value);
            const arrival = new Date(arrivalInput.value);

            if (arrival > departure) {
                const diffInMs = arrival - departure;
                const diffInHours = diffInMs / (1000 * 60 * 60);
                durationInput.value = diffInHours.toFixed(2);
            } else {
                durationInput.value = '';
            }
        };

        departureInput.addEventListener('change', calculateDuration);
        arrivalInput.addEventListener('change', calculateDuration);
    </script>
</body>

</html>