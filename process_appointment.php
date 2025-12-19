<?php
require_once 'config.php';

session_start();

// Initialize error array
$errors = [];
$appointment_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========================================
    // 1. GET AND SANITIZE INPUT DATA
    // ========================================
    
    $name = isset($_POST['patient-name']) ? trim($_POST['patient-name']) : '';
    $email = isset($_POST['patient-email']) ? trim($_POST['patient-email']) : '';
    $phone = isset($_POST['patient-phone']) ? trim($_POST['patient-phone']) : '';
    $service = isset($_POST['service-select']) ? $_POST['service-select'] : '';
    $dentist = isset($_POST['dentist-select']) ? $_POST['dentist-select'] : '';
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $time = isset($_POST['time']) ? $_POST['time'] : '';
    
    // Store raw data for potential redisplay
    $appointment_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'dentist' => $dentist,
        'date' => $date,
        'time' => $time
    ];
    
    // ========================================
    // 2. SERVER-SIDE VALIDATION - REQUIRED FIELDS
    // ========================================
    
    if (empty($name)) {
        $errors[] = "Patient name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    
    if (empty($service)) {
        $errors[] = "Please select a service.";
    }
    
    if (empty($dentist)) {
        $errors[] = "Please select a dentist.";
    }
    
    if (empty($date)) {
        $errors[] = "Appointment date is required.";
    }
    
    if (empty($time)) {
        $errors[] = "Appointment time is required.";
    }
    
    // ========================================
    // 3. REGEX VALIDATIONS
    // ========================================
    
    // Validate name (letters, spaces, hyphens only)
    if (!empty($name) && !preg_match('/^[a-zA-Z\s\-]{2,50}$/', $name)) {
        $errors[] = "Name must be 2-50 characters and contain only letters, spaces, or hyphens.";
    }
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    
    // Validate phone (8-15 digits only)
    $numericPhone = preg_replace('/\D/', '', $phone);
    if (!empty($phone) && !preg_match('/^[0-9]{8,15}$/', $numericPhone)) {
        $errors[] = "Phone number must be 8-15 digits.";
    }
    
    // ========================================
    // 4. LENGTH AND RANGE CONSTRAINTS
    // ========================================
    
    if (!empty($name) && (strlen($name) < 2 || strlen($name) > 50)) {
        $errors[] = "Name must be between 2 and 50 characters.";
    }
    
    // ========================================
    // 5. LOGICAL VALIDATIONS
    // ========================================
    
    // Validate date - cannot be in the past
    if (!empty($date)) {
        $selectedDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selectedDate < $today) {
            $errors[] = "Appointment date cannot be in the past.";
        }
        
        // Check if Friday (closed day)
        $dayOfWeek = (int)$selectedDate->format('w');
        if ($dayOfWeek === 5) {
            $errors[] = "We are closed on Fridays. Please select another day.";
        }
    }
    
    // Validate time based on working hours
    if (!empty($date) && !empty($time)) {
        $selectedDate = new DateTime($date);
        $dayOfWeek = (int)$selectedDate->format('w');
        
        // Define working hours
        $workingHours = [
            0 => ['open' => '09:00', 'close' => '18:00'], // Sunday
            1 => ['open' => '09:00', 'close' => '18:00'], // Monday
            2 => ['open' => '09:00', 'close' => '18:00'], // Tuesday
            3 => ['open' => '09:00', 'close' => '18:00'], // Wednesday
            4 => ['open' => '09:00', 'close' => '18:00'], // Thursday
            5 => ['open' => null, 'close' => null],        // Friday - CLOSED
            6 => ['open' => '09:00', 'close' => '14:00']  // Saturday
        ];
        
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Check if closed
        if ($workingHours[$dayOfWeek]['open'] === null) {
            $errors[] = "We are closed on Fridays.";
        } else {
            // Validate time range
            $openTime = $workingHours[$dayOfWeek]['open'];
            $closeTime = $workingHours[$dayOfWeek]['close'];
            
            if ($time < $openTime || $time >= $closeTime) {
                $errors[] = "On " . $dayNames[$dayOfWeek] . ", we are open from $openTime to $closeTime.";
            }
        }
    }
    
    // ========================================
    // 6. VALIDATE SERVICE AND DENTIST OPTIONS
    // ========================================
    
    $validServices = [
        'Dental Exams & Cleanings',
        'Dental Fillings',
        'Root Canal Therapy',
        'Tooth Extractions',
        'Night Guards',
        'Teeth Whitening',
        'Dental Veneers',
        'Dental Bonding',
        'Dental Crowns',
        'Dental Bridges',
        'Dental Implants',
        'Dentures',
        'Orthodontics (Braces)',
        'Pediatric Dentistry'
    ];
    
    if (!empty($service) && !in_array($service, $validServices)) {
        $errors[] = "Invalid service selected.";
    }
    
    $validDentists = [
        'Dr. Elena Vance',
        'Dr. Ben Carter',
        'Dr. Sophia Rossi',
        'Dr. Marcus Thorne',
        'Dr. Alisha Kumar',
        'Dr. Samuel Jones'
    ];
    
    if (!empty($dentist) && !in_array($dentist, $validDentists)) {
        $errors[] = "Invalid dentist selected.";
    }
    
    // ========================================
    // 7. CHECK FOR DUPLICATE APPOINTMENTS
    // ========================================
    
    if (empty($errors) && !empty($date) && !empty($time) && !empty($dentist)) {
        $checkSql = "SELECT * FROM appointments WHERE dentist = ? AND appointment_date = ? AND appointment_time = ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        
        if ($checkStmt) {
            $checkStmt->bind_param("sss", $dentist, $date, $time);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                $errors[] = "This time slot is already booked. Please choose a different time.";
            }
            
            $checkStmt->close();
        }
    }
    
    // ========================================
    // 8. PROCESS IF NO ERRORS
    // ========================================
    
    if (empty($errors)) {
        // Insert into database
        $sql = "INSERT INTO appointments (patient_name, patient_email, patient_phone, service, dentist, appointment_date, appointment_time, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $phone, $service, $dentist, $date, $time);
        
        if ($stmt->execute()) {
            $_SESSION['appointment_success'] = true;
            $_SESSION['appointment_id'] = $stmt->insert_id;
            $_SESSION['last_appointment'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service' => $service,
                'dentist' => $dentist,
                'date' => $date,
                'time' => $time,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            $errors[] = "Database error: Unable to save appointment. Please try again.";
        }
        
        $stmt->close();
    }
    
    // Store errors in session if any
    if (!empty($errors)) {
        $_SESSION['appointment_errors'] = $errors;
        $_SESSION['appointment_data'] = $appointment_data;
    }
    
    $conn->close();
    
    // Redirect back to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ========================================
// DISPLAY RESULTS
// ========================================

// Get session data
$success = $_SESSION['appointment_success'] ?? false;
$errors = $_SESSION['appointment_errors'] ?? [];
$appointment_data = $_SESSION['last_appointment'] ?? null;
$appointment_id = $_SESSION['appointment_id'] ?? null;

// Clear session
unset($_SESSION['appointment_success']);
unset($_SESSION['appointment_errors']);
unset($_SESSION['appointment_id']);
if ($success) {
    unset($_SESSION['last_appointment']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Appointment Confirmation - SmileCare</title>
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
        <a href="bookAppointment.html" class="btn btn-outline-primary">Back to Booking</a>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                
                <?php if ($success && $appointment_data): ?>
                    <!-- SUCCESS MESSAGE -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">✅ Appointment Confirmed!</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h4>Booking Successful!</h4>
                            <p class="mb-0">Your appointment has been successfully booked.</p>
                            <p class="mb-0">Appointment ID: <strong class="fs-5">APT-<?php echo str_pad($appointment_id, 5, '0', STR_PAD_LEFT); ?></strong></p>
                            <p class="mb-0">We will send a confirmation email to <strong><?php echo htmlspecialchars($appointment_data['email']); ?></strong> within 24 hours.</p>
                        </div>
                        
                        <h4 class="mt-4 mb-3">Appointment Details:</h4>
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 30%;">Appointment ID</th>
                                <td><strong>APT-<?php echo str_pad($appointment_id, 5, '0', STR_PAD_LEFT); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Patient Name</th>
                                <td><?php echo htmlspecialchars($appointment_data['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($appointment_data['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?php echo htmlspecialchars($appointment_data['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Service</th>
                                <td><?php echo htmlspecialchars($appointment_data['service']); ?></td>
                            </tr>
                            <tr>
                                <th>Dentist</th>
                                <td><?php echo htmlspecialchars($appointment_data['dentist']); ?></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?php echo date('l, F j, Y', strtotime($appointment_data['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Time</th>
                                <td><?php echo date('g:i A', strtotime($appointment_data['time'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span class="badge bg-warning text-dark">Pending Confirmation</span></td>
                            </tr>
                        </table>
                        
                        <div class="alert alert-info mt-4">
                            <h5>What's Next?</h5>
                            <ul class="mb-0">
                                <li>You will receive a confirmation email within 24 hours</li>
                                <li>Please arrive 10 minutes before your appointment time</li>
                                <li>Bring your ID and insurance card (if applicable)</li>
                                <li>If you need to cancel or reschedule, please call us at +968 92828289</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="bookAppointment.html" class="btn btn-primary btn-lg me-2">Book Another Appointment</a>
                            <a href="index.html" class="btn btn-secondary btn-lg">Return to Home</a>
                        </div>
                    </div>
                    
                <?php elseif (!empty($errors)): ?>
                    <!-- ERROR MESSAGES -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">❌ Booking Failed</h3>
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
                            <strong>Note:</strong> These errors occurred during server-side validation. Please go back and correct the issues before resubmitting.
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="bookAppointment.html" class="btn btn-primary btn-lg">Try Again</a>
                            <a href="contact.html" class="btn btn-outline-secondary btn-lg ms-2">Contact Us</a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- NO DATA -->
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">ℹ️ No Appointment Data</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h4>No Appointment Found</h4>
                            <p>Please book an appointment using the booking form.</p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="bookAppointment.html" class="btn btn-primary btn-lg">Book Appointment</a>
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