<?php
session_start();
include('../includes/config.php'); // Database connection
include '../includes/header.php'; // Header

// Initialize variables
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
    $stmt->bind_param("ssiii", $like_query, $like_query, $filter_bus_id, $limit, $offset);
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
    <title>Manage Routes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Manage Routes</h2>

        <!-- Search and Filter Form -->
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <input type="text" id="busSearch" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control" placeholder="Search by Bus/Source/Destination">
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
                <a href="route.php" class="btn btn-secondary">Reset</a>
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
            <tbody id="routesTable">
                <?php if (count($routes) > 0): ?>
                    <?php foreach ($routes as $index => $route): ?>
                        <tr id="route-<?php echo $route['id']; ?>">
                            <td><?php echo $index + 1 + $offset; ?></td>
                            <td><?php echo htmlspecialchars($route['bus_name']); ?></td>
                            <td><?php echo htmlspecialchars($route['source']); ?></td>
                            <td><?php echo htmlspecialchars($route['destination']); ?></td>
                            <td><?php echo date("Y-m-d H:i", strtotime($route['departure_time'])); ?></td>
                            <td><?php echo date("Y-m-d H:i", strtotime($route['arrival_time'])); ?></td>
                            <td><?php echo number_format($route['price'], 2); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editRoute(<?php echo htmlspecialchars(json_encode($route)); ?>)">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteRoute(<?php echo $route['id']; ?>)">Delete</button>
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

    <script>
        // Handle edit
        // Handle the edit functionality
        function editRoute(route) {
            // Populate modal with the route data
            $('#routeId').val(route.id);
            $('#source').val(route.source);
            $('#destination').val(route.destination);
            $('#departure_time').val(route.departure_time);
            $('#arrival_time').val(route.arrival_time);
            $('#price').val(route.price);
            $('#bus_id').val(route.bus_id); // Populate bus dropdown
            $('#editModal').modal('show'); // Show modal
        }

        // Handle form submission via AJAX
        $('#editRouteForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            let formData = $(this).serialize(); // Serialize the form data

            $.ajax({
                url: 'update_route.php', // Your PHP handler for updating the route
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Success: Update the table with new values
                        let route = response.route;
                        $('#route-' + route.id).html(`
                    <td>${route.id}</td>
                    <td>${route.bus_name}</td>
                    <td>${route.source}</td>
                    <td>${route.destination}</td>
                    <td>${route.departure_time}</td>
                    <td>${route.arrival_time}</td>
                    <td>${route.price}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editRoute(${JSON.stringify(route)})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteRoute(${route.id})">Delete</button>
                    </td>
                `);
                        $('#editModal').modal('hide'); // Close the modal
                        Swal.fire('Success', response.message, 'success');
                    } else {
                        // Error: Display the error message
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'There was an issue updating the route.', 'error');
                }
            });
        });


        // Handle delete
        function deleteRoute(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the route!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('delete_route.php', {
                        id
                    }, function(response) {
                        if (response.success) {
                            $(`#route-${id}`).remove();
                            Swal.fire('Deleted!', response.message, 'success');
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    }, 'json');
                }
            });
        }

        // Autocomplete for bus search
        $(function() {
            $('#busSearch').autocomplete({
                source: '../search.php',
                minLength: 2,
                select: function(event, ui) {
                    $('#busSearch').val(ui.item.value);
                }
            });
        });
    </script>
</body>

</html>