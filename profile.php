<?php
include("configure.php");

// Determine user_id (from session, URL, or default)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1);

if (!isset($_GET['caterer_id']) || empty($_GET['caterer_id'])) {
    die("Caterer not found!");
}

$caterer_id = $_GET['caterer_id'];

$caterer_query = "SELECT * FROM caterers WHERE caterer_id = " . (int)$caterer_id;
$result = $conn->query($caterer_query);

if (!$result || $result->num_rows == 0) {
    die("Caterer not found!");
}
$caterer = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $comment = $conn->real_escape_string($_POST['comment']);

    $insert_review_query = "INSERT INTO reviews (caterer_id, user_id, rating, comment) VALUES (" . (int)$caterer_id . ", " . (int)$user_id . ", " . (int)$rating . ", '$comment')";
    if ($conn->query($insert_review_query) === TRUE) {
        // Update caterer's average rating
        $avg_rating_query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE caterer_id = " . (int)$caterer_id;
        $avg_rating_result = $conn->query($avg_rating_query);
        $avg_rating = $avg_rating_result->fetch_assoc()['avg_rating'];

        $update_rating_query = "UPDATE caterers SET rating = " . (float)$avg_rating . " WHERE caterer_id = " . (int)$caterer_id;
        $conn->query($update_rating_query);

        header("Location: profile.php?caterer_id=$caterer_id&user_id=$user_id");
        exit();
    } else {
        echo "Error submitting review: " . $conn->error;
    }
}

