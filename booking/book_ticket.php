<?php
session_start();
include('../includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to book tickets.'); window.location.href = '../login.php';</script>";
    exit();
}

$route_id = (int)$_GET['route_id'];

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
$bus_id = $route['bus_id'];

// Fetch seat availability
$seat_query = $conn->prepare("SELECT seat_number, status, booking_time 
                              FROM seat_availability 
                              WHERE route_id = ? AND bus_id = ?");
$seat_query->bind_param('ii', $route_id, $bus_id);
$seat_query->execute();
$seat_result = $seat_query->get_result();

$seats = [];
while ($row = $seat_result->fetch_assoc()) {
    $seats[$row['seat_number']] = $row['status'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
    $user_id = $_SESSION['user_id'];

    if (empty($selected_seats)) {
        echo "<script>alert('Please select at least one seat to proceed.');</script>";
    } elseif (count($selected_seats) > 5) {
        echo "<script>alert('You can select a maximum of 5 seats.');</script>";
    } else {
        // Validate selected seats
        $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $validation_query = $conn->prepare("SELECT seat_number 
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
            // Reserve the seats
            $reserve_query = $conn->prepare("UPDATE seat_availability 
                                            SET status = 'reserved', booking_time = NOW() 
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .seats-layout {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
        }

        .seat-column {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .seat-row {
            display: flex;
            gap: 10px;
        }

        .column-separator {
            width: 5px;
            height: 100%;
            background: #ddd;
            border-radius: 2px;
        }

        .seat {
            width: 50px;
            height: 50px;
            background: #007bff;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
        }

        .seat.reserved {
            background: #ffc107;
            cursor: not-allowed;
        }

        .seat.selected {
            background: #5cb85c;
        }

        .seat:hover:not(.reserved):not(.selected) {
            transform: scale(1.1);
        }

        .door-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .door {
            width: 60px;
            height: 40px;
            background: #444;
            color: white;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 5px;
            border: 2px solid #000;
        }

        /* Legend Styles */
        .legend .seat {
            width: 20px;
            height: 20px;
            margin: 0 5px;
            border-radius: 3px;
            display: inline-block;
            border: 1px solid #ccc;
        }

        .legend .available {
            background-color: #007bff;
        }

        .legend .reserved {
            background-color: #ffc107;
        }

        .legend .booked {
            background-color: #dc3545;
        }

        .legend .selected {
            background-color: #5cb85c;
        }

        /* Door Positioning */
        .door-container {
            position: absolute;
            top: 50px;
            left: -50px;
        }

        /* Driver Image Styling */
        .driver-area img {
            display: block;
            margin: 0 auto;
            border-radius: 50%;
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

        <div class="text-center">
            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#seatModal">Select Seats</button>
        </div>
        <!-- Modal for seats -->
        <div class="modal fade" id="seatModal" tabindex="-1" aria-labelledby="seatModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="seatModalLabel">Select Seats</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="legend text-center mb-4">
                            <div class="d-flex justify-content-center align-items-center gap-3">
                                <div class="seat available"></div> <span>Available</span>
                                <div class="seat reserved"></div> <span>Reserved</span>
                                <div class="seat booked"></div> <span>Booked</span>
                                <div class="seat selected"></div> <span>Selected</span>
                            </div>
                        </div>
                        <div class="bus-container mx-xl">
                            <div class="driver-area">
                                <img src="../assets/images/download (1).png" alt="Driver">
                            </div>

                            <div class="seats-layout">
                                <!-- Left Column -->
                                <div class="seat-column">
                                    <?php
                                    for ($i = 1; $i <= $route['total_seats'] / 2; $i += 2) {
                                        $seat_status_1 = $seats[$i] ?? 'available';
                                        $seat_status_2 = $seats[$i + 1] ?? 'available';

                                        echo "<div class='seat-row'>
                                        <div class='seat $seat_status_1' data-seat='$i'>$i</div>
                                        <div class='seat $seat_status_2' data-seat='" . ($i + 1) . "'>" . ($i + 1) . "</div>
                                      </div>";
                                    }
                                    ?>
                                </div>

                                <!-- Column Separator -->
                                <div class="column-separator"></div>

                                <!-- Right Column -->
                                <div class="seat-column">
                                    <?php
                                    $start = ceil($route['total_seats'] / 2) + 1;
                                    for ($i = $start; $i <= $route['total_seats']; $i += 2) {
                                        $seat_status_1 = $seats[$i] ?? 'available';
                                        $seat_status_2 = $seats[$i + 1] ?? 'available';

                                        echo "<div class='seat-row'>
                                        <div class='seat $seat_status_1' data-seat='$i'>$i</div>
                                        <div class='seat $seat_status_2' data-seat='" . ($i + 1) . "'>" . ($i + 1) . "</div>
                                      </div>";
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="door-container">
                                <div class="door">Door</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" id="confirmSeats">Confirm</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Booking Form -->
        <form method="POST" id="seatForm">
            <input type="hidden" name="selected_seats" id="selectedSeats">
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">Confirm Booking</button>
            </div>
        </form>
    </div>

    <script>
        const maxSeats = 5;
        const selectedSeatsInput = document.getElementById('selectedSeats');
        const confirmSeatsButton = document.getElementById('confirmSeats');

        document.querySelectorAll('.seat').forEach(seat => {
            seat.addEventListener('click', () => {
                if (!seat.classList.contains('reserved')) {
                    if (seat.classList.contains('selected')) {
                        seat.classList.remove('selected');
                    } else {
                        const selectedSeats = document.querySelectorAll('.seat.selected');
                        if (selectedSeats.length < maxSeats) {
                            seat.classList.add('selected');
                        } else {
                            alert(`You can select up to ${maxSeats} seats only.`);
                        }
                    }
                }
            });
        });

        confirmSeatsButton.addEventListener('click', () => {
            const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'))
                .map(seat => seat.getAttribute('data-seat'));
            selectedSeatsInput.value = selectedSeats.join(',');
            document.getElementById('seatModal').querySelector('.btn-close').click();
        });

        document.getElementById('seatForm').addEventListener('submit', function(e) {
            if (!selectedSeatsInput.value) {
                e.preventDefault();
                alert('Please select at least one seat to proceed.');
            }
        });
    </script>
</body>

</html>