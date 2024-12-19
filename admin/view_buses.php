<?php
session_start();
include('../includes/config.php'); // Include database connection


// Pagination and Search Logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$is_ac = isset($_GET['is_ac']) ? $_GET['is_ac'] : '';
$is_wifi = isset($_GET['is_wifi']) ? $_GET['is_wifi'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; // Buses per page
$offset = ($page - 1) * $limit;

// Build query dynamically for search and filters
$query = "SELECT * FROM buses WHERE bus_name LIKE ?";
$params = ['%' . $search . '%'];
$types = 's';

if ($is_ac !== '') {
    $query .= " AND is_ac = ?";
    $params[] = $is_ac;
    $types .= 'i';
}
if ($is_wifi !== '') {
    $query .= " AND is_wifi = ?";
    $params[] = $is_wifi;
    $types .= 'i';
}

$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$buses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total buses for pagination
$count_query = "SELECT COUNT(*) AS count FROM buses WHERE bus_name LIKE ?";
$count_params = ['%' . $search . '%'];
$count_types = 's';

if ($is_ac !== '') {
    $count_query .= " AND is_ac = ?";
    $count_params[] = $is_ac;
    $count_types .= 'i';
}
if ($is_wifi !== '') {
    $count_query .= " AND is_wifi = ?";
    $count_params[] = $is_wifi;
    $count_types .= 'i';
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_buses = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_buses / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Buses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container mt-4">
        <a href="add_bus.php" class="btn btn-success mb-3">➕ Add New Bus</a>
        <h2>🚍 Manage Buses</h2>

        <!-- Search and Filters -->
        <form class="mb-4" method="GET">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="🔍 Search by bus name...">
                </div>
                <div class="col-md-2">
                    <select name="is_ac" class="form-select">
                        <option value="">AC (All)</option>
                        <option value="1" <?= $is_ac === '1' ? 'selected' : '' ?>>AC</option>
                        <option value="0" <?= $is_ac === '0' ? 'selected' : '' ?>>Non-AC</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="is_wifi" class="form-select">
                        <option value="">Wi-Fi (All)</option>
                        <option value="1" <?= $is_wifi === '1' ? 'selected' : '' ?>>Wi-Fi</option>
                        <option value="0" <?= $is_wifi === '0' ? 'selected' : '' ?>>No Wi-Fi</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">🔍 Apply Filters</button>
                </div>
            </div>
        </form>

        <!-- Buses Table -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Bus Name</th>
                    <th>Image</th>
                    <th>Total Seats</th>
                    <th>AC</th>
                    <th>Wi-Fi</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($buses)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No buses found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($buses as $bus): ?>
                        <tr id="bus-row-<?= $bus['id'] ?>">
                            <td><?= htmlspecialchars($bus['bus_name']) ?></td>
                            <td>
                                <img src="../<?= $bus['image_path'] ?>" alt="Bus Image" class="img-thumbnail" style="width: 100px; height: 75px;">
                            </td>
                            <td><?= $bus['total_seats'] ?></td>
                            <td><?= $bus['is_ac'] ? '✔️' : '❌' ?></td>
                            <td><?= $bus['is_wifi'] ? '✔️' : '❌' ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm update-button" data-bs-toggle="modal" data-bs-target="#updateBusModal"
                                    data-id="<?= $bus['id'] ?>" data-name="<?= htmlspecialchars($bus['bus_name']) ?>"
                                    data-seats="<?= $bus['total_seats'] ?>" data-image="<?= $bus['image_path'] ?>"
                                    data-ac="<?= $bus['is_ac'] ?>" data-wifi="<?= $bus['is_wifi'] ?>">✏️ Update</button>
                                <button class="btn btn-danger btn-sm delete-button" data-id="<?= $bus['id'] ?>">🗑️ Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&is_ac=<?= htmlspecialchars($is_ac) ?>&is_wifi=<?= htmlspecialchars($is_wifi) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateBusModal" tabindex="-1" aria-labelledby="updateBusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updateBusForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateBusModalLabel">Update Bus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="update-id">
                        <div class="mb-3">
                            <label for="update-name" class="form-label">Bus Name</label>
                            <input type="text" class="form-control" id="update-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="update-seats" class="form-label">Total Seats</label>
                            <input type="number" class="form-control" id="update-seats" name="seats" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Facilities</label>
                            <div>
                                <input type="checkbox" id="update-ac" name="is_ac"> AC
                                <input type="checkbox" id="update-wifi" name="is_wifi"> Wi-Fi
                            </div>
                        </div>
                        <div class="mb-3">
                            <img id="current-image-preview" src="" alt="Current Bus Image" class="img-thumbnail" style="max-height: 150px; display: none;">
                            <input type="file" class="form-control" id="update-image" name="image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Populate the update modal with existing bus data
            $(document).on('click', '.update-button', function() {
                const busId = $(this).data('id');
                const busName = $(this).data('name');
                const totalSeats = $(this).data('seats');
                const imagePath = $(this).data('image');

                // Populate modal fields
                $('#update-id').val(busId);
                $('#update-name').val(busName);
                $('#update-seats').val(totalSeats);
                $('#update-image').val(''); // Clear file input field

                // Optionally show a preview of the current image (if required)
                if (imagePath) {
                    $('#current-image-preview').attr('src', '../' + imagePath).show();
                } else {
                    $('#current-image-preview').hide();
                }
            });

            // Handle update form submission
            $('#updateBusForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: 'bus_update.php', // Your backend script for updating bus data
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            var res = JSON.parse(response);

                            if (res.status === 'success') {
                                alert(res.message); // Show success notification
                                location.reload(); // Reload to reflect changes
                            } else {
                                alert(res.message); // Show error notification
                            }
                        } catch (error) {
                            console.error('Invalid JSON response:', error);
                            alert('An unexpected error occurred. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        alert('Failed to update the bus. Please try again.');
                    }
                });
            });
        });


        // Handle delete button click
        $(document).on('click', '.delete-button', function() {
            if (confirm('Are you sure you want to delete this bus?')) {
                var busId = $(this).data('id');

                $.ajax({
                    url: 'bus_delete.php',
                    type: 'POST',
                    data: {
                        id: busId
                    },
                    success: function(response) {
                        try {
                            var res = JSON.parse(response);

                            if (res.status === 'success') {
                                alert(res.message); // Show success notification
                                $('#bus-row-' + busId).remove(); // Remove the row from the table
                            } else {
                                alert(res.message); // Show error notification
                            }
                        } catch (error) {
                            console.error('Invalid JSON response:', error);
                            alert('An unexpected error occurred. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        alert('Failed to delete the bus. Please try again later.');
                    }
                });
            }
        });
    </script>
</body>

</html>