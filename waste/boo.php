<?php
session_start();
include_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch route details from the URL
$route_id = $_GET['route_id'] ?? null;

if (!$route_id) {
    $error_message = "No route selected. Please search for a route.";
} else {
    // Fetch route details including bus info
    $route_query = "
        SELECT routes.id, routes.source, routes.destination, routes.departure_time, 
               routes.arrival_time, routes.price, buses.bus_name, buses.image_path, 
               buses.is_ac, buses.is_wifi
        FROM routes
        JOIN buses ON routes.bus_id = buses.id
        WHERE routes.id = ?";
    $stmt = $conn->prepare($route_query);
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $route_result = $stmt->get_result();

    if ($route_result->num_rows > 0) {
        $route_details = $route_result->fetch_assoc();
    } else {
        $error_message = "Route details not found.";
    }

    // Fetch seat availability for this route
    $seat_query = "SELECT seat_number, is_booked FROM seat_availability WHERE route_id = ?";
    $stmt = $conn->prepare($seat_query);
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $seat_result = $stmt->get_result();

    $seats = [];
    while ($row = $seat_result->fetch_assoc()) {
        $seats[$row['seat_number']] = $row['is_booked'];
    }
}

// Handle booking request (via AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    $input = json_decode(file_get_contents('php://input'), true);
    $selected_seats = $input['seats'] ?? [];

    if (count($selected_seats) > 4) {
        echo json_encode(['success' => false, 'message' => 'You can only book up to 4 seats.']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $seat_numbers = implode(",", $selected_seats);

    // Validate seat availability
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    $seat_check_query = "
        SELECT seat_number 
        FROM seat_availability 
        WHERE route_id = ? AND seat_number IN ($placeholders) AND is_booked = 1";
    $stmt = $conn->prepare($seat_check_query);
    $stmt->bind_param(str_repeat('i', count($selected_seats) + 1), $route_id, ...$selected_seats);
    $stmt->execute();
    $conflicts = $stmt->get_result();
    if ($conflicts->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Some selected seats are no longer available.']);
        exit();
    }

    // Insert booking
    $booking_query = "
        INSERT INTO bookings (user_id, route_id, seat_numbers, booking_time) 
        VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($booking_query);
    $stmt->bind_param("iis", $user_id, $route_id, $seat_numbers);
    if ($stmt->execute()) {
        // Mark seats as booked
        $update_query = "
            UPDATE seat_availability 
            SET is_booked = 1 
            WHERE route_id = ? AND seat_number IN ($placeholders)";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param(str_repeat('i', count($selected_seats) + 1), $route_id, ...$selected_seats);
        $stmt->execute();

        // Redirect to payment section
        echo json_encode(['success' => true, 'message' => 'Booking successful!', 'redirect' => 'payment_selection.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book seats.']);
    }
    exit();
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
        body {
            background-color: #f8f9fa;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(5, 60px);
            gap: 10px;
            justify-content: center;
        }

        .seat {
            width: 60px;
            height: 60px;
            text-align: center;
            line-height: 60px;
            border: 1px solid #ccc;
            cursor: pointer;
            border-radius: 5px;
        }

        .available {
            background-color: #28a745;
            color: white;
        }

        .occupied {
            background-color: #dc3545;
            color: white;
            pointer-events: none;
        }

        .selected {
            background-color: #ffc107;
            color: black;
        }

        .container {
            max-width: 800px;
            margin: auto;
            margin-top: 30px;
        }

        .alert {
            margin-top: 20px;
        }

        .bus-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .bus-details img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .bus-details div {
            flex: 1;
            margin-left: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning text-center">
                <p><?php echo $error_message; ?></p>
                <a href="../search.php" class="btn btn-primary">Search Buses</a>
            </div>
        <?php else: ?>
            <h1 class="text-center my-4">Book Your Seats</h1>

            <!-- Bus details -->
            <div class="card p-4 shadow-sm mb-4">
                <div class="bus-details">
                    <img src="../<?php echo $route_details['image_path']; ?>" alt="Bus Image">
                    <div>
                        <h3><?php echo $route_details['bus_name']; ?></h3>
                        <p><strong>Source:</strong> <?php echo $route_details['source']; ?></p>
                        <p><strong>Destination:</strong> <?php echo $route_details['destination']; ?></p>
                        <p><strong>Departure:</strong> <?php echo $route_details['departure_time']; ?></p>
                        <p><strong>Arrival:</strong> <?php echo $route_details['arrival_time']; ?></p>
                        <p><strong>Price:</strong> NPR <?php echo number_format($route_details['price'], 2); ?></p>
                        <p><strong>AC:</strong> <?php echo $route_details['is_ac'] ? 'Yes' : 'No'; ?></p>
                        <p><strong>Wi-Fi:</strong> <?php echo $route_details['is_wifi'] ? 'Yes' : 'No'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Seat selection -->
            <div class="card p-4 shadow-sm">
                <h3>Select Seats</h3>
                <div class="seat-grid my-3">
                    <?php foreach ($seats as $seat => $is_booked): ?>
                        <div class="seat <?= $is_booked ? 'occupied' : 'available' ?> seat-item"
                            data-seat="<?= $seat ?>"
                            onclick="toggleSeat(this, '<?= $seat ?>')">
                            <?= $seat ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div>
                    <h3>Booking Summary</h3>
                    <ul id="selected-seats-list" class="list-group mb-3"></ul>
                    <p>Total Price: NPR <span id="total-price">0.00</span></p>
                    <button id="confirm-booking" class="btn btn-success">Confirm Booking</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let selectedSeats = [];
        let totalPrice = 0;

        function toggleSeat(el, seat) {
            const price = <?= $route_details['price'] ?>;

            if (selectedSeats.includes(seat)) {
                selectedSeats = selectedSeats.filter(s => s !== seat);
                totalPrice -= price;
                el.classList.remove('selected');
            } else if (selectedSeats.length < 4) {
                selectedSeats.push(seat);
                totalPrice += price;
                el.classList.add('selected');
            }

            // Update selected seats list and price
            updateSelectedSeats();
        }

        function updateSelectedSeats() {
            const list = document.getElementById('selected-seats-list');
            list.innerHTML = '';

            selectedSeats.forEach(seat => {
                const li = document.createElement('li');
                li.classList.add('list-group-item');
                li.textContent = seat;
                list.appendChild(li);
            });

            document.getElementById('total-price').textContent = totalPrice.toFixed(2);
        }

        // Handle booking confirmation
        document.getElementById('confirm-booking').addEventListener('click', () => {
            if (selectedSeats.length === 0) {
                alert('Please select at least one seat!');
                return;
            }

            fetch('book_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        seats: selectedSeats
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect; // Redirect to payment page
                    } else {
                        alert(data.message);
                    }
                });
        });
    </script>
</body>

</html>