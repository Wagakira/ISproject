<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caterer') {
    header("Location: login.php");
    exit();
}

include "configure.php";

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$caterer_id = $_SESSION['user_id'];
$sql = "SELECT * FROM caterers WHERE caterer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caterer_id);
$stmt->execute();
$caterer = $stmt->get_result()->fetch_assoc();
if (!$caterer) {
    $sql = "SELECT * FROM users WHERE user_id = ? AND role = 'caterer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $caterer_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $caterer = [
            'caterer_id' => $user['user_id'],
            'business_name' => $user['email'],
            'email' => $user['email'],
            'password' => $user['password'],
            'description' => '',
            'rating' => null,
            'image' => null,
            'menu_url' => null,
            'created_at' => $user['created_at']
        ];
    } else {
        die("Caterer not found.");
    }
}
$caterer_id = $caterer['caterer_id'];

$error = '';
$success = '';

if (isset($_POST['update_profile'])) {
    $business_name = $conn->real_escape_string($_POST['business_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "UPDATE caterers SET business_name = ?, email = ?, description = ? WHERE caterer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $business_name, $email, $description, $caterer_id);
    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
        $sql = "SELECT * FROM caterers WHERE caterer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $caterer_id);
        $stmt->execute();
        $caterer = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error updating profile: " . $stmt->error;
    }
}

if (isset($_POST['update_availability'])) {
    $date = $conn->real_escape_string($_POST['date']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    $sql = "SELECT * FROM availability WHERE caterer_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $caterer_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "UPDATE availability SET is_available = ? WHERE caterer_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $is_available, $caterer_id, $date);
    } else {
        $sql = "INSERT INTO availability (caterer_id, date, is_available) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $caterer_id, $date, $is_available);
    }

    if ($stmt->execute()) {
        $success = "Availability updated successfully!";
    } else {
        $error = "Error updating availability: " . $stmt->error;
    }
}

if (isset($_POST['create_booking'])) {
    $client_id = $conn->real_escape_string($_POST['client_id']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_location = $conn->real_escape_string($_POST['event_location']);
    $guest_number = intval($_POST['guest_number']);
    $special_request = $conn->real_escape_string($_POST['special_request']);
    $status = 'pending';

    $sql = "SELECT is_available FROM availability WHERE caterer_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $caterer_id, $event_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = $result->fetch_assoc();

    if ($availability && !$availability['is_available']) {
        $error = "You are not available on the selected date.";
    } else {
        $sql = "INSERT INTO bookings (client_id, caterer_id, event_date, event_location, guest_number, special_request, created_at, status) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iississ", $client_id, $caterer_id, $event_date, $event_location, $guest_number, $special_request, $status);
        if ($stmt->execute()) {
            $success = "Booking created successfully!";
        } else {
            $error = "Error creating booking: " . $stmt->error;
        }
    }
}

