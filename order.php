<?php
$host = 'localhost';
$dbname = 'catering_system';
$username = 'hannah_b';
$password = 'hannah1234$$';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process new order submission from profile.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    // Get user_id from the form (passed as a hidden input)
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        header("Location: profile.php?caterer_id=" . (int)$_POST['caterer_id'] . "&order=error&reason=no_user_id");
        exit();
    }

    $user_id = (int)$_POST['user_id'];
    $caterer_id = (int)$_POST['caterer_id'];
    $order_date = $conn->real_escape_string($_POST['order_date']);
    $guests = (int)$_POST['guests'];
    $order_details = $conn->real_escape_string($_POST['order_details']);
    $delivery_address = $conn->real_escape_string($_POST['delivery_address']);
    $special_requests = $conn->real_escape_string($_POST['special_requests'] ?? '');
    $order_status = 'Pending';
    $order_timestamp = date('Y-m-d H:i:s');
    $total_price = 0.00; // Default until caterer updates

    $insert_order_query = "INSERT INTO orders (user_id, caterer_id, order_date, guests, order_details, delivery_address, special_requests, order_status, order_timestamp, total_price, status) 
                          VALUES ($user_id, $caterer_id, '$order_date', $guests, '$order_details', '$delivery_address', '$special_requests', '$order_status', '$order_timestamp', $total_price, '$order_status')";

    if ($conn->query($insert_order_query) === TRUE) {
        header("Location: profile.php?caterer_id=$caterer_id&order=success");
    } else {
        header("Location: profile.php?caterer_id=$caterer_id&order=error");
    }
    exit();
}

// Order tracking logic
// Use user_id from URL parameter, default to 1 if not provided
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

if (isset($_POST['cancel_order'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $sql = "SELECT status FROM orders WHERE order_id = '$order_id' AND user_id = '$user_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        if (in_array($order['status'], ['Pending', 'Confirmed'])) {
            $sql = "UPDATE orders SET status = 'Cancelled', order_status = 'Cancelled' WHERE order_id = '$order_id'";
            if ($conn->query($sql) === TRUE) {
                header("Location: order.php?user_id=$user_id&message=Order+cancelled+successfully");
                exit();
            } else {
                $error = "Error cancelling order: " . $conn->error;
            }
        } else {
            $error = "Order cannot be cancelled at this stage.";
        }
    } else {
        $error = "Order not found.";
    }
}

$orders = [];
$sql = "SELECT o.*, c.business_name 
        FROM orders o 
        JOIN caterers c ON o.caterer_id = c.caterer_id 
        WHERE o.user_id = '$user_id' 
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$active_orders = [];
$completed_orders = [];
foreach ($orders as $order) {
    if ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') {
        $completed_orders[] = $order;
    } else {
        $active_orders[] = $order;
    }
}

