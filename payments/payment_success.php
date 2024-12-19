<?php
session_start();
include('../includes/config.php');

// Check if booking_id is set
if (!isset($_GET['booking_id'])) {
    die("<script>alert('Invalid booking ID.'); window.location.href = '../dashboard.php';</script>");
}

$booking_id = (int)$_GET['booking_id'];

// Fetch payment details
$query = $conn->prepare("SELECT b.*, p.payment_method, p.transaction_id, p.payment_amount, p.payment_status
                        FROM bookings b
                        JOIN payments p ON b.id = p.booking_id
                        WHERE b.id = ?");
$query->bind_param('i', $booking_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("<script>alert('Payment details not found.'); window.location.href = '../dashboard.php';</script>");
}

$payment = $result->fetch_assoc();

// HTML for displaying receipt and buttons
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>

    <style>
        .receipt-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #e0f7fa;
            /* Light Green */
        }

        .receipt-title {
            font-size: 24px;
            color: #388e3c;
            /* Dark Green */
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt-details {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: space-around;
        }

        .button {
            padding: 10px 20px;
            background-color: #388e3c;
            /* Dark Green */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .button:hover {
            background-color: #2c6e32;
            /* Darker Green */
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="receipt-title">Payment Receipt</div>

        <div class="receipt-details">
            <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($payment['id']); ?></p>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
            <p><strong>Payment Amount:</strong> NPR <?php echo number_format($payment['payment_amount'], 2); ?></p>
            <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($payment['payment_status']); ?></p>
        </div>

        <div class="button-container">
            <!-- Redirect to Home Page Button -->
            <a href="../index.php" class="button">Back to Home</a>

            <!-- Download Receipt Button -->
            <a href="esewa_receipt.php?booking_id=<?php echo htmlspecialchars($payment['id']); ?>" class="button">Download Receipt</a>
        </div>
    </div>
</body>

</html>