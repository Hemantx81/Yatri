<?php
session_start();
include("includes/config.php");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Variables
$buses = [];
$recommended_buses = [];
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 3;
$total_pages = 1;

// Initialize filter variables
$filter_price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$filter_price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 10000;
$filter_departure_time = isset($_GET['departure_time']) ? $_GET['departure_time'] : 'any';
$filter_ac = isset($_GET['ac']) ? (int)$_GET['ac'] : null;
$filter_wifi = isset($_GET['wifi']) ? (int)$_GET['wifi'] : null;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    $source = trim($_REQUEST['source'] ?? '');
    $destination = trim($_REQUEST['destination'] ?? '');
    $date = trim($_REQUEST['date'] ?? '');
    $current_date = date('Y-m-d');

    if (!empty($source) && !empty($destination) && !empty($date) && $date >= $current_date) {
        // Main query for buses
        $query = "
            SELECT r.id AS route_id, b.id AS bus_id, b.bus_name, b.image_path,
                   b.is_ac, b.is_wifi, r.price,
                   DATE_FORMAT(r.departure_time, '%h:%i %p') AS departure_time,
                   DATE_FORMAT(r.arrival_time, '%h:%i %p') AS arrival_time,
                   r.source, r.destination, COUNT(sa.seat_number) AS total_seats,
                   SUM(CASE WHEN sa.status = 'available' THEN 1 ELSE 0 END) AS available_seats
            FROM buses b
            JOIN routes r ON b.id = r.bus_id
            LEFT JOIN seat_availability sa ON r.id = sa.route_id
            WHERE DATE(r.departure_time) = ? 
            AND r.source LIKE ? 
            AND r.destination LIKE ? 
            AND r.price BETWEEN ? AND ? 
        ";

        if ($filter_departure_time !== 'any') {
            $query .= " AND HOUR(r.departure_time) = ? ";
        }
        if ($filter_ac !== null) {
            $query .= " AND b.is_ac = ? ";
        }
        if ($filter_wifi !== null) {
            $query .= " AND b.is_wifi = ? ";
        }
        $query .= " GROUP BY r.id ORDER BY r.departure_time ASC";

        $stmt = $conn->prepare($query);
        $source_param = "%" . $source . "%";
        $destination_param = "%" . $destination . "%";

        $params = [$date, $source_param, $destination_param, $filter_price_min, $filter_price_max];
        if ($filter_departure_time !== 'any') $params[] = $filter_departure_time;
        if ($filter_ac !== null) $params[] = $filter_ac;
        if ($filter_wifi !== null) $params[] = $filter_wifi;

        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $all_buses = $result->fetch_all(MYSQLI_ASSOC);
        $total_pages = ceil(count($all_buses) / $items_per_page);
        $buses = array_slice($all_buses, ($current_page - 1) * $items_per_page, $items_per_page);

        // Recommended buses based on feedback
        $recommended_query = "
            SELECT r.id AS route_id, b.id AS bus_id, b.bus_name, b.image_path, b.is_ac, b.is_wifi,
                   AVG(f.rating) AS avg_rating, r.price,
                   DATE_FORMAT(r.departure_time, '%h:%i %p') AS departure_time,
                   DATE_FORMAT(r.arrival_time, '%h:%i %p') AS arrival_time,
                   r.source, r.destination
            FROM feedback f
            JOIN routes r ON f.bus_id = r.bus_id
            JOIN buses b ON r.bus_id = b.id
            WHERE r.source LIKE ? AND r.destination LIKE ? 
            GROUP BY r.id
            ORDER BY avg_rating DESC LIMIT 3
        ";
        $stmt_recommended = $conn->prepare($recommended_query);
        $stmt_recommended->bind_param('ss', $source_param, $destination_param);
        $stmt_recommended->execute();
        $recommended_buses = $stmt_recommended->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_message = "Travel date must be today or later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Buses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .bus-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }

        .no-buses {
            text-align: center;
            font-size: 1.2rem;
            color: #888;
            margin-top: 20px;
        }

        .footer {
            background: #004d99;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
    <script>
        $(function() {
            const availablePlaces = ["Kathmandu", "Pokhara", "Chitwan", "Lumbini", "Biratnagar", "Nepalgunj"];
            $("#source, #destination").autocomplete({
                source: availablePlaces
            });
        });
    </script>
</head>

<body>
    <?php include("includes/header.php"); ?>
    <div class="container mt-4">
        <h2>Search Buses</h2>
        <form method="POST">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" id="source" name="source" class="form-control" placeholder="Source" required>
                </div>
                <div class="col-md-4">
                    <input type="text" id="destination" name="destination" class="form-control" placeholder="Destination" required>
                </div>
                <div class="col-md-4">
                    <input type="date" id="date" name="date" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Search</button>
        </form>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="price_min" class="form-label">Min Price</label>
                    <input type="number" id="price_min" name="price_min" class="form-control" min="0" value="<?= $filter_price_min ?>">
                </div>
                <div class="col-md-3">
                    <label for="price_max" class="form-label">Max Price</label>
                    <input type="number" id="price_max" name="price_max" class="form-control" min="0" value="<?= $filter_price_max ?>">
                </div>
                <div class="col-md-3">
                    <label for="departure_time" class="form-label">Departure Time</label>
                    <select id="departure_time" name="departure_time" class="form-control">
                        <option value="any" <?= $filter_departure_time == 'any' ? 'selected' : '' ?>>Any</option>
                        <?php for ($i = 0; $i < 24; $i++): ?>
                            <option value="<?= $i ?>" <?= $filter_departure_time == $i ? 'selected' : '' ?>><?= sprintf('%02d:00', $i) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- AC and Wi-Fi Filters -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <label for="is_ac" class="form-label">AC</label>
                    <input type="checkbox" id="is_ac" name="is_ac" value="1" <?= $filter_ac ? 'checked' : '' ?>>
                </div>
                <div class="col-md-3">
                    <label for="is_wifi" class="form-label">Wi-Fi</label>
                    <input type="checkbox" id="is_wifi" name="is_wifi" value="1" <?= $filter_wifi ? 'checked' : '' ?>>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary mt-3">Apply Filters</button>
        </form>


        <!-- Bus Results -->
        <div class="mt-4">
            <?php if (!empty($buses)): ?>
                <?php foreach ($buses as $bus): ?>
                    <div class="bus-item">
                        <h5><?= htmlspecialchars($bus['bus_name']) ?></h5>
                        <p>Route: <?= htmlspecialchars($bus['source']) ?> to <?= htmlspecialchars($bus['destination']) ?></p>
                        <p>Departure: <?= htmlspecialchars($bus['departure_time']) ?> | Arrival: <?= htmlspecialchars($bus['arrival_time']) ?></p>
                        <p>Price: <?= htmlspecialchars($bus['price']) ?> NPR</p>
                        <p>AC: <?= $bus['is_ac'] ? 'Yes' : 'No' ?> | Wi-Fi: <?= $bus['is_wifi'] ? 'Yes' : 'No' ?></p>
                        <p>Available Seats: <?= htmlspecialchars($bus['available_seats']) ?></p>
                        <a href="booking/book_ticket.php?route_id=<?php echo $bus['route_id']; ?>" class="btn btn-success">Book Now</a>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="no-buses">No buses found for the selected criteria.</div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (count($buses) > 0 && $total_pages > 1): ?>
            <div class="mt-4 text-center">
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $current_page == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&source=<?= $source ?>&destination=<?= $destination ?>&date=<?= $date ?>&price_min=<?= $filter_price_min ?>&price_max=<?= $filter_price_max ?>&departure_time=<?= $filter_departure_time ?>&ac=<?= $filter_ac ?>&wifi=<?= $filter_wifi ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <?php include("includes/footer.php"); ?>
</body>

</html>