$review_query = "SELECT * FROM reviews WHERE caterer_id = " . (int)$caterer_id;
$reviews = $conn->query($review_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($caterer['business_name']); ?> - Profile</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <header>
        <div class="logo">🍽️ CaterBind</div>
        <nav>
            <a href="explore.php">Home</a>
            <a href="services.html">Services</a>
            <a href="contact.html">Contact Us</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2><?php echo htmlspecialchars($caterer['business_name']); ?></h2>
        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($caterer['specialty']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($caterer['location']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($caterer['description']); ?></p>

        <h3>Availability Calendar</h3>
        <div class="calendar">
            <?php
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n'); 
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y'); 

            $prev_month = $month - 1;
            $prev_year = $year;
            if ($prev_month == 0) {
                $prev_month = 12;
                $prev_year--;
            }
            $next_month = $month + 1;
            $next_year = $year;
            if ($next_month == 13) {
                $next_month = 1;
                $next_year++;
            }
            ?>

            <div class="calendar-navigation">
                <a href="?caterer_id=<?php echo $caterer_id; ?>&user_id=<?php echo $user_id; ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">Previous Month</a>
                <form method="GET" action="" style="display: inline;">
                    <input type="hidden" name="caterer_id" value="<?php echo $caterer_id; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <label for="month">Month:</label>
                    <select name="month" id="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <label for="year">Year:</label>
                    <select name="year" id="year">
                        <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit">Go</button>
                </form>
                <a href="?caterer_id=<?php echo $caterer_id; ?>&user_id=<?php echo $user_id; ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">Next Month</a>
            </div>

            <?php
            $start_date = sprintf('%04d-%02d-01', $year, $month);
            $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));

            $availability_query = "SELECT date, is_available FROM availability WHERE caterer_id = " . (int)$caterer_id . " AND date BETWEEN '$start_date' AND '$end_date'";
            $availability_result = $conn->query($availability_query);

            $availability = [];
            if ($availability_result) {
                while ($row = $availability_result->fetch_assoc()) {
                    $availability[$row['date']] = $row['is_available'];
                }
            }
            ?>

            <h4><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h4>
            <div class="calendar-header">
                <span>Sun</span>
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
            </div>
            <div class="calendar-body">
                <?php
                $first_day = mktime(0, 0, 0, $month, 1, $year);
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $day_of_week = date('w', $first_day);

                for ($i = 0; $i < $day_of_week; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }

                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $is_available = isset($availability[$current_date]) ? $availability[$current_date] : 1; 
                    $class = $is_available ? 'available' : 'unavailable';
                    echo "<div class='calendar-day $class'>$day</div>";
                }

                $last_day_of_week = date('w', mktime(0, 0, 0, $month, $days_in_month, $year));
                for ($i = $last_day_of_week + 1; $i < 7; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                ?>
            </div>
            </div>

            </div>


    <h3>Contact Options</h3>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($caterer['phone_number']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($caterer['email']); ?></p>
    <a href="https://wa.me/254701998246" target="_blank" class="whatsapp-btn"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>

        <h3>Menu</h3>
        <?php if (isset($caterer['menu_url'])): ?>
            <a href="<?php echo htmlspecialchars($caterer['menu_url']); ?>" download class="btn-download"><i class="fas fa-download"></i> Download Menu (PDF)</a>
        <?php else: ?>
            <p>No menu PDF available.</p>
        <?php endif; ?>

        <h3>Order Now</h3>
        <?php if (isset($_GET['order']) && $_GET['order'] == 'success'): ?>
            <p class="success-message">Order placed successfully! We'll contact you with a price quote soon.</p>
        <?php elseif (isset($_GET['order']) && $_GET['order'] == 'error'): ?>
            <p class="error-message">
                <?php echo isset($_GET['reason']) && $_GET['reason'] == 'no_user_id' ? 'Error: User ID is required.' : 'Error placing order. Please try again.'; ?>
            </p>
        <?php endif; ?>
        <form method="POST" action="order.php" class="order-form">
            <input type="hidden" name="caterer_id" value="<?php echo $caterer_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            
            <label for="order_date">Event Date:</label>
            <input type="date" name="order_date" id="order_date" min="<?php echo date('Y-m-d'); ?>" required>
            
            <label for="guests">Number of Guests:</label>
            <input type="number" name="guests" id="guests" min="1" max="500" required>
            
            <label for="order_details">Menu Selections:</label>
            <textarea name="order_details" id="order_details" rows="4" placeholder="E.g., 50 Chicken Parmesan, 30 Caesar Salads" required></textarea>
            
            <label for="delivery_address">Delivery Address:</label>
            <input type="text" name="delivery_address" id="delivery_address" placeholder="123 Main St, City, State, ZIP" required>
            
            <label for="special_requests">Special Requests (Optional):</label>
            <textarea name="special_requests" id="special_requests" rows="2" placeholder="E.g., 10 vegetarian meals, include utensils"></textarea>
            
            <button type="submit" name="submit_order" class="btn-order">Place Order</button>
        </form>

        <h3>Share This Caterer</h3>
        <div class="share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://yourdomain.com/profile.php?caterer_id=' . $caterer_id . '&user_id=' . $user_id); ?>" target="_blank" class="share-btn"><i class="fab fa-facebook"></i> Share on Facebook</a>
            <a href="mailto:?subject=Check out this caterer&body=I found this amazing caterer on CaterBind: http://yourdomain.com/profile.php?caterer_id=<?php echo $caterer_id; ?>&user_id=<?php echo $user_id; ?>" class="share-btn"><i class="fas fa-envelope"></i> Share via Email</a>
        </div>

        <h3>Reviews & Ratings</h3>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <p><strong>User <?php echo htmlspecialchars($review['user_id']); ?>:</strong> 
                    <?php echo htmlspecialchars($review['comment']); ?>
                    (⭐ <?php echo $review['rating']; ?>/5)
                </p>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to leave a review!</p>
        <?php endif; ?>

        <h3>Leave a Review</h3>
        <form method="POST" action="">
            <div class="review-form">
                <label for="rating">Rating (1-5):</label>
                <select name="rating" id="rating" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <label for="comment">Comment:</label>
                <textarea name="comment" id="comment" rows="3" required></textarea>
                <button type="submit" name="submit_review">Submit Review</button>
            </div>
        </form>

        <div class="cta-buttons">
            <a href="message.php?caterer_id=<?php echo $caterer_id; ?>&user_id=<?php echo $user_id; ?>"><button class="btn-message">Receive a Quotation</button></a>
            <a href="booking.php?caterer_id=<?php echo $caterer_id; ?>&user_id=<?php echo $user_id; ?>"><button class="btn-booking">Book this Caterer</button></a>
        </div>

        <a href="explore.php">Back to Explore</a>
    </div>
</body>
</html>