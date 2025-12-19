<?php
// process_review.php 

// Database connection
$conn = new mysqli("localhost", "root", "", "smilecare_db");
if ($conn->connect_error) {
    die("Database connection failed. Run install.php first.");
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['patient_name'] ?? 'Anonymous';
    $rating = $_POST['overall_rating'];
    $comments = $_POST['review_comments'];
    $recommend = $_POST['recommend'] ?? 'Yes';
    
    // Insert into database
    $sql = "INSERT INTO reviews (patient_name, rating, comments, recommend) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $name, $rating, $comments, $recommend);
    
    $success = $stmt->execute();
    $stmt->close();
    
    // Store data for display
    $review_data = [
        'name' => $name,
        'rating' => $rating,
        'comments' => $comments,
        'recommend' => $recommend,
        'time' => date('Y-m-d H:i:s')
    ];
} else {
    $success = false;
    $review_data = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Submission - SmileCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Georgia', serif; background-color: #f8f9fa; }
        .review-table th { background-color: #4a6fa5; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background: linear-gradient(90deg, #f1f4f8, #4a6fa5);">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <img src="logoo.png" alt="Logo" width="80" height="80">
        </a>
        <div>
            <a href="review.html" class="btn btn-outline-light me-2">Back to Reviews</a>
            <a href="index.html" class="btn btn-light">Home</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Review Submission Result</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($success && $review_data): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading">✅ Review Submitted Successfully!</h4>
                            <p>Thank you for sharing your experience with SmileCare Dental.</p>
                            <hr>
                            <p class="mb-0">Your review has been saved to our database.</p>
                        </div>
                        
                        <h4 class="mt-4 mb-3">Your Review Details:</h4>
                        <table class="table table-bordered review-table">
                            <tr><th width="30%">Patient Name</th><td><?php echo htmlspecialchars($review_data['name']); ?></td></tr>
                            <tr><th>Rating</th><td><?php echo $review_data['rating']; ?> out of 5</td></tr>
                            <tr><th>Comments</th><td><?php echo htmlspecialchars($review_data['comments']); ?></td></tr>
                            <tr><th>Recommend to Others</th><td><?php echo $review_data['recommend']; ?></td></tr>
                            <tr><th>Submission Time</th><td><?php echo $review_data['time']; ?></td></tr>
                        </table>
                        
                        <div class="alert alert-info mt-4">
                            <h5>📊 Review Added to Database</h5>
                            <p>Your review is now stored in the 'reviews' table in our MySQL database.</p>
                        </div>
                        
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success): ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">❌ Submission Failed</h4>
                            <p>There was an error saving your review. Please try again.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading">⚠️ No Review Data</h4>
                            <p>Please submit a review from the <a href="review.html">Review Page</a> first.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="review.html" class="btn btn-primary px-4">Submit Another Review</a>
                            <a href="index.html" class="btn btn-secondary px-4">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Database Preview -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Latest Reviews in Database</h5>
                </div>
                <div class="card-body">
                    <?php
                   
                    $sql = "SELECT patient_name, rating, comments, created_at 
                            FROM reviews 
                            ORDER BY created_at DESC 
                            LIMIT 3";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        echo '<table class="table table-sm table-hover">';
                        echo '<thead><tr><th>Name</th><th>Rating</th><th>Comments</th><th>Date</th></tr></thead>';
                        echo '<tbody>';
                        while ($row = $result->fetch_assoc()) {
                            $stars = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['patient_name']) . '</td>';
                            echo '<td><span class="text-warning">' . $stars . '</span></td>';
                            echo '<td>' . htmlspecialchars(substr($row['comments'], 0, 40)) . '...</td>';
                            echo '<td>' . $row['created_at'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '<p class="text-muted small">Showing 3 most recent reviews from database</p>';
                    } else {
                        echo '<p class="text-muted">No reviews in database yet.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 bg-light">
    <div class="container text-center">
        <p class="mb-0">© 2025 SmileCare Dental Clinic. Review Processing System</p>
        <p class="text-muted small">PHP Form Processing - COMP3700 Project Part 4</p>
    </div>
</footer>
</body>
</html>
<?php
// Close connection 
if (isset($conn)) {
    $conn->close();
}
?>