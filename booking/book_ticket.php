<?php
session_start();
include('../includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to book tickets.'); window.location.href = '../login.php';</script>";
    exit();
}

$route_id = (int)$_GET['route_id']; // Sanitize route_id

// Fetch route and bus details
$query = $conn->prepare("SELECT r.id AS route_id, r.source, r.destination, r.departure_time, r.arrival_time, r.price, 
                               b.id AS bus_id, b.bus_name, b.total_seats, b.is_ac, b.is_wifi 
                          FROM routes r
                          JOIN buses b ON r.bus_id = b.id
                          WHERE r.id = ?");
$query->bind_param('i', $route_id);
$query->execute();
$route_result = $query->get_result();

if ($route_result->num_rows === 0) {
    die("<script>alert('Route not found. Please select a valid route.'); window.location.href = '../search.php';</script>");
}

$route = $route_result->fetch_assoc();
$bus_id = $route['bus_id'];  // Get the bus_id for this route

// Fetch seat availability based on route_id and bus_id
$seat_query = $conn->prepare("SELECT seat_number, status 
                              FROM seat_availability 
                              WHERE route_id = ? AND bus_id = ?");
$seat_query->bind_param('ii', $route_id, $bus_id);
$seat_query->execute();
$seat_result = $seat_query->get_result();

$seats = [];
while ($row = $seat_result->fetch_assoc()) {
    $seats[$row['seat_number']] = $row['status']; // Status can be 'available', 'reserved', or 'booked'
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
    $user_id = $_SESSION['user_id'];

    if (empty($selected_seats)) {
        echo "<script>alert('Please select at least one seat.');</script>";
    } elseif (count($selected_seats) > 5) {
        echo "<script>alert('You can select a maximum of 5 seats.');</script>";
    } else {
        // Validate selected seats
        $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $validation_query = $conn->prepare("SELECT seat_number, status 
                                            FROM seat_availability 
                                            WHERE route_id = ? AND bus_id = ? AND seat_number IN ($placeholders) AND status != 'available'");
        $params = array_merge([$route_id, $bus_id], $selected_seats);
        $validation_query->bind_param(str_repeat('i', count($params)), ...$params);
        $validation_query->execute();
        $conflict_result = $validation_query->get_result();

        if ($conflict_result->num_rows > 0) {
            $conflict_seats = implode(', ', array_column($conflict_result->fetch_all(MYSQLI_ASSOC), 'seat_number'));
            echo "<script>alert('The following seats are already booked or reserved: $conflict_seats');</script>";
        } else {
            // Reserve the seats and save booking
            $reserve_query = $conn->prepare("UPDATE seat_availability 
                                            SET status = 'reserved' 
                                            WHERE route_id = ? AND bus_id = ? AND seat_number IN ($placeholders)");
            $reserve_query->bind_param(str_repeat('i', count($params)), ...$params);
            $reserve_query->execute();

            // Calculate total amount
            $total_amount = count($selected_seats) * $route['price'];
            $seat_list = implode(',', $selected_seats);

            $booking_query = $conn->prepare("INSERT INTO bookings (user_id, route_id, seats_booked, booking_time, payment_status, total_amount) 
                                            VALUES (?, ?, ?, NOW(), 'Pending', ?)");
            $booking_query->bind_param('iisd', $user_id, $route_id, $seat_list, $total_amount);
            $booking_query->execute();

            echo "<script>alert('Booking successful! Redirecting to payment.'); 
                  window.location.href = '../payments/payment_selection.php?booking_id=" . $conn->insert_id . "';</script>";
            exit();
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
            width: 35px;
            height: 35px;
            margin: 4px;
            text-align: center;
            line-height: 35px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Seat Colors */
        .seat.available {
            background-color: #28a745;
            /* Green for available seats */
            color: white;
        }

        .seat.reserved {
            background-color: #ffc107;
            /* Yellow for reserved seats */
            color: white;
            cursor: not-allowed;
        }

        .seat.booked {
            background-color: #dc3545;
            /* Red for booked seats */
            color: white;
            cursor: not-allowed;
        }

        .seat.selected {
            background-color: #007bff;
            /* Blue for selected seats */
            color: white;
        }

        /* Seat Map Layout */
        .seat-map {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            /* 6 seats per row */
            gap: 8px;
            margin-bottom: 20px;
        }

        .legend {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Book Your Tickets</h2>
        <div class="mt-4">
            <h5>Route Details</h5>
            <p><strong>Bus:</strong> <?= htmlspecialchars($route['bus_name']); ?></p>
            <p><strong>Route:</strong> <?= htmlspecialchars($route['source'] . " to " . $route['destination']); ?></p>
            <p><strong>Departure:</strong> <?= htmlspecialchars($route['departure_time']); ?></p>
            <p><strong>Arrival:</strong> <?= htmlspecialchars($route['arrival_time']); ?></p>
            <p><strong>Price per Seat:</strong> NPR <?= number_format($route['price'], 2); ?></p>
            <p><strong>AC:</strong> <?= $route['is_ac'] ? 'Yes' : 'No'; ?></p>
            <p><strong>Wi-Fi:</strong> <?= $route['is_wifi'] ? 'Yes' : 'No'; ?></p>
        </div>

        <div class="legend">
            <h5>Seat Legend</h5>
            <p><span class="badge bg-success">Available</span> - You can select this seat</p>
            <p><span class="badge bg-warning">Reserved</span> - This seat is currently reserved</p>
            <p><span class="badge bg-danger">Booked</span> - This seat is already booked</p>
        </div>

        <form method="POST" id="seatForm">
            <h5>Select Seats (Max: 5)</h5>
            <div class="seat-map">
                <?php
                for ($i = 1; $i <= $route['total_seats']; $i++) {
                    $seat_status = $seats[$i] ?? 'available'; // Default to available if not found
                    $seat_class = "seat $seat_status";
                    echo "<div class='$seat_class' data-seat='$i'>$i</div>";
                }
                ?>
            </div>
            <input type="hidden" name="selected_seats" id="selectedSeats">
            <button type="submit" class="btn btn-primary mt-3">Confirm Booking</button>
        </form>
    </div>

    <script>
        const maxSeats = 5;
        document.querySelectorAll('.seat.available').forEach(seat => {
            seat.addEventListener('click', () => {
                const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'));
                if (selectedSeats.length < maxSeats || seat.classList.contains('selected')) {
                    seat.classList.toggle('selected');
                    const updatedSeats = Array.from(document.querySelectorAll('.seat.selected'))
                        .map(s => s.getAttribute('data-seat'));
                    document.getElementById('selectedSeats').value = updatedSeats.join(',');
                } else {
                    alert(`You can select up to ${maxSeats} seats only.`);
                }
            });
        });
    </script>
</body>

</html>