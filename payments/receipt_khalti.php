<?php
session_start();
include('../includes/config.php');

// Ensure 'booking_id' and 'transaction_id' are set in the URL parameters
if (!isset($_GET['booking_id']) || !isset($_GET['transaction_id'])) {
    die("<script>alert('Invalid payment details.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];
$transaction_id = $_GET['transaction_id'];

// Fetch payment details
$query = $conn->prepare("
    SELECT b.id AS booking_id, p.payment_method, p.payment_amount, p.payment_status, p.transaction_id
    FROM bookings b
    JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ? AND p.transaction_id = ?
");
$query->bind_param('is', $booking_id, $transaction_id);
$query->execute();
$result = $query->get_result();

// Check if the payment details are found
if ($result->num_rows === 0) {
    die("<script>alert('Payment details not found.'); window.location.href = '../dashboard.php';</script>");
}

$payment = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khalti Payment Receipt</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .receipt-container {
            background: #5d2b80;
            color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            padding: 20px;
            max-width: 500px;
            text-align: center;
        }

        h2 {
            color: #ffcc00;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1em;
        }

        .receipt-details {
            margin: 20px 0;
            text-align: left;
        }

        .receipt-details p {
            margin: 5px 0;
        }

        .button {
            display: inline-block;
            background-color: #ffcc00;
            color: #5d2b80;
            padding: 10px 20px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #e6b800;
        }

        .button-download {
            background-color: #ffffff;
            color: #5d2b80;
        }

        .button-download:hover {
            background-color: #f0e8ff;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <h2>Khalti Payment Receipt</h2>
        <div class="receipt-details">
            <p><strong>Booking ID:</strong> <?= isset($payment['booking_id']) ? $payment['booking_id'] : 'N/A' ?></p>
            <p><strong>Transaction ID:</strong> <?= isset($payment['transaction_id']) ? $payment['transaction_id'] : 'N/A' ?></p>
            <p><strong>Payment Method:</strong> <?= isset($payment['payment_method']) ? $payment['payment_method'] : 'N/A' ?></p>
            <p><strong>Payment Amount:</strong> NPR <?= isset($payment['payment_amount']) ? number_format($payment['payment_amount'], 2) : '0.00' ?></p>
            <p><strong>Payment Status:</strong> <?= isset($payment['payment_status']) ? $payment['payment_status'] : 'Pending' ?></p>
        </div>
        <a href="download_receipt.php?booking_id=<?= isset($payment['booking_id']) ? $payment['booking_id'] : '' ?>" class="button button-download">Download Receipt</a>
        <a href="../index.php" class="button">Back to Homepage</a>
    </div>
</body>

</html>