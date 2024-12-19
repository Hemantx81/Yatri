<?php
session_start();
include('../includes/config.php'); // Database connection
include '../includes/header.php'; // Header

// Fetch routes with optional filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;

// Pagination variables
$limit = 10; // Number of routes per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// SQL query with search and filters
$query_base = "SELECT routes.*, buses.bus_name FROM routes 
               JOIN buses ON routes.bus_id = buses.id 
               WHERE 1=1";

if (!empty($search_query)) {
    $query_base .= " AND (routes.source LIKE ? OR routes.destination LIKE ?)";
}
if ($filter_bus_id > 0) {
    $query_base .= " AND routes.bus_id = ?";
}

$query_base .= " ORDER BY routes.id DESC LIMIT ? OFFSET ?";

// Prepare query and bind parameters
$stmt = $conn->prepare($query_base);
if (!empty($search_query) && $filter_bus_id > 0) {
    $like_query = "%" . $search_query . "%";
    $stmt->bind_param("ssi", $like_query, $like_query, $filter_bus_id, $limit, $offset);
} elseif (!empty($search_query)) {
    $like_query = "%" . $search_query . "%";
    $stmt->bind_param("ssii", $like_query, $like_query, $limit, $offset);
} elseif ($filter_bus_id > 0) {
    $stmt->bind_param("iii", $filter_bus_id, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$routes = $result->fetch_all(MYSQLI_ASSOC);

// Fetch total rows for pagination
$count_query = "SELECT COUNT(*) as total FROM routes WHERE 1=1";
if (!empty($search_query)) {
    $count_query .= " AND (source LIKE '%$search_query%' OR destination LIKE '%$search_query%')";
}
if ($filter_bus_id > 0) {
    $count_query .= " AND bus_id = $filter_bus_id";
}
$total_result = $conn->query($count_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch all buses for the filter dropdown
$buses_query = $conn->query("SELECT id, bus_name FROM buses ORDER BY bus_name ASC");
$buses = $buses_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Routes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Routes</h2>

        <!-- Search and Filter Form -->
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control" placeholder="Search by Source/Destination">
            </div>
            <div class="col-md-4">
                <select name="bus_id" class="form-control">
                    <option value="0">All Buses</option>
                    <?php foreach ($buses as $bus): ?>
                        <option value="<?php echo $bus['id']; ?>" <?php echo ($filter_bus_id == $bus['id']) ? 'selected' : ''; ?>>
                            <?php echo $bus['bus_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="view_routes.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Routes Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bus</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($routes) > 0): ?>
                    <?php foreach ($routes as $index => $route): ?>
                        <tr>
                            <td><?php echo $index + 1 + $offset; ?></td>
                            <td><?php echo htmlspecialchars($route['bus_name']); ?></td>
                            <td><?php echo htmlspecialchars($route['source']); ?></td>
                            <td><?php echo htmlspecialchars($route['destination']); ?></td>
                            <td><?php echo date("Y-m-d H:i", strtotime($route['departure_time'])); ?></td>
                            <td><?php echo date("Y-m-d H:i", strtotime($route['arrival_time'])); ?></td>
                            <td><?php echo number_format($route['price'], 2); ?></td>
                            <td>
                                <a href="edit_route.php?id=<?php echo $route['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No routes found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&bus_id=<?php echo $filter_bus_id; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</body>

</html>