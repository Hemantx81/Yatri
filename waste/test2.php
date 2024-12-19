<?php
session_start();
include('../includes/config.php'); // Database connection
include '../includes/header.php'; // Header

// Fetch all buses from the database
$buses_query = $conn->query("SELECT * FROM buses ORDER BY bus_name ASC");
$buses = $buses_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Buses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2>All Buses</h2>

        <!-- Table of buses -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Image</th>
                    <th>Total Seats</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buses as $bus): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                        <td><img src="../<?php echo $bus['image_path']; ?>" alt="Bus Image" style="width: 100px; height: auto;"></td>
                        <td><?php echo $bus['total_seats']; ?></td>
                        <td>
                            <!-- Update button triggers the modal -->
                            <button class="btn btn-warning btn-sm update-button"
                                data-bs-toggle="modal"
                                data-bs-target="#updateBusModal"
                                data-id="<?php echo $bus['id']; ?>"
                                data-bus-name="<?php echo htmlspecialchars($bus['bus_name']); ?>"
                                data-total-seats="<?php echo $bus['total_seats']; ?>"
                                data-image-path="<?php echo $bus['image_path']; ?>">Update</button>
                            <a href="bus_delete.php?id=<?php echo $bus['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bus?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="add_bus.php" class="btn btn-primary mt-2">Add New Bus</a>
    </div>

    <!-- Update Bus Modal -->
    <div class="modal fade" id="updateBusModal" tabindex="-1" aria-labelledby="updateBusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateBusModalLabel">Update Bus Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateBusForm" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="bus_name">Bus Name:</label>
                            <input type="text" id="bus_name" name="bus_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="total_seats">Total Seats:</label>
                            <input type="number" id="total_seats" name="total_seats" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="bus_image">Bus Image (Optional):</label>
                            <input type="file" id="bus_image" name="bus_image" class="form-control">
                            <img id="existing_image" src="" alt="Bus Image" class="mt-2" style="width: 100px; height: auto;">
                        </div>
                        <input type="hidden" id="bus_id" name="bus_id">
                        <button type="submit" class="btn btn-primary mt-2">Update Bus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // When the update button is clicked, populate the modal with current bus details
        $('#updateBusModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var busId = button.data('id'); // Extract bus ID
            var busName = button.data('bus-name'); // Extract bus name
            var totalSeats = button.data('total-seats'); // Extract total seats
            var imagePath = button.data('image-path'); // Extract image path

            // Populate the modal form fields with current bus details
            $('#bus_name').val(busName);
            $('#total_seats').val(totalSeats);
            $('#existing_image').attr('src', '../' + imagePath);
            $('#bus_id').val(busId);
        });

        // Handle form submission for updating bus
        // Handle form submission for updating bus
        $('#updateBusForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            var formData = new FormData(this);

            $.ajax({
                url: 'bus_update.php', // Your update handler
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Parse the JSON response from the server
                    var res = JSON.parse(response);

                    if (res.status === 'success') {
                        alert(res.message); // Show success message
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert(res.message); // Show error message
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any error to the console for debugging
                    alert('Error occurred. Please try again.');
                }
            });
        });
    </script>
</body>

</html>