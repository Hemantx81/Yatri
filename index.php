<?php
// Start session if not already started
session_start();
include("includes/config.php");

// Fetch search query if present
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Pagination for feedback
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Current timestamp
$current_time = date("Y-m-d H:i:s");

// Fetch available buses (filtered by search query if provided and departure time not expired)
$query = "SELECT r.id as route_id, b.bus_name, b.image_path, r.source, r.destination, r.price, r.departure_time 
          FROM routes r 
          INNER JOIN buses b ON r.bus_id = b.id 
          WHERE r.departure_time >= ?";
if (!empty($search)) {
    $query .= " AND (b.bus_name LIKE ? OR r.source LIKE ? OR r.destination LIKE ?)";
}
$query .= " ORDER BY r.departure_time ASC LIMIT 6";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bind_param("ssss", $current_time, $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param("s", $current_time);
}
$stmt->execute();
$result_available = $stmt->get_result();

// Fetch recommended buses (top-rated and with future departure times)
$query_recommended = "SELECT b.id as bus_id, b.bus_name, b.image_path, AVG(f.rating) as avg_rating, MIN(r.departure_time) as earliest_departure 
                      FROM feedback f 
                      INNER JOIN buses b ON f.bus_id = b.id 
                      INNER JOIN routes r ON b.id = r.bus_id 
                      WHERE r.departure_time >= ?
                      GROUP BY b.id 
                      ORDER BY avg_rating DESC, earliest_departure ASC LIMIT 6";
$stmt_recommended = $conn->prepare($query_recommended);
$stmt_recommended->bind_param("s", $current_time);
$stmt_recommended->execute();
$result_recommended = $stmt_recommended->get_result();

// Fetch top feedback for display with pagination
$query_feedback_paginated = "SELECT f.rating, f.comment, u.name, b.bus_name 
                             FROM feedback f
                             INNER JOIN buses b ON f.bus_id = b.id 
                             INNER JOIN users u ON f.user_id = u.id 
                             ORDER BY f.rating DESC 
                             LIMIT ? OFFSET ?";
$stmt_feedback = $conn->prepare($query_feedback_paginated);
$stmt_feedback->bind_param("ii", $limit, $offset);
$stmt_feedback->execute();
$result_feedback_paginated = $stmt_feedback->get_result();

