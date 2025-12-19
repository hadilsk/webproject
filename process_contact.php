<?php
require_once 'config.php';

session_start();

// Initialize error array
$errors = [];
$contact_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========================================
    // 1. GET AND SANITIZE INPUT DATA
    // ========================================
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Store data for potential redisplay
    $contact_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message
    ];
    
    // ========================================
    // 2. SERVER-SIDE VALIDATION - REQUIRED FIELDS
    // ========================================
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    }
    
    // ========================================
    // 3. REGULAR EXPRESSION VALIDATIONS
    // ========================================
    
    // Validate name (letters, spaces, hyphens only)
    if (!empty($name) && !preg_match('/^[a-zA-Z\s\-]{2,50}$/', $name)) {
        $errors[] = "Name must be 2-50 characters and contain only letters, spaces, or hyphens.";
    }
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    
    // Validate phone (8-15 digits, optional)
    if (!empty($phone)) {
        $numericPhone = preg_replace('/\D/', '', $phone);
        if (!preg_match('/^[0-9]{8,15}$/', $numericPhone)) {
            $errors[] = "Phone number must be 8-15 digits.";
        }
    }
    
    // ========================================
    // 4. LENGTH AND RANGE CONSTRAINTS
    // ========================================
    
    if (!empty($name) && (strlen($name) < 2 || strlen($name) > 50)) {
        $errors[] = "Name must be between 2 and 50 characters.";
    }
    
    if (!empty($message) && (strlen($message) < 10 || strlen($message) > 500)) {
        $errors[] = "Message must be between 10 and 500 characters.";
    }
    
    // ========================================
    // 5. ADDITIONAL SECURITY CHECKS
    // ========================================
    
    // Check for common spam patterns
    $spamKeywords = ['viagra', 'cialis', 'casino', 'lottery', 'winner'];
    $messageLower = strtolower($message);
    foreach ($spamKeywords as $keyword) {
        if (strpos($messageLower, $keyword) !== false) {
            $errors[] = "Your message contains prohibited content.";
            break;
        }
    }
    
    // Check for excessive URLs in message
    if (substr_count($message, 'http') > 2) {
        $errors[] = "Your message contains too many links.";
    }
    
    // ========================================
    // 6. PROCESS IF NO ERRORS
    // ========================================
    
    if (empty($errors)) {
        // Set phone to 'Not provided' if empty
        if (empty($phone)) {
            $phone = 'Not provided';
        }
        
        // Insert into database
        $sql = "INSERT INTO contact_messages (name, email, phone, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $phone, $message);
            
            if ($stmt->execute()) {
                $_SESSION['contact_success'] = true;
                $_SESSION['last_contact'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => $message,
                    'time' => date('Y-m-d H:i:s')
                ];
            } else {
                $errors[] = "Database error: Unable to save your message. Please try again.";
            }
            
            $stmt->close();
        } else {
            $errors[] = "Database connection error. Please try again later.";
        }
    }
    
    // Store errors in session if any
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_data'] = $contact_data;
    }
    
    $conn->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ========================================
// DISPLAY RESULTS
// ========================================

// Get session data
$success = $_SESSION['contact_success'] ?? false;
$errors = $_SESSION['contact_errors'] ?? [];
$contact_data = $_SESSION['last_contact'] ?? null;

// Clear session
unset($_SESSION['contact_success']);
unset($_SESSION['contact_errors']);
if ($success) {
    unset($_SESSION['last_contact']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Message Status - SmileCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Georgia', serif;
            background-color: #f8f9fa;
        }
        h1, h2, h3, h4 {
            font-family: 'Montserrat', sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(90deg, #f1f4f8, #4a6fa5);
            padding: 1rem 0;
        }
        .card {
            border-radius: 10px;
            border: none;
        }
        .error-list {
            list-style: none;
            padding-left: 0;
        }
        .error-list li:before {
            content: "✗ ";
            color: #dc3545;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <img src="logoo.png" alt="Logo" width="80" height="80">
        </a>
        <span class="navbar-text fw-bold fs-5" style="color: #0d1b2a;">SmileCare Dental Clinic</span>
        <a href="contact.html" class="btn btn-outline-primary">Back to Contact</a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                
                <?php if ($success && $contact_data): ?>
                    <!-- SUCCESS MESSAGE -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">✅ Message Sent Successfully!</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h4>Thank You for Contacting Us!</h4>
                            <p class="mb-0">Your message has been received successfully.</p>
                            <p class="mb-0">We will respond to <strong><?php echo htmlspecialchars($contact_data['email']); ?></strong> within 24 hours.</p>
                        </div>
                        
                        <h4 class="mt-4 mb-3">Message Details:</h4>
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 25%;">Name</th>
                                <td><?php echo htmlspecialchars($contact_data['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($contact_data['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?php echo htmlspecialchars($contact_data['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Message</th>
                                <td><?php echo nl2br(htmlspecialchars($contact_data['message'])); ?></td>
                            </tr>
                            <tr>
                                <th>Submitted</th>
                                <td><?php echo date('l, F j, Y \a\t g:i A', strtotime($contact_data['time'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span class="badge bg-info">Message Received</span></td>
                            </tr>
                        </table>
                        
                        <div class="alert alert-info mt-4">
                            <h5>What Happens Next?</h5>
                            <ul class="mb-0">
                                <li>Our team will review your message</li>
                                <li>You will receive a response within 24 hours (business days)</li>
                                <li>Check your email inbox and spam folder</li>
                                <li>For urgent matters, please call us at +968 92828289</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="contact.html" class="btn btn-primary btn-lg me-2">Send Another Message</a>
                            <a href="index.html" class="btn btn-secondary btn-lg">Return to Home</a>
                        </div>
                    </div>
                    
                <?php elseif (!empty($errors)): ?>
                    <!-- ERROR MESSAGES -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">❌ Message Not Sent</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h4>Validation Errors:</h4>
                            <p>The following errors were found with your submission:</p>
                            <ul class="error-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Note:</strong> These errors occurred during server-side validation. Please go back and correct the issues before resubmitting your message.
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="contact.html" class="btn btn-primary btn-lg">Try Again</a>
                            <a href="index.html" class="btn btn-outline-secondary btn-lg ms-2">Go to Home</a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- NO DATA -->
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">ℹ️ No Message Data</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h4>No Message Found</h4>
                            <p>Please send us a message using the contact form.</p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="contact.html" class="btn btn-primary btn-lg">Contact Us</a>
                            <a href="index.html" class="btn btn-secondary btn-lg ms-2">Go to Home</a>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<footer style="background-color: #d9e2e8; padding: 2rem 0;">
    <div class="container text-center">
        <p class="mb-0 text-muted">© 2025 SmileCare Dental Clinic. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>