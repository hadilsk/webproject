<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['patient-name']);
    $email = trim($_POST['patient-email']);
    $phone = trim($_POST['patient-phone']);
    $service = $_POST['service-select'];
    $dentist = $_POST['dentist-select'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Store in session
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
    
    // Insert into database
    $sql = "INSERT INTO appointments (patient_name, patient_email, patient_phone, service, dentist, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $name, $email, $phone, $service, $dentist, $date, $time);
    
    if ($stmt->execute()) {
        $_SESSION['appointment_success'] = true;
        $_SESSION['appointment_id'] = $stmt->insert_id;
    } else {
        $_SESSION['appointment_error'] = "Database error";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get session data
$success = $_SESSION['appointment_success'] ?? false;
$error = $_SESSION['appointment_error'] ?? false;
$appointment_data = $_SESSION['last_appointment'] ?? null;
$appointment_id = $_SESSION['appointment_id'] ?? null;

// Clear session
unset($_SESSION['appointment_success']);
unset($_SESSION['appointment_error']);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background: linear-gradient(90deg, #f1f4f8, #4a6fa5);">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <img src="logoo.png" alt="Logo" width="80" height="80">
        </a>
        <a href="bookAppointment.html" class="btn btn-outline-light">Back to Booking</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Appointment Booking Result</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($success && $appointment_data): ?>
                        <div class="alert alert-success">
                            <h4>✅ Appointment Booked Successfully!</h4>
                            <p>Appointment ID: <strong>APT-<?php echo str_pad($appointment_id, 5, '0', STR_PAD_LEFT); ?></strong></p>
                            <p>We will send confirmation email within 24 hours.</p>
                        </div>
                        
                        <h4 class="mt-4">Appointment Details:</h4>
                        <table class="table table-bordered">
                            <tr><th>Appointment ID</th><td>APT-<?php echo str_pad($appointment_id, 5, '0', STR_PAD_LEFT); ?></td></tr>
                            <tr><th>Patient Name</th><td><?php echo htmlspecialchars($appointment_data['name']); ?></td></tr>
                            <tr><th>Email</th><td><?php echo htmlspecialchars($appointment_data['email']); ?></td></tr>
                            <tr><th>Phone</th><td><?php echo htmlspecialchars($appointment_data['phone']); ?></td></tr>
                            <tr><th>Service</th><td><?php echo htmlspecialchars($appointment_data['service']); ?></td></tr>
                            <tr><th>Dentist</th><td><?php echo htmlspecialchars($appointment_data['dentist']); ?></td></tr>
                            <tr><th>Date</th><td><?php echo $appointment_data['date']; ?></td></tr>
                            <tr><th>Time</th><td><?php echo $appointment_data['time']; ?></td></tr>
                            <tr><th>Status</th><td><span class="badge bg-warning">Pending Confirmation</span></td></tr>
                        </table>
                        
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger">
                            <h4>❌ Booking Failed</h4>
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h4>No Appointment Data</h4>
                            <p>Please book an appointment from the booking page.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="bookAppointment.html" class="btn btn-primary">Book Another</a>
                        <a href="index.html" class="btn btn-secondary">Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>