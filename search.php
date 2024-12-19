<?php
session_start();
include("includes/config.php"); // Database connection

// Initialize variables
$buses = [];
$recommended_buses = [];
$error = '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 5;
$total_pages = 1;

// Fuzzy Search Function (Levenshtein Distance)
function fuzzy_search($string, $search)
{
    return levenshtein(strtolower($string), strtolower($search)) <= 3; // Allow up to 3 character differences
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $date = trim($_POST['date']);
    $current_date = date('Y-m-d');

    if (!empty($source) && !empty($destination) && !empty($date)) {
        if ($date >= $current_date) {
            // Query to fetch buses by date along with seat availability
            $query = $conn->prepare("
                SELECT r.id AS route_id, b.id AS bus_id, b.bus_name, b.image_path, 
                       r.price, r.departure_time, r.arrival_time, r.source, r.destination, 
                       b.is_ac, b.is_wifi, COUNT(sa.seat_number) AS total_seats, 
                       SUM(CASE WHEN sa.status = 'available' THEN 1 ELSE 0 END) AS available_seats
                FROM buses b
                JOIN routes r ON b.id = r.bus_id
                LEFT JOIN seat_availability sa ON r.id = sa.route_id
                WHERE DATE(r.departure_time) = ?
                GROUP BY r.id, b.id
            ");
            $query->bind_param("s", $date);
            $query->execute();
            $result = $query->get_result();

            if ($result->num_rows > 0) {
                $all_buses = $result->fetch_all(MYSQLI_ASSOC);

                // Filter results using fuzzy search
                foreach ($all_buses as $bus) {
                    if (fuzzy_search($bus['source'], $source) && fuzzy_search($bus['destination'], $destination)) {
                        $buses[] = $bus;
                    }
                }

                // Pagination logic
                $total_pages = ceil(count($buses) / $items_per_page);
                $buses = array_slice($buses, ($current_page - 1) * $items_per_page, $items_per_page);

                // Fetch recommended buses (average rating >= 4.0)
                $recommended_query = $conn->prepare("
                    SELECT b.id AS bus_id, b.bus_name, b.image_path, r.id AS route_id, 
                           r.price, r.departure_time, r.arrival_time, r.source, r.destination, 
                           b.is_ac, b.is_wifi, AVG(f.rating) AS average_rating, 
                           COUNT(sa.seat_number) AS total_seats, 
                           SUM(CASE WHEN sa.status = 'available' THEN 1 ELSE 0 END) AS available_seats
                    FROM buses b
                    JOIN routes r ON b.id = r.bus_id
                    LEFT JOIN feedback f ON b.id = f.bus_id
                    LEFT JOIN seat_availability sa ON r.id = sa.route_id
                    WHERE DATE(r.departure_time) = ?
                    GROUP BY b.id, r.id
                    HAVING average_rating >= 4.0
                    ORDER BY average_rating DESC
                ");
                $recommended_query->bind_param("s", $date);
                $recommended_query->execute();
                $recommended_result = $recommended_query->get_result();
                $all_recommended_buses = $recommended_result->fetch_all(MYSQLI_ASSOC);

                // Filter recommended buses using fuzzy search
                foreach ($all_recommended_buses as $bus) {
                    if (fuzzy_search($bus['source'], $source) && fuzzy_search($bus['destination'], $destination)) {
                        $recommended_buses[] = $bus;
                    }
                }
            }
        } else {
            $error = "Please select a valid date (not earlier than today).";
        }
    } else {
        $error = "Please fill out all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Search Buses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #4caf50, #f8f9fa);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .search-container {
            margin: 50px auto;
            max-width: 600px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .bus-list {
            margin-top: 20px;
        }

        .bus-item {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .bus-item:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .bus-item img {
            max-width: 100%;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .bus-item h5 {
            margin: 0;
            color: #4caf50;
        }

        .tabs .nav-link {
            color: white;
            background-color: #4caf50;
            border: none;
            transition: background-color 0.2s ease;
        }

        .tabs .nav-link:hover {
            background-color: #45a045;
        }

        .tabs .nav-link.active {
            background-color: darkgreen;
        }

        .pagination {
            margin-top: 20px;
            justify-content: center;
        }

        .no-buses-message {
            text-align: center;
            font-size: 1.2em;
            color: #dc3545;
            margin-top: 20px;
        }

        .features {
            margin-top: 10px;
        }

        .feature-badge {
            margin-right: 5px;
        }

        footer {
            margin-top: auto;
            padding: 20px;
            background-color: #4caf50;
            color: white;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include("includes/header.php") ?>
    <div class="container">
        <div class="search-container">
            <h2 class="text-center">Search Buses</h2>
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="source" class="form-label">Source</label>
                    <input type="text" class="form-control" id="source" name="source" placeholder="Enter source">
                </div>
                <div class="mb-3">
                    <label for="destination" class="form-label">Destination</label>
                    <input type="text" class="form-control" id="destination" name="destination" placeholder="Enter destination">
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="tabs">
                <ul class="nav nav-tabs" id="busTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-buses-tab" data-bs-toggle="tab" data-bs-target="#all-buses" type="button" role="tab" aria-controls="all-buses" aria-selected="true">All Buses</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="recommended-buses-tab" data-bs-toggle="tab" data-bs-target="#recommended-buses" type="button" role="tab" aria-controls="recommended-buses" aria-selected="false">Recommended Buses</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="busTabsContent">
                    <!-- All Buses Tab -->
                    <div class="tab-pane fade show active" id="all-buses" role="tabpanel" aria-labelledby="all-buses-tab">
                        <div class="bus-list">
                            <?php if (!empty($buses)): ?>
                                <?php foreach ($buses as $bus): ?>
                                    <div class="bus-item">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="<?php echo $bus['image_path']; ?>" alt="<?php echo $bus['bus_name']; ?>" class="img-fluid">
                                            </div>
                                            <div class="col-md-8">
                                                <h5><?php echo $bus['bus_name']; ?></h5>
                                                <p><strong>Route:</strong> <?php echo $bus['source'] . ' to ' . $bus['destination']; ?></p>
                                                <p><strong>Departure Time:</strong> <?php echo date('H:i', strtotime($bus['departure_time'])); ?></p>
                                                <p><strong>Arrival Time:</strong> <?php echo date('H:i', strtotime($bus['arrival_time'])); ?></p>
                                                <p><strong>Price:</strong> Rs. <?php echo $bus['price']; ?></p>
                                                <p><strong>Total Seats:</strong> <?php echo $bus['total_seats']; ?> | <strong>Available Seats:</strong> <?php echo $bus['available_seats']; ?></p>
                                                <div class="features">
                                                    <!-- For AC feature -->
                                                    <?php if ($bus['is_ac'] == 1) {
                                                        echo '<span class="badge bg-success feature-badge">AC</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary feature-badge">AC: Not Available</span>';
                                                    } ?>

                                                    <!-- For Wi-Fi feature -->
                                                    <?php if ($bus['is_wifi'] == 1) {
                                                        echo '<span class="badge bg-info feature-badge">Wi-Fi</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary feature-badge">Wi-Fi: Not Available</span>';
                                                    } ?>
                                                </div>
                                                <a href="booking/book_ticket.php?bus_id=<?php echo $bus['bus_id']; ?>&route_id=<?php echo $bus['route_id']; ?>" class="btn btn-success mt-2">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-buses-message">No buses available for the selected search criteria.</div>
                            <?php endif; ?>
                        </div>
                        <!-- Pagination -->
                        <div class="pagination">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&source=<?php echo $source; ?>&destination=<?php echo $destination; ?>&date=<?php echo $date; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Recommended Buses Tab -->
                    <div class="tab-pane fade" id="recommended-buses" role="tabpanel" aria-labelledby="recommended-buses-tab">
                        <div class="bus-list">
                            <?php if (!empty($recommended_buses)): ?>
                                <?php foreach ($recommended_buses as $bus): ?>
                                    <div class="bus-item">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="<?php echo $bus['image_path']; ?>" alt="<?php echo $bus['bus_name']; ?>" class="img-fluid">
                                            </div>
                                            <div class="col-md-8">
                                                <h5><?php echo $bus['bus_name']; ?></h5>
                                                <p><strong>Route:</strong> <?php echo $bus['source'] . ' to ' . $bus['destination']; ?></p>
                                                <p><strong>Departure Time:</strong> <?php echo date('H:i', strtotime($bus['departure_time'])); ?></p>
                                                <p><strong>Arrival Time:</strong> <?php echo date('H:i', strtotime($bus['arrival_time'])); ?></p>
                                                <p><strong>Price:</strong> Rs. <?php echo $bus['price']; ?></p>
                                                <p><strong>Total Seats:</strong> <?php echo $bus['total_seats']; ?> | <strong>Available Seats:</strong> <?php echo $bus['available_seats']; ?></p>
                                                <div class="features">
                                                    <!-- For AC feature -->
                                                    <?php if ($bus['is_ac'] == 1) {
                                                        echo '<span class="badge bg-success feature-badge">AC</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary feature-badge">AC: Not Available</span>';
                                                    } ?>

                                                    <!-- For Wi-Fi feature -->
                                                    <?php if ($bus['is_wifi'] == 1) {
                                                        echo '<span class="badge bg-info feature-badge">Wi-Fi</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary feature-badge">Wi-Fi: Not Available</span>';
                                                    } ?>
                                                </div>
                                                <a href="booking/book_ticket.php?bus_id=<?php echo $bus['bus_id']; ?>&route_id=<?php echo $bus['route_id']; ?>" class="btn btn-success mt-2">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-buses-message">No recommended buses available for the selected search criteria.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Yatri Online Bus Ticket Reservation System</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>