$order_items = [];
if (isset($_GET['order_id'])) {
    $order_id = $conn->real_escape_string($_GET['order_id']);
    $sql = "SELECT m.dish_name, oi.quantity, oi.price 
            FROM order_items oi 
            JOIN menu m ON oi.id = m.id 
            WHERE oi.order_id = '$order_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $order_items[] = $row;
        }
    } else {
        $error = "No items found for order ID $order_id: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking</title>
    <link rel="stylesheet" type="text/css" href="order.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="order.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Order Tracking</h1>
            <p>View and track the progress of your catering orders</p>
            <?php if (isset($_GET['message'])): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </header>
        
        <div class="main-content">
            <div class="order-list">
                <!-- Active Orders Section -->
                <h2>Order Progress</h2>
                <?php if (empty($active_orders)): ?>
                    <div class="no-orders">
                        <p>You have no active orders at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_orders as $order): ?>
                        <div class="order-card <?php echo isset($_GET['order_id']) && $_GET['order_id'] == $order['order_id'] ? 'active' : ''; ?>" 
                             data-order-id="<?php echo $order['order_id']; ?>">
                            <h3>Order #<?php echo $order['order_id']; ?></h3>
                            <div class="order-meta">
                                <span><strong>Caterer:</strong> <?php echo htmlspecialchars($order['business_name']); ?></span>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                            <div class="order-meta">
                                <span><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                                <span><strong>Total:</strong> Ksh <?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                            <!-- Mini Progress Bar -->
                            <div class="mini-progress-tracker">
                                <div class="mini-progress-bar" 
                                     data-status="<?php echo strtolower($order['status']); ?>" 
                                     style="width: 
                                     <?php 
                                     $status_progress = [
                                         'Pending' => '0%',
                                         'Confirmed' => '20%',
                                         'Preparing' => '40%',
                                         'Ready' => '60%',
                                         'Delivered' => '80%',
                                         'Completed' => '100%',
                                         'Cancelled' => '0%'
                                     ];
                                     echo $status_progress[$order['status']] ?? '0%';
                                     ?>">
                                </div>
                            </div>
                            <!-- Cancel Button -->
                            <?php if (in_array($order['status'], ['Pending', 'Confirmed'])): ?>
                                <form method="POST" class="cancel-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="cancel_order" class="cancel-btn">Cancel Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Order History Section -->
                <h2>Order History</h2>
                <?php if (empty($completed_orders)): ?>
                    <div class="no-orders">
                        <p>You have no completed orders yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($completed_orders as $order): ?>
                        <div class="order-card <?php echo isset($_GET['order_id']) && $_GET['order_id'] == $order['order_id'] ? 'active' : ''; ?>" 
                             data-order-id="<?php echo $order['order_id']; ?>">
                            <h3>Order #<?php echo $order['order_id']; ?></h3>
                            <div class="order-meta">
                                <span><strong>Caterer:</strong> <?php echo htmlspecialchars($order['business_name']); ?></span>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                            <div class="order-meta">
                                <span><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                                <span><strong>Total:</strong> Ksh <?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                            <!-- Mini Progress Bar -->
                            <div class="mini-progress-tracker">
                                <div class="mini-progress-bar" 
                                     data-status="<?php echo strtolower($order['status']); ?>" 
                                     style="width: 
                                     <?php 
                                     $status_progress = [
                                         'Pending' => '0%',
                                         'Confirmed' => '20%',
                                         'Preparing' => '40%',
                                         'Ready' => '60%',
                                         'Delivered' => '80%',
                                         'Completed' => '100%',
                                         'Cancelled' => '0%'
                                     ];
                                     echo $status_progress[$order['status']] ?? '0%';
                                     ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="order-details">
                <?php if (isset($_GET['order_id']) && !empty($orders)): ?>
                    <?php 
                    $selected_order = null;
                    foreach ($orders as $order) {
                        if ($order['order_id'] == $_GET['order_id']) {
                            $selected_order = $order;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($selected_order): ?>
                        <h2>Order Details #<?php echo $selected_order['order_id']; ?></h2>
                        
                        <div class="order-meta">
                            <p><strong>Caterer:</strong> <?php echo htmlspecialchars($selected_order['business_name']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i a', strtotime($selected_order['order_date'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="order-status status-<?php echo strtolower($selected_order['status']); ?>">
                                    <?php echo $selected_order['status']; ?>
                                </span>
                            </p>
                        </div>
                        
                        <h3>Order Items</h3>
                        <?php if (!empty($order_items)): ?>
                            <table class="item-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['dish_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Ksh <?php echo number_format($item['price'], 2); ?></td>
                                            <td>Ksh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="3" style="text-align: right;">Total:</td>
                                        <td>Ksh <?php echo number_format($selected_order['total_price'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No items found for this order.</p>
                        <?php endif; ?>
                        
                        <div class="progress-tracker">
                            <h3>Order Progress</h3>
                            <div class="progress-steps">
                                <div class="progress-bar" 
                                     data-status="<?php echo strtolower($selected_order['status']); ?>" 
                                     style="width: 
                                     <?php 
                                     $status_progress = [
                                         'Pending' => '0%',
                                         'Confirmed' => '20%',
                                         'Preparing' => '40%',
                                         'Ready' => '60%',
                                         'Delivered' => '80%',
                                         'Completed' => '100%',
                                         'Cancelled' => '0%'
                                     ];
                                     echo $status_progress[$selected_order['status']] ?? '0%';
                                     ?>">
                                    <span class="progress-tooltip">
                                        <?php echo $status_progress[$selected_order['status']] ?? '0%'; ?>
                                    </span>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Pending' ? 'active' : ''; ?> <?php echo in_array($selected_order['status'], ['Confirmed', 'Preparing', 'Ready', 'Delivered', 'Completed']) ? 'completed' : ''; ?>">
                                    <div class="step-icon">1</div>
                                    <div class="step-label">Order Placed</div>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Confirmed' ? 'active' : ''; ?> <?php echo in_array($selected_order['status'], ['Preparing', 'Ready', 'Delivered', 'Completed']) ? 'completed' : ''; ?>">
                                    <div class="step-icon">2</div>
                                    <div class="step-label">Confirmed</div>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Preparing' ? 'active' : ''; ?> <?php echo in_array($selected_order['status'], ['Ready', 'Delivered', 'Completed']) ? 'completed' : ''; ?>">
                                    <div class="step-icon">3</div>
                                    <div class="step-label">Preparing</div>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Ready' ? 'active' : ''; ?> <?php echo in_array($selected_order['status'], ['Delivered', 'Completed']) ? 'completed' : ''; ?>">
                                    <div class="step-icon">4</div>
                                    <div class="step-label">Ready</div>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Delivered' ? 'active' : ''; ?> <?php echo $selected_order['status'] == 'Completed' ? 'completed' : ''; ?>">
                                    <div class="step-icon">5</div>
                                    <div class="step-label">Delivered</div>
                                </div>
                                
                                <div class="step <?php echo $selected_order['status'] == 'Completed' ? 'active' : ''; ?>">
                                    <div class="step-icon">6</div>
                                    <div class="step-label">Completed</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-selection">
                            <p>Order not found.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-selection">
                        <p>Select an order from the list to view details</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>