if (isset($_POST['update_booking_status'])) {
    $booking_id = $conn->real_escape_string($_POST['booking_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE bookings SET status = ? WHERE booking_id = ? AND caterer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $booking_id, $caterer_id);
    if ($stmt->execute()) {
        $success = "Booking status updated successfully!";
    } else {
        $error = "Error updating booking status: " . $stmt->error;
    }
}

if (isset($_POST['place_order'])) {
    $client_id = $conn->real_escape_string($_POST['client_id']);
    $caterer_id = $conn->real_escape_string($_POST['caterer_id']);
    $order_details = $conn->real_escape_string($_POST['order_details']);
    $total_price = floatval($_POST['total_price']);
    $order_date = date('Y-m-d H:i:s');
    $status = 'pending';

    // Validate caterer_id
    $check_sql = "SELECT caterer_id FROM caterers WHERE caterer_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $caterer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Validate client_id (user_id) exists in users table
        $client_check_sql = "SELECT user_id FROM users WHERE user_id = ?";
        $client_check_stmt = $conn->prepare($client_check_sql);
        $client_check_stmt->bind_param("i", $client_id);
        $client_check_stmt->execute();
        $client_result = $client_check_stmt->get_result();

        if ($client_result->num_rows > 0) {
            $sql = "INSERT INTO orders (client_id, caterer_id, order_details, total_price, status, order_date) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisdss", $client_id, $caterer_id, $order_details, $total_price, $status, $order_date);
            
            if ($stmt->execute()) {
                $success = "Order placed successfully!";
            } else {
                $error = "Error placing order: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Error: Invalid client ID - User does not exist";
        }
        $client_check_stmt->close();
    } else {
        $error = "Error: Invalid caterer ID - Caterer does not exist";
    }
    $check_stmt->close();
}

if (isset($_POST['update_order_status'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE orders SET status = ? WHERE order_id = ? AND caterer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_status, $order_id, $caterer_id);
    if ($stmt->execute()) {
        $success = "Order status updated successfully!";
    } else {
        $error = "Error updating order status: " . $stmt->error;
    }
}

if (isset($_POST['upload_menu'])) {
    $target_dir = "uploads/menu/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["menu_pdf"]["name"]);
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $new_filename = "menu_" . $caterer_id . "_" . time() . "." . $fileType;
    $target_file = $target_dir . $new_filename;

    if ($_FILES["menu_pdf"]["size"] > 5000000) {
        $error = "Sorry, your file is too large (max 5MB).";
    } elseif ($fileType != 'pdf') {
        $error = "Only PDF files are allowed.";
    } elseif (move_uploaded_file($_FILES["menu_pdf"]["tmp_name"], $target_file)) {
        $sql = "UPDATE caterers SET menu_url = ? WHERE caterer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_filename, $caterer_id);
        if ($stmt->execute()) {
            $success = "Menu uploaded successfully!";
            $caterer['menu_url'] = $new_filename;
        } else {
            $error = "Error uploading menu: " . $stmt->error;
        }
    } else {
        $error = "Sorry, there was an error uploading your file.";
    }
}

$bookings = [];
$sql = "SELECT b.*, u.email AS client_email 
        FROM bookings b 
        JOIN clients c ON b.client_id = c.client_id
        JOIN users u ON c.user_id = u.user_id
        WHERE b.caterer_id = ? 
        ORDER BY b.event_date";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caterer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['event_location'] = htmlspecialchars($row['event_location'] ?? '');
    $row['special_request'] = htmlspecialchars($row['special_request'] ?? '');
    $bookings[] = $row;
}

$availability = [];
$sql = "SELECT date, is_available FROM availability WHERE caterer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caterer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $availability[$row['date']] = $row['is_available'];
}

$clients = [];
$sql = "SELECT c.client_id, u.user_id, u.email 
        FROM clients c 
        JOIN users u ON c.user_id = u.user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

