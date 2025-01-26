<?php
session_start();
include("includes/config.php");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all buses for client-side filtering
$all_buses_query = "
    SELECT r.id AS route_id, b.id AS bus_id, b.bus_name, b.image_path,
           b.is_ac, b.is_wifi, r.price,
           DATE_FORMAT(r.departure_time, '%h:%i %p') AS departure_time,
           DATE_FORMAT(r.arrival_time, '%h:%i %p') AS arrival_time,
           r.source, r.destination, r.departure_time AS raw_departure_time,
           COUNT(sa.seat_number) AS total_seats,
           SUM(CASE WHEN sa.status = 'available' THEN 1 ELSE 0 END) AS available_seats
    FROM buses b
    JOIN routes r ON b.id = r.bus_id
    LEFT JOIN seat_availability sa ON r.id = sa.route_id
    WHERE r.departure_time > NOW() -- Ensure buses departing in the past are excluded
    GROUP BY r.id
    ORDER BY r.departure_time ASC
";
$result = $conn->query($all_buses_query);
$all_buses = $result->fetch_all(MYSQLI_ASSOC);
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

        .filter-section {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f7f7f7;
            margin-top: 20px;
        }
    </style>
    <script>
        $(function() {
            const buses = <?= json_encode($all_buses) ?>;

            // Apply Autocomplete to Input Fields
            const availablePlaces = ["Kathmandu", "Pokhara", "Chitwan", "Lumbini", "Biratnagar", "Nepalgunj", "Illam", "Kohalpur", "Butwal", "Kanchanpur", "Dhangadi", "Tikapur"];
            $("#source, #destination").autocomplete({
                source: availablePlaces
            });

            // Levenshtein Distance Algorithm for Fuzzy Search
            function levenshteinSearch(query, buses) {
                const {
                    source,
                    destination
                } = query;
                const maxDistance = 3; // Allowable distance for fuzzy matching
                const now = new Date();

                return buses.filter(bus => {
                    const departureTime = new Date(bus.raw_departure_time);

                    const sourceDistance = levenshteinDistance(source.toLowerCase(), bus.source.toLowerCase());
                    const destinationDistance = levenshteinDistance(destination.toLowerCase(), bus.destination.toLowerCase());

                    return (
                        sourceDistance <= maxDistance &&
                        destinationDistance <= maxDistance &&
                        departureTime > now
                    );
                });
            }

            // Levenshtein Distance Calculation
            function levenshteinDistance(a, b) {
                const matrix = [];

                // Create the matrix
                for (let i = 0; i <= b.length; i++) {
                    matrix[i] = [i];
                }
                for (let j = 0; j <= a.length; j++) {
                    matrix[0][j] = j;
                }

                // Populate the matrix
                for (let i = 1; i <= b.length; i++) {
                    for (let j = 1; j <= a.length; j++) {
                        if (b.charAt(i - 1) === a.charAt(j - 1)) {
                            matrix[i][j] = matrix[i - 1][j - 1];
                        } else {
                            matrix[i][j] = Math.min(
                                matrix[i - 1][j - 1] + 1, // Substitution
                                Math.min(
                                    matrix[i][j - 1] + 1, // Insertion
                                    matrix[i - 1][j] + 1 // Deletion
                                )
                            );
                        }
                    }
                }

                return matrix[b.length][a.length];
            }

            // Filter Results Function
            function filterResults(filters, buses) {
                return buses.filter(bus => {
                    const matchesAC = !filters.ac || bus.is_ac;
                    const matchesWiFi = !filters.wifi || bus.is_wifi;
                    const matchesPrice = bus.price >= filters.price.min && bus.price <= filters.price.max;
                    const matchesSeats = bus.available_seats >= filters.minSeats;

                    return matchesAC && matchesWiFi && matchesPrice && matchesSeats;
                });
            }

            // Display Results Function
            function displayResults(filteredBuses) {
                const resultsContainer = $(".results");
                resultsContainer.empty();

                if (filteredBuses.length > 0) {
                    filteredBuses.forEach(bus => {
                        const busHTML = `
                        <div class="bus-item">
                            <h5>${bus.bus_name}</h5>
                            <p>Route: ${bus.source} to ${bus.destination}</p>
                            <p>Departure: ${bus.departure_time} | Arrival: ${bus.arrival_time}</p>
                            <p>Price: ${bus.price} NPR</p>
                            <p>AC: ${bus.is_ac ? "Yes" : "No"} | Wi-Fi: ${bus.is_wifi ? "Yes" : "No"}</p>
                            <p>Available Seats: ${bus.available_seats}</p>
                            <a href="booking/book_ticket.php?route_id=${bus.route_id}" class="btn btn-success">Book Now</a>
                        </div>
                    `;
                        resultsContainer.append(busHTML);
                    });
                } else {
                    resultsContainer.html('<div class="no-buses">No buses found for the selected criteria.</div>');
                }
            }

            // Handle Form Submission
            $("form.search-form").on("submit", function(e) {
                e.preventDefault();

                const source = $("#source").val();
                const destination = $("#destination").val();
                const date = $("#date").val();
                const currentDate = new Date().toISOString().split("T")[0];

                if (!source || !destination || !date || date < currentDate) {
                    alert("Please provide valid search inputs.");
                    return;
                }

                const searchedBuses = levenshteinSearch({
                    source,
                    destination
                }, buses);
                displayResults(searchedBuses);

                // Show the filter section only after initial search
                $(".filter-section").removeClass("d-none");
            });

            // Handle Filter Button Click
            $("#filter-button").on("click", function() {
                const filters = {
                    ac: $("#filter-ac").is(":checked"),
                    wifi: $("#filter-wifi").is(":checked"),
                    price: {
                        min: parseInt($("#filter-price-min").val()) || 0,
                        max: parseInt($("#filter-price-max").val()) || Infinity
                    },
                    minSeats: parseInt($("#filter-seats").val()) || 0
                };

                const searchedBuses = levenshteinSearch({
                    source: $("#source").val(),
                    destination: $("#destination").val()
                }, buses);

                const filteredBuses = filterResults(filters, searchedBuses);
                displayResults(filteredBuses);
            });
        });
    </script>

</head>

<body>
    <?php include("includes/header.php"); ?>
    <div class="container mt-4">
        <h2>Search Buses</h2>
        <form class="search-form">
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

        <!-- Filter Section -->
        <div class="filter-section">
            <h5>Filters</h5>
            <div class="row">
                <div class="col-md-3">
                    <label for="filter-price-min">Price (Min):</label>
                    <input type="number" id="filter-price-min" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="filter-price-max">Price (Max):</label>
                    <input type="number" id="filter-price-max" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="filter-seats">Min Seats:</label>
                    <input type="number" id="filter-seats" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>
                        <input type="checkbox" id="filter-ac"> AC
                    </label>
                </div>
                <div class="col-md-2">
                    <label>
                        <input type="checkbox" id="filter-wifi"> Wi-Fi
                    </label>
                </div>
            </div>
            <button id="filter-button" class="btn btn-secondary mt-3">Apply Filters</button>

        </div>

        <!-- Results Container -->
        <div class="results mt-4"></div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>

</html>