<?php
session_start();
include('../includes/config.php');

// Pagination variables
$perPage = 10; // Routes per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Search functionality
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$filterBus = isset($_GET['bus_filter']) ? $_GET['bus_filter'] : '';

// Query to get routes
$query = "
    SELECT r.id, r.source, r.destination, r.departure_time, r.arrival_time, r.price, b.bus_name
    FROM routes r
    JOIN buses b ON r.bus_id = b.id
    WHERE (r.source LIKE ? OR r.destination LIKE ? OR b.bus_name LIKE ?)
    " . ($filterBus ? "AND b.bus_name = ?" : "") . "
    LIMIT ?, ?
";
$stmt = $conn->prepare($query);
if ($filterBus) {
    $stmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm, $filterBus, $offset, $perPage);
} else {
    $stmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm, $offset, $perPage);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total routes for pagination
$countQuery = "
    SELECT COUNT(*) AS total
    FROM routes r
    JOIN buses b ON r.bus_id = b.id
    WHERE (r.source LIKE ? OR r.destination LIKE ? OR b.bus_name LIKE ?)
    " . ($filterBus ? "AND b.bus_name = ?" : "");
$countStmt = $conn->prepare($countQuery);
if ($filterBus) {
    $countStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $filterBus);
} else {
    $countStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalRoutes = $countResult['total'];
$totalPages = ceil($totalRoutes / $perPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .container {
            margin-top: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .actions button {
            margin-right: 5px;
        }

        .pagination {
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="text-center mb-4">Manage Routes</h2>

        <!-- Add Route Button -->
        <div class="d-flex justify-content-between mb-3">
            <form action="manage_routes.php" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search by bus, source, or destination" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <select name="bus_filter" class="form-control me-2">
                    <option value="">All Buses</option>
                    <?php
                    $busQuery = "SELECT DISTINCT bus_name FROM buses";
                    $busResult = $conn->query($busQuery);
                    while ($bus = $busResult->fetch_assoc()):
                    ?>
                        <option value="<?= $bus['bus_name'] ?>" <?= $filterBus === $bus['bus_name'] ? 'selected' : '' ?>><?= $bus['bus_name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <a href="add_route.php" class="btn btn-success">Add Route</a>
        </div>

        <!-- Routes Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure Time</th>
                    <th>Arrival Time</th>
                    <th>Price (NPR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($route = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $route['id'] ?></td>
                        <td><?= $route['bus_name'] ?></td>
                        <td><?= $route['source'] ?></td>
                        <td><?= $route['destination'] ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($route['departure_time'])) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($route['arrival_time'])) ?></td>
                        <td>रू <?= number_format($route['price'], 2) ?></td>
                        <td class="actions">
                            <!-- Edit Button -->
                            <button class="btn btn-warning btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#updateModal" data-route_id="<?= $route['id'] ?>" data-bus_name="<?= $route['bus_name'] ?>" data-source="<?= $route['source'] ?>" data-destination="<?= $route['destination'] ?>" data-departure_time="<?= $route['departure_time'] ?>" data-arrival_time="<?= $route['arrival_time'] ?>" data-price="<?= $route['price'] ?>">Edit</button>

                            <!-- Delete Button -->
                            <button class="btn btn-danger btn-sm delete-btn" data-route_id="<?= $route['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= isset($_GET['search']) ? $_GET['search'] : '' ?>&bus_filter=<?= $filterBus ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= isset($_GET['search']) ? $_GET['search'] : '' ?>&bus_filter=<?= $filterBus ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= isset($_GET['search']) ? $_GET['search'] : '' ?>&bus_filter=<?= $filterBus ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Modal for updating route -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" name="route_id" id="route_id">
                        <!-- Other form fields as in the original code -->
                        <button type="submit" class="btn btn-primary">Update Route</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Populate modal fields when Edit button is clicked
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const routeId = this.getAttribute('data-route_id');
                const busName = this.getAttribute('data-bus_name');
                const source = this.getAttribute('data-source');
                const destination = this.getAttribute('data-destination');
                const departureTime = this.getAttribute('data-departure_time');
                const arrivalTime = this.getAttribute('data-arrival_time');
                const price = this.getAttribute('data-price');

                document.getElementById('route_id').value = routeId;
                document.getElementById('bus_name').value = busName;
                document.getElementById('source').value = source;
                document.getElementById('destination').value = destination;
                document.getElementById('departure_time').value = departureTime;
                document.getElementById('arrival_time').value = arrivalTime;
                document.getElementById('price').value = price;
            });
        });

        // Handle form submission for route update
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('route_actions.php?action=update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload(); // Reload the page to see the changes
                });
        });

        // Handle route deletion
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const routeId = this.getAttribute('data-route_id');
                if (confirm('Are you sure you want to delete this route?')) {
                    fetch('route_actions.php?action=delete', {
                            method: 'POST',
                            body: new URLSearchParams({
                                route_id: routeId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            location.reload(); // Reload the page to remove the route
                        });
                }
            });
        });
    </script>
</body>

</html>