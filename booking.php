<?php
session_start();

// Verify the user is logged in and is a client
//if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
 //   error_log("Session data missing or invalid role: " . print_r($_SESSION, true));
 //   header("Location: login.php?error=Please log in to continue");
 //   exit();
//}

// Database connection
$host = 'localhost';
$dbname = 'catering_system';
$username = 'hannah_b';
$password = 'hannah1234$$';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in client's user_id
$client_id = (int)$_SESSION['user_id']; // Use user_id directly

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $caterer_id = $conn->real_escape_string($_POST['caterer_id']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_location = $conn->real_escape_string($_POST['event_location']);
    $guest_number = intval($_POST['guest_number']);
    $special_request = $conn->real_escape_string($_POST['special_request']);
    $created_at = date('Y-m-d H:i:s');
    $status = 'pending';

    // Validate event date (must be today or in the future)
    $today = date('Y-m-d');
    if ($event_date < $today) {
        $error_message = "Event date must be today or in the future.";
    } elseif ($guest_number < 1) {
        $error_message = "Number of guests must be at least 1.";
    } elseif (empty($caterer_id)) {
        $error_message = "Please select a caterer.";
    } else {
        // Insert booking using prepared statement
        $sql = "INSERT INTO bookings (client_id, caterer_id, event_date, event_location, guest_number, special_request, created_at, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error_message = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("iississs", $client_id, $caterer_id, $event_date, $event_location, $guest_number, $special_request, $created_at, $status);

            if ($stmt->execute()) {
                $success_message = "Booking submitted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch caterers for the dropdown
$caterers = [];
$sql = "SELECT caterer_id, business_name FROM caterers";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $caterers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Booking System</title>
    <link rel="stylesheet" type="text/css" href="booking.css">
    <script src="booking.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Booking Space</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="bookingForm">
            <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_id); ?>"> 
            
            <div class="form-group">
                <label for="caterer_id">Select Caterer</label>
                <select name="caterer_id" id="caterer_id" required>
                    <option value="">-- Select a caterer --</option>
                    <?php foreach ($caterers as $caterer): ?>
                        <option value="<?php echo $caterer['caterer_id']; ?>">
                            <?php echo htmlspecialchars($caterer['business_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="event_date">Event Date</label>
                <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="event_location">Event Location</label>
                <input type="text" name="event_location" id="event_location" required placeholder="Enter event location">
            </div>
            
            <div class="form-group">
                <label for="guest_number">Number of Guests</label>
                <input type="number" name="guest_number" id="guest_number" min="1" required placeholder="Enter number of guests">
            </div>
            
            <div class="form-group">
                <label for="special_request">Special Requests</label>
                <textarea name="special_request" id="special_request" placeholder="Any special dietary requirements or other requests"></textarea>
            </div>
            
            <button type="submit" name="submit_booking" class="btn">Submit Booking</button>
        </form>
        
        <div class="booking-details" id="bookingPreview" style="display: none;">
            <h2>Booking Summary</h2>
            <div class="detail-row">
                <span class="detail-label">Caterer:</span>
                <span class="detail-value" id="previewCaterer"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Event Date:</span>
                <span class="detail-value" id="previewDate"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Location:</span>
                <span class="detail-value" id="previewLocation"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Guests:</span>
                <span class="detail-value" id="previewGuests"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Special Requests:</span>
                <span class="detail-value" id="previewRequest"></span>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>