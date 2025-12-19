<?php
require_once 'Review.class.php';
require_once 'config.php';

// Fetch reviews from database
$sql = "SELECT * FROM reviews ORDER BY created_at DESC";
$result = $conn->query($sql);

// Create array of Review objects
$reviewsArray = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $review = new Review(
            $row['review_id'],
            $row['patient_name'],
            $row['rating'],
            $row['comments'],
            $row['recommend'],
            $row['created_at']
        );
        $reviewsArray[] = $review;
    }
}

// Display using the function
echo "<h2>Patient Reviews</h2>";
echo displayReviewsTable($reviewsArray);

$conn->close();
?>