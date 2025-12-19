<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : 'Not provided';
    $message = trim($_POST['message']);
    
    // Store in session
    $_SESSION['last_contact'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Insert into database
    $sql = "INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $message);
    
    if ($stmt->execute()) {
        $_SESSION['contact_success'] = true;
    } else {
        $_SESSION['contact_error'] = "Database error";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get session data
$success = $_SESSION['contact_success'] ?? false;
$error = $_SESSION['contact_error'] ?? false;
$contact_data = $_SESSION['last_contact'] ?? null;

// Clear session
unset($_SESSION['contact_success']);
unset($_SESSION['contact_error']);
if ($success) {
    unset($_SESSION['last_contact']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Message - SmileCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background: linear-gradient(90deg, #f1f4f8, #4a6fa5);">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <img src="logoo.png" alt="Logo" width="80" height="80">
        </a>
        <a href="contact.html" class="btn btn-outline-light">Back to Contact</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0">Message Status</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($success && $contact_data): ?>
                        <div class="alert alert-success">
                            <h4>✅ Message Sent Successfully!</h4>
                            <p>We'll respond within 24 hours.</p>
                        </div>
                        
                        <h4>Your Message:</h4>
                        <table class="table table-bordered">
                            <tr><th>Name</th><td><?php echo htmlspecialchars($contact_data['name']); ?></td></tr>
                            <tr><th>Email</th><td><?php echo htmlspecialchars($contact_data['email']); ?></td></tr>
                            <tr><th>Phone</th><td><?php echo htmlspecialchars($contact_data['phone']); ?></td></tr>
                            <tr><th>Message</th><td><?php echo nl2br(htmlspecialchars($contact_data['message'])); ?></td></tr>
                            <tr><th>Sent</th><td><?php echo $contact_data['time']; ?></td></tr>
                        </table>
                        
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger">
                            <h4>❌ Message Not Sent</h4>
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h4>No Message Data</h4>
                            <p>Please send a message from the contact page.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="contact.html" class="btn btn-primary">Send Another</a>
                        <a href="index.html" class="btn btn-secondary">Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>