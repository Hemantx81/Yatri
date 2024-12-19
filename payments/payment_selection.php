<?php
session_start();
include('../includes/config.php');

if (!isset($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];

// Fetch booking details
$query = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$query->bind_param('i', $booking_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("<script>alert('Booking not found.'); window.location.href = '../dashboard.php';</script>");
}

$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Payment Method</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1em;
            color: #555;
        }

        .payment-options {
            margin-top: 20px;
        }

        .payment-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            margin: 10px 0;
            width: 100%;
            font-size: 1em;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-button:hover {
            background-color: #0056b3;
        }

        .khalti {
            background-color: #5434A7;
        }

        .khalti:hover {
            background-color: #392580;
        }

        .esewa {
            background-color: #009933;
        }

        .esewa:hover {
            background-color: #007722;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Select Payment Method</h2>
        <p>Total Amount: NPR <?= number_format($booking['total_amount'], 2) ?></p>
        <div class="payment-options">
            <!-- Khalti Payment Button -->
            <form action="khalti_payment.php" method="GET">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                <button type="submit" class="payment-button khalti">Pay with Khalti</button>
            </form>
            <!-- eSewa Payment Button -->
            <form action="esewa_payment.php" method="GET">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                <button type="submit" class="payment-button esewa">Pay with eSewa</button>
            </form>
        </div>
    </div>
</body>

</html>