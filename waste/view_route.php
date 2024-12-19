<?php
session_start();
include('../includes/config.php'); // Database connection
include '../includes/header.php'; // Header

// Pagination variables
$limit = 10; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $limit;

// Fetch total number of routes
$total_query = $conn->query("SELECT COUNT(*) AS total FROM routes");
$total_rows = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch routes with pagination
$query = $conn->prepare("SELECT routes.id, buses.bus_name, routes.source, routes.destination, 
                         routes.departure_time, routes.arrival_time, routes.price
                         FROM routes
                         INNER JOIN buses ON routes.bus_id = buses.id
                         ORDER BY routes.departure_time ASC
                         LIMIT ?, ?");
$query->bind_param("ii", $start, $limit);
$query->execute();
$result = $query->get_result();
$routes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Routes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert for enhanced dialogs -->
</head>

<body>
    <div class="container mt-5">
        <h2>View Routes</h2>
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search routes (e.g., source, destination, bus name)">
        </div>

        <!-- Routes Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Departure Time</th>
                    <th>Arrival Time</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="routesTable">
                <?php foreach ($routes as $index => $route): ?>
                    <tr>
                        <td><?php echo ($start + $index + 1); ?></td>
                        <td><?php echo $route['bus_name']; ?></td>
                        <td><?php echo $route['source']; ?></td>
                        <td><?php echo $route['destination']; ?></td>
                        <td><?php echo date("Y-m-d H:i", strtotime($route['departure_time'])); ?></td>
                        <td><?php echo date("Y-m-d H:i", strtotime($route['arrival_time'])); ?></td>
                        <td><?php echo number_format($route['price'], 2); ?></td>
                        <td>
                            <a href="#" class="btn btn-danger btn-sm"
                                onclick="confirmDelete('<?php echo $route['id']; ?>')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <script>
        // SweetAlert for confirmation dialog
        function confirmDelete(routeId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `view_routes.php?delete=${routeId}`;
                }
            });
        }

        // Client-side table filtering
        document.getElementById('searchInput').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#routesTable tr');
            rows.forEach(row => {
                const cells = Array.from(row.getElementsByTagName('td'));
                const match = cells.some(cell => cell.textContent.toLowerCase().includes(filter));
                row.style.display = match ? '' : 'none';
            });
        });
    </script>
</body>

</html>