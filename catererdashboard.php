<?php
session_start();
include 'configure.php'; // Database connection
// Check if caterer is logged in
if (!isset($_SESSION['caterer_id'])) {
    header("Location: login.html");
    exit();
}

$caterer_id = $_SESSION['caterer_id'];
$error = '';
$stats = [];
$today = date('Y-m-d');

// Fetch dashboard statistics
$stats['total_orders'] = $conn->query("SELECT COUNT(*) FROM orders WHERE caterer_id = $caterer_id")->fetch_row()[0];
$stats['pending_orders'] = $conn->query("SELECT COUNT(*) FROM orders WHERE caterer_id = $caterer_id AND status = 'Pending'")->fetch_row()[0];
$stats['today_orders'] = $conn->query("SELECT COUNT(*) FROM orders WHERE caterer_id = $caterer_id AND DATE(order_date) = '$today'")->fetch_row()[0];
$stats['month_revenue'] = $conn->query("SELECT SUM(total_price) FROM orders WHERE caterer_id = $caterer_id AND MONTH(order_date) = MONTH(CURRENT_DATE())")->fetch_row()[0];

// Fetch recent orders
$recent_orders = [];
$result = $conn->query("SELECT o.order_id, o.total_price, o.status, o.order_date, c.full_name 
                       FROM orders o 
                       JOIN clients c ON o.client_id = c.client_id 
                       WHERE o.caterer_id = $caterer_id 
                       ORDER BY o.order_date DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Fetch upcoming bookings
$upcoming_bookings = [];
$result = $conn->query("SELECT b.booking_id, b.event_date, b.event_time, b.guests, c.full_name 
                       FROM bookings b 
                       JOIN clients c ON b.client_id = c.client_id 
                       WHERE b.caterer_id = $caterer_id AND b.event_date >= '$today' AND b.status = 'Confirmed'
                       ORDER BY b.event_date ASC, b.event_time ASC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $upcoming_bookings[] = $row;
    }
}

// Fetch recent reviews
$recent_reviews = [];
$result = $conn->query("SELECT r.rating, r.comment, r.created_at, c.full_name 
                       FROM reviews r 
                       JOIN clients c ON r.client_id = c.client_id 
                       WHERE r.caterer_id = $caterer_id 
                       ORDER BY r.created_at DESC LIMIT 3");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_reviews[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caterer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #2196F3;
            --warning: #FFC107;
            --danger: #F44336;
            --light: #f5f5f5;
            --dark: #212121;
            --gray: #757575;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .welcome-message h1 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .notification-bell {
            position: relative;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
        }

        .dashboard-section {
            background-color: var(--white);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            color: var(--primary);
        }

        .section-header a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }

        .section-header a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: var(--gray);
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-confirmed {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .status-preparing {
            background-color: #E3F2FD;
            color: #1565C0;
        }

        .status-completed {
            background-color: #F1F8E9;
            color: #558B2F;
        }

        .review-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-rating {
            font-size: 1.5rem;
            color: var(--warning);
            margin-right: 15px;
        }

        .review-content {
            flex: 1;
        }

        .review-author {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .review-date {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .review-comment {
            color: var(--dark);
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="welcome-message">
                <h1>Welcome Back!</h1>
                <p>Here's what's happening with your business today</p>
            </div>
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-utensils"></i></div>
                <h3>Total Orders</h3>
                <div class="value"><?php echo $stats['total_orders']; ?></div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
                <h3>Today's Orders</h3>
                <div class="value"><?php echo $stats['today_orders']; ?></div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Monthly Revenue</h3>
                <div class="value">Ksh <?php echo number_format($stats['month_revenue'], 2); ?></div>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fas fa-list-ul"></i> Recent Orders</h2>
                <a href="orders.php">View All</a>
            </div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td>Ksh <?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i a', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fas fa-calendar-alt"></i> Upcoming Bookings</h2>
                <a href="bookings.php">View All</a>
            </div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>Event Date</th>
                            <th>Client</th>
                            <th>Time</th>
                            <th>Guests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_bookings as $booking): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                <td><?php echo date('g:i a', strtotime($booking['event_time'])); ?></td>
                                <td><?php echo $booking['guests']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2><i class="fas fa-star"></i> Recent Reviews</h2>
                <a href="reviews.php">View All</a>
            </div>
            <div class="section-body">
                <?php foreach ($recent_reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-rating">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <div class="review-content">
                            <div class="review-author"><?php echo htmlspecialchars($review['full_name']); ?></div>
                            <div class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                            <div class="review-comment"><?php echo htmlspecialchars($review['comment'] ?? 'No comment'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Notification bell click handler
        document.querySelector('.notification-bell').addEventListener('click', function() {
            // In a real app, this would fetch and display notifications
            alert('Notifications feature will be implemented here');
        });

        // Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);

        // Status color coding for orders
        document.querySelectorAll('.status').forEach(status => {
            const statusText = status.textContent.trim();
            if (statusText === 'Pending') {
                status.classList.add('status-pending');
            } else if (statusText === 'Confirmed') {
                status.classList.add('status-confirmed');
            } else if (statusText === 'Preparing') {
                status.classList.add('status-preparing');
            } else if (statusText === 'Completed') {
                status.classList.add('status-completed');
            }
        });
    </script>
</body>
</html>