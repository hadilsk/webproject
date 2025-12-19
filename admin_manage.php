<?php
// admin_manage.php - Admin panel for managing reviews (INSERT, UPDATE, DELETE)

session_start();
require_once 'config.php';

// Simple authentication system
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Simple hardcoded credentials (in real app, use database)
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
        } else {
            $login_error = "Invalid username or password";
        }
    }
    
    // Show login form if not logged in
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Admin Login - SmileCare</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: 'Georgia', serif; background-color: #f8f9fa; }
                .login-box { max-width: 400px; margin: 100px auto; }
            </style>
        </head>
        <body>
        <div class="container">
            <div class="login-box">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="mb-0">Admin Login</h3>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted text-center mb-4">Enter credentials to access admin panel</p>
                        
                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger"><?php echo $login_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required 
                                       placeholder="Enter admin username">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required 
                                       placeholder="Enter password">
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="index.html" class="btn btn-sm btn-outline-secondary">Back to Home</a>
                        </div>
                        
                        <div class="mt-4 alert alert-info">
                            <small><strong>Demo Credentials:</strong><br>
                            Username: <code>admin</code><br>
                            Password: <code>admin123</code></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_manage.php");
    exit();
}

// Initialize message variable
$message = '';

// Handle INSERT operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {
    $name = !empty($_POST['new_name']) ? trim($_POST['new_name']) : 'Anonymous';
    $rating = (int)$_POST['new_rating'];
    $comments = trim($_POST['new_comments']);
    $recommend = $_POST['new_recommend'];
    
    if (!empty($comments) && $rating >= 1 && $rating <= 5) {
        $sql = "INSERT INTO reviews (patient_name, rating, comments, consent, recommend) 
                VALUES (?, ?, ?, 1, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siss", $name, $rating, $comments, $recommend);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ New review inserted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error inserting review: ' . $conn->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">⚠️ Please provide valid review data</div>';
    }
}

// Handle UPDATE operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int)$_POST['update_id'];
    $rating = (int)$_POST['update_rating'];
    $comments = trim($_POST['update_comments']);
    $recommend = $_POST['update_recommend'];
    
    if ($id > 0 && !empty($comments) && $rating >= 1 && $rating <= 5) {
        $sql = "UPDATE reviews SET rating = ?, comments = ?, recommend = ? WHERE review_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $rating, $comments, $recommend, $id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Review updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error updating review: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Handle DELETE operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int)$_POST['delete_id'];
    
    if ($id > 0) {
        $sql = "DELETE FROM reviews WHERE review_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Review deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error deleting review: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// Fetch all reviews for display
$sql = "SELECT * FROM reviews ORDER BY review_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Manage Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Georgia', serif; background-color: #f8f9fa; }
        .admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .operation-card { border-left: 4px solid; }
        .insert-card { border-left-color: #28a745; }
        .update-card { border-left-color: #ffc107; }
        .delete-card { border-left-color: #dc3545; }
        .action-buttons { min-width: 150px; }
    </style>
</head>
<body>
<!-- Admin Header -->
<div class="admin-header text-white py-4 mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-6 fw-bold"><i class="bi bi-shield-lock"></i> Admin Panel</h1>
                <p class="lead mb-0">Manage Patient Reviews (INSERT, UPDATE, DELETE)</p>
            </div>
            <div class="text-end">
                <p class="mb-1">Logged in as: <strong><?php echo $_SESSION['admin_username']; ?></strong></p>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?php echo $message; ?>
    
    <!-- INSERT Operation Card -->
    <div class="card shadow mb-4 operation-card insert-card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-plus-circle"></i> INSERT New Review</h4>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Patient Name:</label>
                    <input type="text" name="new_name" class="form-control" placeholder="Optional, defaults to Anonymous">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rating (1-5):</label>
                    <select name="new_rating" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Very Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Very Poor</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Comments:</label>
                    <input type="text" name="new_comments" class="form-control" required 
                           placeholder="Enter review comments...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Recommend:</label>
                    <select name="new_recommend" class="form-select" required>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="insert" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i> Insert
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Current Reviews with UPDATE/DELETE -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-list-ul"></i> Current Reviews (UPDATE/DELETE)</h4>
        </div>
        <div class="card-body p-0">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Patient Name</th>
                                <th class="text-center">Rating</th>
                                <th>Comments</th>
                                <th>Recommend</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <td>
                                        #<?php echo $row['review_id']; ?>
                                        <input type="hidden" name="update_id" value="<?php echo $row['review_id']; ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['review_id']; ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="update_name" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($row['patient_name']); ?>" 
                                               placeholder="Anonymous">
                                    </td>
                                    <td class="text-center">
                                        <select name="update_rating" class="form-select form-select-sm">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?php echo $i; ?>" 
                                                    <?php echo ($i == $row['rating']) ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?> 
                                                    <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="update_comments" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($row['comments']); ?>" required>
                                    </td>
                                    <td>
                                        <select name="update_recommend" class="form-select form-select-sm">
                                            <option value="Yes" <?php echo ($row['recommend'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="No" <?php echo ($row['recommend'] == 'No') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="submit" name="update" class="btn btn-warning" 
                                                    title="Update this review">
                                                <i class="bi bi-pencil-square"></i> Update
                                            </button>
                                            <button type="submit" name="delete" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete review #<?php echo $row['review_id']; ?>?')"
                                                    title="Delete this review">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="alert alert-info mx-3">
                        <h5>No reviews found in database</h5>
                        <p class="mb-0">Use the INSERT form above to add new reviews.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Database Statistics -->
    <?php
    $stats_sql = "SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN recommend = 'Yes' THEN 1 ELSE 0 END) as recommend_count,
        SUM(CASE WHEN consent = 1 THEN 1 ELSE 0 END) as consent_count
        FROM reviews";
    
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    ?>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Reviews</h5>
                    <h2 class="text-primary"><?php echo $stats['total_reviews']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h5 class="card-title text-muted">Avg Rating</h5>
                    <h2 class="text-warning"><?php echo number_format($stats['avg_rating'], 1); ?>/5</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h5 class="card-title text-muted">Recommend</h5>
                    <h2 class="text-success"><?php echo $stats['recommend_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h5 class="card-title text-muted">With Consent</h5>
                    <h2 class="text-info"><?php echo $stats['consent_count']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="mt-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="display_reviews.php" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> View Public Reviews
                        </a>
                        <a href="search_reviews.php" class="btn btn-outline-success">
                            <i class="bi bi-search"></i> Search Reviews
                        </a>
                    </div>
                    <div>
                        <a href="review.html" class="btn btn-outline-info">
                            <i class="bi bi-plus-circle"></i> Submit New Review (Public)
                        </a>
                        <a href="index.html" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 bg-light">
    <div class="container text-center">
        <p class="mb-0">© 2025 SmileCare Dental Clinic. Admin Panel - COMP3700 Project Part 4</p>
        <p class="text-muted small">
            Demonstrates INSERT, UPDATE, DELETE operations with user-friendly interface
        </p>
    </div>
</footer>
</body>
</html>
<?php
$conn->close();
?>