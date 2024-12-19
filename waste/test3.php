<?php
session_start();
include('../includes/config.php'); // Include the database connection
include '../includes/header.php'; // Include the header

// Check if route_id is provided and valid
if (!isset($_GET['route_id']) || empty($_GET['route_id']) || !is_numeric($_GET['route_id'])) {
    die("Invalid route selection. Please go back and select a bus.");
}

$route_id = (int)$_GET['route_id']; // Sanitize route_id

// Fetch route and bus details
$query = $conn->prepare("
    SELECT r.id AS route_id, r.source, r.destination, r.departure_time, r.arrival_time, r.price, 
           b.bus_name, b.total_seats 
    FROM routes r
    JOIN buses b ON r.bus_id = b.id
    WHERE r.id = ?
");
$query->bind_param('i', $route_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("Route not found.");
}

$route = $result->fetch_assoc();

// Fetch seat availability
$seat_query = $conn->prepare("
    SELECT seat_number, is_booked 
    FROM seat_availability 
    WHERE route_id = ?
");
$seat_query->bind_param('i', $route_id);
$seat_query->execute();
$seat_result = $seat_query->get_result();

$seats = [];
while ($row = $seat_result->fetch_assoc()) {
    $seats[$row['seat_number']] = $row['is_booked'];
}

// Process booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
    $user_id = $_SESSION['user_id'] ?? null; // Assuming the user is logged in

    if (!$user_id) {
        $error = "You must be logged in to book tickets.";
    } elseif (empty($selected_seats)) {
        $error = "Please select at least one seat to book.";
    } elseif (count($selected_seats) > 5) {
        $error = "You can only select up to 5 seats.";
    } else {
        // Check if seats are still available
        $seat_placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $check_query = $conn->prepare("
            SELECT seat_number FROM seat_availability 
            WHERE route_id = ? AND seat_number IN ($seat_placeholders) AND is_booked = TRUE
        ");
        $params = array_merge([$route_id], $selected_seats);
        $check_query->bind_param(str_repeat('i', count($params)), ...$params);
        $check_query->execute();
        $conflict_result = $check_query->get_result();

        if ($conflict_result->num_rows > 0) {
            $conflict_seats = implode(', ', array_column($conflict_result->fetch_all(MYSQLI_ASSOC), 'seat_number'));
            $error = "Some of the selected seats are already booked: $conflict_seats";
        } else {
            // Book seats
            $booking_query = $conn->prepare("
                INSERT INTO bookings (user_id, route_id, seat_numbers, payment_status, booking_time) 
                VALUES (?, ?, ?, 'Pending', NOW())
            ");
            $seat_list = implode(',', $selected_seats);
            $booking_query->bind_param('iis', $user_id, $route_id, $seat_list);
            $booking_query->execute();

            // Update seat availability
            $update_query = $conn->prepare("
                UPDATE seat_availability SET is_booked = TRUE 
                WHERE route_id = ? AND seat_number IN ($seat_placeholders)
            ");
            $update_query->bind_param(str_repeat('i', count($params)), ...$params);
            $update_query->execute();

            $success = "Seats successfully booked! Please proceed to payment.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .seat {
            width: 40px;
            height: 40px;
            margin: 5px;
            display: inline-block;
            text-align: center;
            line-height: 40px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }

        .seat.available {
            background-color: #28a745;
            color: white;
        }

        .seat.booked {
            background-color: #dc3545;
            color: white;
            cursor: not-allowed;
        }

        .seat.selected {
            background-color: #ffc107;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Book Your Tickets</h2>
        <div class="mt-4">
            <h5>Route Details</h5>
            <p><strong>Bus:</strong> <?php echo htmlspecialchars($route['bus_name']); ?></p>
            <p><strong>Route:</strong> <?php echo htmlspecialchars($route['source'] . " to " . $route['destination']); ?></p>
            <p><strong>Departure:</strong> <?php echo htmlspecialchars($route['departure_time']); ?></p>
            <p><strong>Arrival:</strong> <?php echo htmlspecialchars($route['arrival_time']); ?></p>
            <p><strong>Price per Seat:</strong> $<?php echo htmlspecialchars($route['price']); ?></p>
        </div>

        <form method="POST" id="seatForm">
            <h5>Select Seats</h5>
            <div class="seat-map">
                <?php
                for ($i = 1; $i <= $route['total_seats']; $i++) {
                    $seat_class = isset($seats[$i]) && $seats[$i] ? 'seat booked' : 'seat available';
                    echo "<div class='$seat_class' data-seat='$i'>$i</div>";
                }
                ?>
            </div>
            <input type="hidden" name="selected_seats" id="selectedSeats">
            <button type="submit" class="btn btn-primary mt-3">Confirm Booking</button>
        </form>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger mt-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success mt-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
    </div>

    <script>
        const maxSeats = 5;
        document.querySelectorAll('.seat.available').forEach(seat => {
            seat.addEventListener('click', () => {
                const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'));
                if (selectedSeats.length < maxSeats || seat.classList.contains('selected')) {
                    seat.classList.toggle('selected');
                    const updatedSelectedSeats = Array.from(document.querySelectorAll('.seat.selected'))
                        .map(s => s.getAttribute('data-seat'));
                    document.getElementById('selectedSeats').value = updatedSelectedSeats.join(',');
                } else {
                    alert(`You can only select up to ${maxSeats} seats.`);
                }
            });
        });
    </script>
</body>

</html>