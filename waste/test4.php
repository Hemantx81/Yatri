<?php
session_start();
include("includes/config.php"); // Database connection
include 'includes/header.php'; // Include header

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
            // Query to fetch buses by date
            $query = $conn->prepare("
            SELECT r.id AS route_id, b.id AS bus_id, b.bus_name, b.image_path, 
                   r.price, r.departure_time, r.arrival_time, r.source, r.destination
            FROM buses b
            JOIN routes r ON b.id = r.bus_id
            WHERE DATE(r.departure_time) = ?
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
                if (!empty($buses)) {
                    $recommended_query = $conn->prepare("
                        SELECT b.id, b.bus_name, b.image_path, r.price, r.departure_time, r.arrival_time, r.source, r.destination, AVG(f.rating) AS average_rating
                        FROM buses b
                        JOIN routes r ON b.id = r.bus_id
                        LEFT JOIN feedback f ON b.id = f.bus_id
                        WHERE DATE(r.departure_time) = ?
                        GROUP BY b.id
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
                $error = "No buses available for the selected date.";
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
            background-color: #f8f9fa;
        }

        .search-container {
            margin: 50px auto;
            max-width: 600px;
        }

        .bus-list {
            margin-top: 20px;
        }

        .bus-item {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            padding: 20px;
        }

        .bus-item img {
            max-width: 100%;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .bus-item h5 {
            margin: 0;
        }

        .tabs .nav-link {
            color: white;
            background-color: green;
        }

        .tabs .nav-link.active {
            background-color: darkgreen;
        }

        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
    </style>
</head>

<body>
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

            <?php if ($error): ?>
                <div class="alert alert-danger mt-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($buses)): ?>
            <div class="tabs">
                <ul class="nav nav-tabs" id="busTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-buses-tab" data-bs-toggle="tab" data-bs-target="#all-buses" type="button" role="tab">
                            All Buses
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="recommended-buses-tab" data-bs-toggle="tab" data-bs-target="#recommended-buses" type="button" role="tab">
                            Recommended Buses
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="busTabsContent">
                    <div class="tab-pane fade show active" id="all-buses" role="tabpanel">
                        <?php foreach ($buses as $bus): ?>
                            <div class="bus-item">
                                <img src="<?php echo $bus['image_path']; ?>" alt="Bus Image">
                                <h5><?php echo $bus['bus_name']; ?></h5>
                                <p>Route: <?php echo $bus['source'] . ' - ' . $bus['destination']; ?></p>
                                <p>Price: $<?php echo $bus['price']; ?></p>
                                <p>Departure: <?php echo $bus['departure_time']; ?></p>
                                <p>Arrival: <?php echo $bus['arrival_time']; ?></p>
                                <a href="booking/book_ticket.php?bus_id=<?php echo $bus['bus_id']; ?>&route_id=<?php echo $bus['route_id']; ?>" class="btn btn-success">Book Now</a>

                            </div>
                        <?php endforeach; ?>

                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                    <div class="tab-pane fade" id="recommended-buses" role="tabpanel">
                        <?php foreach ($recommended_buses as $bus): ?>
                            <div class="bus-item">
                                <img src="<?php echo $bus['image_path']; ?>" alt="Bus Image">
                                <h5><?php echo $bus['bus_name']; ?></h5>
                                <p>Route: <?php echo $bus['source'] . ' - ' . $bus['destination']; ?></p>
                                <p>Price: $<?php echo $bus['price']; ?></p>
                                <p>Departure: <?php echo $bus['departure_time']; ?></p>
                                <p>Arrival: <?php echo $bus['arrival_time']; ?></p>
                                <p>Average Rating: <?php echo round($bus['average_rating'], 1); ?> ★</p>
                                <a href="booking/book_ticket.php?bus_id=<?php echo $bus['bus_id']; ?>&route_id=<?php echo $bus['route_id']; ?>" class="btn btn-success">Book Now</a>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
            <div class="alert alert-warning mt-4">
                No buses available for the selected date.
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>