<?php
require '../includes/config.php'; // Connection to the database

// Fetch buses departing within the next 2 hours
$query = "
    SELECT r.id AS route_id, r.source, r.destination, r.departure_time, r.arrival_time, 
           b.bus_name, b.total_seats 
    FROM routes r
    JOIN buses b ON r.bus_id = b.id
    WHERE r.departure_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
    ORDER BY r.departure_time ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses Departing Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
            color: #333;
        }

        .container {
            width: 90%;
            margin: 0 auto;
            max-width: 1200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-radius: 8px;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .download-link {
            display: inline-block;
            padding: 8px 12px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .download-link:hover {
            background-color: #218838;
        }

        @media (max-width: 768px) {

            table th,
            table td {
                padding: 10px;
                font-size: 14px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Buses Departing Within 2 Hours</h1>
        <table>
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure Time</th>
                    <th>Seats</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['bus_name']); ?></td>
                        <td><?= htmlspecialchars($row['source']); ?></td>
                        <td><?= htmlspecialchars($row['destination']); ?></td>
                        <td><?= htmlspecialchars($row['departure_time']); ?></td>
                        <td><?= htmlspecialchars($row['total_seats']); ?></td>
                        <td>
                            <a class="download-link" href="download_passenger_list.php?route_id=<?= $row['route_id']; ?>">Download Passenger List</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>