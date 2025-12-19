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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Testimonials - SmileCare Dental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Roboto&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Georgia', serif; background-color: #f8f9fa; color: #0d1b2a; }
        h2 { font-family: 'Montserrat', sans-serif; color: #4a6fa5; font-weight: 700; }
        .navbar-custom { background: linear-gradient(90deg, #f1f4f8, #4a6fa5); border-bottom: 2px solid #4a6fa5; }
        .table-container { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 2rem; margin-top: 2rem; }
        .btn-action { background-color: #4a6fa5; color: white; border-radius: 25px; padding: 10px 25px; transition: 0.3s; }
        .btn-action:hover { background-color: #ff8aa2; color: white; transform: translateY(-2px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.html"><strong>SmileCare Dental</strong></a>
        <div class="ms-auto">
            <a href="review.html" class="btn btn-sm btn-outline-primary">Write a Review</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 table-container">
            <div class="text-center mb-4">
                <h2><i class="bi bi-chat-heart"></i> Patient Testimonials</h2>
                <p class="text-muted">See what our community is saying about their smiles.</p>
            </div>

            <?php if (empty($reviewsArray)): ?>
                <div class="alert alert-info text-center">
                    No reviews yet. Be the first to share your experience!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <?php echo displayReviewsTable($reviewsArray); ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="index.html" class="btn btn-secondary me-2">Back to Home</a>
                <a href="review.html" class="btn btn-action shadow-sm">Submit Your Review</a>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 text-center text-muted border-top">
    <p>&copy; 2025 SmileCare Dental Clinic. Quality Care You Can Trust.</p>
</footer>

</body>
</html>

