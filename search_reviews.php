<?php
// search_reviews.php - Search functionality for reviews

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Reviews - SmileCare Dental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Georgia', serif; background-color: #f8f9fa; }
        .search-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .result-card { transition: all 0.3s; }
        .result-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background: linear-gradient(90deg, #f1f4f8, #4a6fa5);">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <img src="logoo.png" alt="Logo" width="80" height="80">
        </a>
        <a href="review.html" class="btn btn-outline-dark">Back to Reviews</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Search Header -->
            <div class="search-box text-white p-5 rounded-3 mb-4">
                <h1 class="display-5 fw-bold">Search Patient Reviews</h1>
                <p class="lead">Find reviews by name, rating, or keywords in comments</p>
            </div>
            
            <!-- Search Form -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Search Criteria</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search by Name or Comments:</label>
                                <input type="text" name="keyword" class="form-control" 
                                       placeholder="Enter name or keyword..." 
                                       value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Minimum Rating:</label>
                                <select name="min_rating" class="form-select">
                                    <option value="">Any Rating</option>
                                    <option value="5" <?php echo (isset($_GET['min_rating']) && $_GET['min_rating'] == '5') ? 'selected' : ''; ?>>★★★★★ (5)</option>
                                    <option value="4" <?php echo (isset($_GET['min_rating']) && $_GET['min_rating'] == '4') ? 'selected' : ''; ?>>★★★★☆ (4+)</option>
                                    <option value="3" <?php echo (isset($_GET['min_rating']) && $_GET['min_rating'] == '3') ? 'selected' : ''; ?>>★★★☆☆ (3+)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Recommendation:</label>
                                <select name="recommend" class="form-select">
                                    <option value="">Any</option>
                                    <option value="Yes" <?php echo (isset($_GET['recommend']) && $_GET['recommend'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo (isset($_GET['recommend']) && $_GET['recommend'] == 'No') ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary btn-lg px-4">
                                        <i class="bi bi-search"></i> Search Reviews
                                    </button>
                                    <a href="search_reviews.php" class="btn btn-secondary btn-lg px-4">Clear Search</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Search Results -->
            <?php
            // Process search query
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['keyword']) || isset($_GET['min_rating']) || isset($_GET['recommend']))) {
                
                // Build SQL query based on search criteria
                $sql = "SELECT * FROM reviews WHERE 1=1";
                $params = [];
                $types = "";
                
                if (!empty($_GET['keyword'])) {
                    $sql .= " AND (patient_name LIKE ? OR comments LIKE ?)";
                    $keyword = "%" . $_GET['keyword'] . "%";
                    $params[] = $keyword;
                    $params[] = $keyword;
                    $types .= "ss";
                }
                
                if (!empty($_GET['min_rating'])) {
                    $sql .= " AND rating >= ?";
                    $params[] = $_GET['min_rating'];
                    $types .= "i";
                }
                
                if (!empty($_GET['recommend'])) {
                    $sql .= " AND recommend = ?";
                    $params[] = $_GET['recommend'];
                    $types .= "s";
                }
                
                $sql .= " ORDER BY created_at DESC";
                
                // Prepare and execute statement
                $stmt = $conn->prepare($sql);
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Display results
                echo '<div class="card shadow">';
                echo '<div class="card-header bg-success text-white">';
                echo '<h4 class="mb-0">Search Results</h4>';
                echo '</div>';
                echo '<div class="card-body">';
                
                if ($result->num_rows > 0) {
                    echo '<p class="text-muted">Found ' . $result->num_rows . ' review(s)</p>';
                    
                    echo '<table class="table table-hover">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Patient Name</th>';
                    echo '<th class="text-center">Rating</th>';
                    echo '<th>Comments</th>';
                    echo '<th>Recommend</th>';
                    echo '<th>Date</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    while ($row = $result->fetch_assoc()) {
                        // Get star rating
                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            $stars .= ($i <= $row['rating']) ? '★' : '☆';
                        }
                        
                        echo '<tr class="result-card">';
                        echo '<td>' . htmlspecialchars($row['patient_name']) . '</td>';
                        echo '<td class="text-center">';
                        echo '<span class="text-warning" title="' . $row['rating'] . '/5">' . $stars . '</span>';
                        echo '<br><small class="text-muted">' . $row['rating'] . '/5</small>';
                        echo '</td>';
                        echo '<td>' . htmlspecialchars(substr($row['comments'], 0, 80)) . '...</td>';
                        echo '<td>';
                        $badgeClass = ($row['recommend'] == 'Yes') ? 'badge bg-success' : 'badge bg-secondary';
                        echo '<span class="' . $badgeClass . '">' . $row['recommend'] . '</span>';
                        echo '</td>';
                        echo '<td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<div class="alert alert-warning text-center py-4">';
                    echo '<h5>No reviews found matching your criteria</h5>';
                    echo '<p class="mb-0">Try different search terms or clear the search to see all reviews.</p>';
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
                
                $stmt->close();
            }
            
            $conn->close();
            ?>
            
            <!-- Quick Stats -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Search Tips</h5>
                            <ul class="text-start">
                                <li>Search by patient name</li>
                                <li>Search keywords in comments</li>
                                <li>Filter by minimum rating</li>
                                <li>Filter by recommendation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Try These Searches</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <a href="search_reviews.php?keyword=excellent&min_rating=5" class="btn btn-outline-primary w-100">
                                        "Excellent" + 5★
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="search_reviews.php?recommend=Yes" class="btn btn-outline-success w-100">
                                        Recommended: Yes
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="search_reviews.php?min_rating=4" class="btn btn-outline-warning w-100">
                                        4+ Star Reviews
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="mt-4 text-center">
                <div class="btn-group" role="group">
                    <a href="display_reviews.php" class="btn btn-primary">View All Reviews</a>
                    <a href="admin_manage.php" class="btn btn-warning">Admin Management</a>
                    <a href="review.html" class="btn btn-success">Submit Review</a>
                    <a href="index.html" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 bg-light">
    <div class="container text-center">
        <p class="mb-0">© 2025 SmileCare Dental Clinic. All Rights Reserved.</p>
        <p class="text-muted small">Search functionality using PHP and MySQL - COMP3700 Project Part 4</p>
    </div>
</footer>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>