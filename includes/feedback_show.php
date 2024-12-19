<?php
$bus_id = $_GET['bus_id'] ?? null;
if (!$bus_id) {
    echo "Invalid bus selected.";
    exit();
}

// Fetch feedback for the bus
$query = "SELECT f.rating, f.comment, u.username, f.created_at FROM feedback f
          JOIN users u ON f.user_id = u.id
          WHERE f.bus_id = ? ORDER BY f.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bus_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h3>User Feedback for Bus #<?= htmlspecialchars($bus_id) ?></h3>
<div>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div>
            <strong><?= htmlspecialchars($row['username']) ?> (Rating: <?= $row['rating'] ?>)</strong>
            <p><?= htmlspecialchars($row['comment']) ?></p>
            <p><small>Posted on: <?= $row['created_at'] ?></small></p>
        </div>
    <?php endwhile; ?>
</div>