// Count total feedback for pagination
$query_feedback_count = "SELECT COUNT(*) as total_feedback FROM feedback";
$total_feedback_result = $conn->query($query_feedback_count);
$total_feedback = $total_feedback_result->fetch_assoc()['total_feedback'];
$total_pages = ceil($total_feedback / $limit);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $bus_id = $_POST['bus_id'];

    // Handle feedback submission
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $query_insert_feedback = "INSERT INTO feedback (user_id, bus_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt_insert_feedback = $conn->prepare($query_insert_feedback);
    $stmt_insert_feedback->bind_param("iiis", $user_id, $bus_id, $rating, $comment);
    $stmt_insert_feedback->execute();
    echo "<script>alert('Feedback submitted successfully!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YATRI - Online Bus Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .hero {
            background: url('assets/images/hero.jpg') no-repeat center center / cover;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
        }

        .search-bar {
            display: flex;
            width: 50%;
            margin: 0 auto;
        }

        .search-bar input {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 5px 0 0 5px;
        }

        .search-bar button {
            padding: 15px 20px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

        .tabs {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .tabs button {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            background-color: #0066cc;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .tabs button.active {
            background-color: #004d99;
        }

        .bus-item {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .bus-item:hover {
            transform: scale(1.05);
        }

        .bus-item img {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .feedback-item {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .star-rating {
            display: flex;
            gap: 5px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }

        .star-rating input:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: gold;
        }
    </style>

</head>

<?php include("includes/header.php") ?>

<body>
    <!-- Hero Section -->
    <div class="hero">
        <h1>Welcome to YATRI</h1>
        <p>Your trusted platform for convenient and hassle-free bus reservations.</p>
        <form method="GET" action="" class="search-bar">
            <input type="text" name="search" placeholder="Search for buses, routes..." value="<?= htmlspecialchars($search) ?>" />
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Available Buses Section -->
    <div class="container my-5">
        <h2>Available Buses</h2>
        <div class="row">
            <?php if ($result_available->num_rows > 0): ?>
                <?php while ($row = $result_available->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="bus-item">
                            <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['bus_name']) ?>">
                            <h4><?= htmlspecialchars($row['bus_name']) ?></h4>
                            <p>Route: <?= htmlspecialchars($row['source']) ?> - <?= htmlspecialchars($row['destination']) ?></p>
                            <p class="price">Price: NPR <?= htmlspecialchars($row['price']) ?></p>
                            <p>Departure: <?= htmlspecialchars(date("d M Y, H:i", strtotime($row['departure_time']))) ?></p>
                            <a href="booking/book_ticket.php?route_id=<?= htmlspecialchars($row['route_id']) ?>" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center">No buses found for your search query.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recommended Buses Section -->
    <!-- <div class="container my-5">
        <h2>Recommended Buses (Top Rated)</h2>
        <div class="row">
            <?php if ($result_recommended->num_rows > 0): ?>
                <?php while ($row = $result_recommended->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="bus-item">
                            <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['bus_name']) ?>">
                            <h4><?= htmlspecialchars($row['bus_name']) ?> (Rating: <?= number_format($row['avg_rating'], 2) ?>)</h4>
                            <p>Next Departure: <?= htmlspecialchars(date("d M Y, H:i", strtotime($row['earliest_departure']))) ?></p>
                            <a href="booking/book_ticket.php?route_id=<?= htmlspecialchars($row['bus_id']) ?>" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center">No recommended buses found.</p>
            <?php endif; ?>
        </div>
    </div> -->
    <div class="container my-5">
        <h2>Recommended Buses (Top Rated)</h2>
        <div class="row" id="recommended-buses">
            <!-- Recommended buses will be dynamically added here -->
        </div>
    </div>

    <!-- Feedback Section -->
    <div class="container my-5">
        <h2>User Feedback</h2>
        <?php if ($result_feedback_paginated->num_rows > 0): ?>
            <?php while ($row = $result_feedback_paginated->fetch_assoc()): ?>
                <div class="feedback-item my-3">
                    <h5><?= htmlspecialchars($row['bus_name']) ?> (Rating: <?= htmlspecialchars($row['rating']) ?>/5)</h5>
                    <p><?= htmlspecialchars($row['comment']) ?></p>
                    <p><strong>- <?= htmlspecialchars($row['name']) ?></strong></p>
                </div>
            <?php endwhile; ?>
            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p class="text-center">No feedback available.</p>
        <?php endif; ?>
    </div>

    <!-- Feedback Modal Trigger -->
    <div class="text-center my-4">
        <?php if ($is_logged_in): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#feedbackModal">Give Feedback</button>
        <?php else: ?>
            <p class="text-center">Please <a href="login.php">log in</a> to provide feedback.</p>
        <?php endif; ?>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Give Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($is_logged_in): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="bus_id" class="form-label">Bus</label>
                                <select class="form-select" id="bus_id" name="bus_id" required>
                                    <?php
                                    // Fetch buses available for feedback by the logged-in user
                                    $query_user_buses = "SELECT b.id as bus_id, b.bus_name, r.source, r.destination
                                                         FROM buses b
                                                         INNER JOIN routes r ON b.id = r.bus_id";
                                    $stmt_user_buses = $conn->prepare($query_user_buses);
                                    $stmt_user_buses->execute();
                                    $result_user_buses = $stmt_user_buses->get_result();
                                    ?>
                                    <?php while ($bus = $result_user_buses->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($bus['bus_id']) ?>"><?= htmlspecialchars($bus['bus_name']) ?> (<?= htmlspecialchars($bus['source']) ?> - <?= htmlspecialchars($bus['destination']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required />
                                    <label for="star5">★</label>
                                    <input type="radio" id="star4" name="rating" value="4" />
                                    <label for="star4">★</label>
                                    <input type="radio" id="star3" name="rating" value="3" />
                                    <label for="star3">★</label>
                                    <input type="radio" id="star2" name="rating" value="2" />
                                    <label for="star2">★</label>
                                    <input type="radio" id="star1" name="rating" value="1" />
                                    <label for="star1">★</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment</label>
                                <textarea id="comment" class="form-control" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    <?php else: ?>
                        <p>Please <a href="login.php">log in</a> to submit feedback.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include("includes/footer.php") ?>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Fetch bus and feedback data
            const response = await fetch('server.php');
            const {
                buses,
                feedback
            } = await response.json();

            // Current time for filtering
            const currentTime = new Date();

            // Aggregate ratings for each bus
            const busRatings = {};
            feedback.forEach(({
                bus_id,
                rating
            }) => {
                if (!busRatings[bus_id]) {
                    busRatings[bus_id] = {
                        total: 0,
                        count: 0
                    };
                }
                busRatings[bus_id].total += parseFloat(rating);
                busRatings[bus_id].count += 1;
            });

            // Calculate average ratings
            buses.forEach(bus => {
                const ratings = busRatings[bus.bus_id];
                bus.avg_rating = ratings ? (ratings.total / ratings.count) : 0;
            });

            // Filter buses with future departure times
            const futureBuses = buses.filter(bus => new Date(bus.departure_time) > currentTime);

            // Sort buses by average rating (descending) and earliest departure time (ascending)
            const sortedBuses = futureBuses.sort((a, b) => {
                if (b.avg_rating !== a.avg_rating) {
                    return b.avg_rating - a.avg_rating; // Higher rating first
                }
                return new Date(a.departure_time) - new Date(b.departure_time); // Earlier departure first
            });

            // Render recommended buses
            const recommendedContainer = document.getElementById('recommended-buses');
            if (sortedBuses.length === 0) {
                recommendedContainer.innerHTML = '<p>No recommended buses found.</p>';
            } else {
                sortedBuses.forEach(bus => {
                    recommendedContainer.innerHTML += `
                        <div class="col-md-4">
                            <div class="bus-item">
                                <img src="${bus.image_path}" alt="${bus.bus_name}">
                                <h4>${bus.bus_name} (Rating: ${bus.avg_rating.toFixed(2)})</h4>
                                <p>Route: ${bus.source} - ${bus.destination}</p>
                                <p>Price: NPR ${bus.price}</p>
                                <p>Next Departure: ${new Date(bus.departure_time).toLocaleString()}</p>
                                <a href="booking/book_ticket.php?route_id=${bus.bus_id}" class="btn btn-primary">Book Now</a>
                            </div>
                        </div>
                    `;
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>