$orders = [];
$sql = "SELECT o.*, u.email AS client_email 
        FROM orders o 
        JOIN users u ON o.client_id = u.user_id
        WHERE o.caterer_id = ? 
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caterer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$reviews = [];
$sql = "SELECT r.*, u.email AS user_email 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.caterer_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caterer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caterer Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="dashboard.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($caterer['business_name']); ?>!</h1>
            <p>Manage your bookings, orders, and profile</p>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" data-tab="bookings">Bookings</button>
            <button class="tab-btn" data-tab="orders">Orders</button>
            <button class="tab-btn" data-tab="profile">Profile</button>
        </div>

        <div class="tab-content active" id="bookings">
            <h2>Bookings</h2>
            
            <h3>Create New Booking</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="client_id">Client:</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['client_id']; ?>">
                                <?php echo htmlspecialchars($client['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date:</label>
                    <input type="date" name="event_date" id="event_date" required>
                </div>
                <div class="form-group">
                    <label for="event_location">Event Location:</label>
                    <input type="text" name="event_location" id="event_location" required>
                </div>
                <div class="form-group">
                    <label for="guest_number">Number of Guests:</label>
                    <input type="number" name="guest_number" id="guest_number" min="1" required>
                </div>
                <div class="form-group">
                    <label for="special_request">Special Request:</label>
                    <textarea name="special_request" id="special_request"></textarea>
                </div>
                <button type="submit" name="create_booking" class="btn"><i class="fas fa-plus"></i> Create Booking</button>
            </form>

            <h3>Manage Availability</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" required>
                </div>
                <div class="form-group">
                    <label for="is_available">Available:</label>
                    <input type="checkbox" name="is_available" id="is_available" value="1">
                </div>
                <button type="submit" name="update_availability" class="btn"><i class="fas fa-calendar-check"></i> Update Availability</button>
            </form>

            <h3>Your Bookings</h3>
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Client</th>
                        <th>Event Date</th>
                        <th>Location</th>
                        <th>Guests</th>
                        <th>Special Request</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="8">No bookings yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['client_email']); ?></td>
                                <td><?php echo $booking['event_date']; ?></td>
                                <td><?php echo $booking['event_location']; ?></td>
                                <td><?php echo $booking['guest_number']; ?></td>
                                <td><?php echo $booking['special_request']; ?></td>
                                <td><?php echo $booking['status']; ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <select name="status" required>
                                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_booking_status" class="btn"><i class="fas fa-sync"></i> Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-content" id="orders">
            <h2>Place New Order</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="client_id">Client:</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['user_id']; ?>">
                                <?php echo htmlspecialchars($client['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_details">Order Details:</label>
                    <textarea name="order_details" id="order_details" required></textarea>
                </div>
                <div class="form-group">
                    <label for="total_price">Total Price (Ksh):</label>
                    <input type="number" step="0.01" name="total_price" id="total_price" required>
                </div>
                <input type="hidden" name="caterer_id" value="<?php echo htmlspecialchars($caterer_id); ?>">
                <button type="submit" name="place_order" class="btn"><i class="fas fa-plus"></i> Place Order</button>
            </form>

            <h2>Your Orders</h2>
            <div class="order-list">
                <?php if (empty($orders)): ?>
                    <p>No orders yet.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <h3>Order #<?php echo $order['order_id']; ?></h3>
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($order['client_email']); ?></p>
                            <p><strong>Details:</strong> <?php echo htmlspecialchars($order['order_details']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'])); ?></p>
                            <p><strong>Total:</strong> Ksh <?php echo number_format($order['total_price'], 2); ?></p>
                            <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="status" required>
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_order_status" class="btn"><i class="fas fa-sync"></i> Update Status</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="profile">
            <h2>Profile</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" name="business_name" id="business_name" 
                           value="<?php echo htmlspecialchars($caterer['business_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($caterer['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description"><?php echo htmlspecialchars($caterer['description'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>

            <h2>Upload Menu PDF</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="menu_pdf">Menu PDF</label>
                    <input type="file" name="menu_pdf" id="menu_pdf" accept=".pdf" required>
                </div>
                <button type="submit" name="upload_menu" class="btn"><i class="fas fa-upload"></i> Upload Menu</button>
            </form>
            <?php if ($caterer['menu_url']): ?>
                <div>
                    <h3>Current Menu</h3>
                    <a href="uploads/menu/<?php echo htmlspecialchars($caterer['menu_url']); ?>" target="_blank">View Menu PDF</a>
                </div>
            <?php endif; ?>

            <h2>Menu Reviews</h2>
            <div class="review-list">
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review">
                            <p><strong>From:</strong> <?php echo htmlspecialchars($review['user_email']); ?></p>
                            <p><strong>Rating:</strong> <?php echo $review['rating']; ?>/5</p>
                            <p><?php echo htmlspecialchars($review['comment']); ?></p>
                            <p><small>Posted: <?php echo date('M j, Y', strtotime($review['created_at'])); ?></small></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const bookings = <?php echo json_encode($bookings); ?>;
        const availability = <?php echo json_encode($availability); ?>;
    </script>
</body>
</html>