<?php
session_start();
include('../includes/config.php'); // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_name = trim($_POST['bus_name']);
    $total_seats = intval($_POST['total_seats']);
    $is_ac = isset($_POST['is_ac']) ? 1 : 0; // Checkbox for AC availability
    $is_wifi = isset($_POST['is_wifi']) ? 1 : 0; // Checkbox for Wi-Fi availability
    $image_path = "assets/images/default_bus.jpg"; // Default image path

    // Handle file upload
    if (isset($_FILES['bus_image']) && $_FILES['bus_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Ensure the directory exists
        }

        $file_tmp = $_FILES['bus_image']['tmp_name'];
        $file_name = basename($_FILES['bus_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_extensions)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        } else {
            $new_file_name = uniqid('bus_', true) . "." . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_path = 'assets/images/' . $new_file_name;
            } else {
                error_log("Failed to upload file: " . $file_tmp . " to " . $upload_path);
                $error = "Failed to upload the image. Try again.";
            }
        }
    }

    if (!isset($error)) {
        if (empty($bus_name) || $total_seats <= 0) {
            $error = "Please provide valid bus details.";
        } else {
            // Insert bus data into the buses table
            $query = $conn->prepare("INSERT INTO buses (bus_name, image_path, total_seats, is_ac, is_wifi) VALUES (?, ?, ?, ?, ?)");
            $query->bind_param("ssiii", $bus_name, $image_path, $total_seats, $is_ac, $is_wifi);

            if ($query->execute()) {
                $bus_id = $conn->insert_id; // Get the inserted bus ID

                // Insert seat availability for each seat
                for ($seat_number = 1; $seat_number <= $total_seats; $seat_number++) {
                    $status = 'available'; // Default status is available
                    $seat_query = $conn->prepare("INSERT INTO seat_availability (bus_id, seat_number, status) VALUES (?, ?, ?)");
                    $seat_query->bind_param("iis", $bus_id, $seat_number, $status);
                    $seat_query->execute();
                }
                $success = "Bus added successfully with seat availability!";
            } else {
                $error = "Failed to add the bus. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
            background-color: #fff;
        }

        .form-control {
            border-radius: 5px;
        }

        .form-check-label {
            margin-left: 5px;
        }

        .btn {
            width: 100%;
            margin-top: 10px;
            border-radius: 5px;
        }

        .form-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="card p-4">
        <h2 class="form-title text-center">Add New Bus</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="bus_name" class="form-label">Bus Name:</label>
                <input type="text" name="bus_name" class="form-control" placeholder="Enter bus name" required>
            </div>
            <div class="form-group mb-3">
                <label for="total_seats" class="form-label">Total Seats:</label>
                <input type="number" name="total_seats" class="form-control" placeholder="Enter total seats" required>
            </div>
            <div class="form-group mb-3">
                <label for="bus_image" class="form-label">Bus Image:</label>
                <input type="file" name="bus_image" class="form-control" accept="image/*">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="is_ac" class="form-check-input" id="is_ac">
                <label class="form-check-label" for="is_ac">AC Available</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="is_wifi" class="form-check-input" id="is_wifi">
                <label class="form-check-label" for="is_wifi">Wi-Fi Available</label>
            </div>
            <button type="submit" class="btn btn-primary">Add Bus</button>
            <a href="view_buses.php" class="btn btn-secondary">View Buses</a>
        </form>
    </div>
</body>

</html>