<?php
session_start();
include("configure.php");

//if (!isset($_SESSION['user_id'])) {
    //header("Location: login.php");
    //exit();
//}

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$specialty_filter = isset($_GET['specialty']) ? $_GET['specialty'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating_desc';

$query = "SELECT * FROM caterers WHERE 1=1";
if ($search_query) {
    $search_query = $conn->real_escape_string($search_query);
    $query .= " AND (business_name LIKE '%$search_query%' OR specialty LIKE '%$search_query%')";
}
if ($location_filter) {
    $location_filter = $conn->real_escape_string($location_filter);
    $query .= " AND location LIKE '%$location_filter%'";
}
if ($specialty_filter) {
    $specialty_filter = $conn->real_escape_string($specialty_filter);
    $query .= " AND specialty LIKE '%$specialty_filter%'";
}

if ($sort == 'rating_desc') {
    $query .= " ORDER BY rating DESC";
} elseif ($sort == 'rating_asc') {
    $query .= " ORDER BY rating ASC";
}

$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$total_query = "SELECT COUNT(*) as total FROM caterers WHERE 1=1";
if ($search_query) {
    $total_query .= " AND (business_name LIKE '%$search_query%' OR specialty LIKE '%$search_query%')";
}
if ($location_filter) {
    $total_query .= " AND location LIKE '%$location_filter%'";
}
if ($specialty_filter) {
    $total_query .= " AND specialty LIKE '%$specialty_filter%'";
}
$total_result = $conn->query($total_query);
$total_caterers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_caterers / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Caterers | CaterBind</title>
    <link rel="stylesheet" href="explore.css">
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

    <div class="dashboard">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-nav">
                <div class="nav-item active">
                    <i class="fa-solid fa-house"></i>
                    <a href="explore.php"><span>🏚Browse</span></a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-utensils"></i>
                    <a href="order.php"><span>My Orders</span></a>
                </div>
                <!--<div class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <a href="message.php"><span>Messages</span></a>
                </div>-->
                <div class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <a href="booking.php"><span>Bookings</span></a>
                </div>
               <!-- <div class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <a href="payment.php"><span>Payments</span></a>
                </div>-->
                <div class="nav-item">
                    <i class="far fa-user"></i>
                    <a href="userprofile.php"><span>Profile</span></a>
                </div>
            </div>
        </div>

        <div class="container">
            <h2>Find a Caterer</h2>

            <div class="search-filter">
                <form method="GET" action="explore.php">
                    <div class="search-bar">
                        <input type="text" name="search" placeholder="Search by name or specialty..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filters">
                        <input type="text" name="location" placeholder="Filter by location..." value="<?php echo htmlspecialchars($location_filter); ?>">
                        <input type="text" name="specialty" placeholder="Filter by specialty..." value="<?php echo htmlspecialchars($specialty_filter); ?>">
                        <select name="sort">
                            <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Sort by Rating (High to Low)</option>
                            <option value="rating_asc" <?php echo $sort == 'rating_asc' ? 'selected' : ''; ?>>Sort by Rating (Low to High)</option>
                        </select>
                        <button type="submit">Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="caterers-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($caterer = $result->fetch_assoc()): ?>
                        <div class="caterer">
                            <div class="caterer-header">
                                <h4><?php echo htmlspecialchars($caterer['business_name']); ?></h4>
                                <p><i class="fas fa-utensils"></i> Specialty: <?php echo htmlspecialchars($caterer['specialty']); ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($caterer['location']); ?></p>                        
                            </div>

                            <section class="gallery-section">
                                <h2><i class="fas fa-images"></i> Preview Menu</h2>
                                <div class="image-container">
                                    <?php
                                    $caterer_id = $caterer['caterer_id'];
                                    $imageQuery = "SELECT image_url, dish_name FROM menu WHERE caterer_id = $caterer_id LIMIT 4";
                                    $imageResult = $conn->query($imageQuery);

                                    if ($imageResult->num_rows > 0):
                                        while ($image = $imageResult->fetch_assoc()):
                                    ?>
                                        <div class="item">
                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['dish_name']); ?>">
                                            <p><?php echo htmlspecialchars($image['dish_name']); ?></p>
                                        </div>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <div class="no-images">No menu images available</div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <div class="caterer-footer">
                                <p><?php echo htmlspecialchars($caterer['description']); ?></p>
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo number_format($caterer['rating'] ?? 0, 1); ?>/5.0</span>
                                </div>
                                <a href="profile.php?caterer_id=<?php echo $caterer['caterer_id']; ?>">
                                    <i class="fas fa-user-circle"></i> View Profile
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No caterers found matching your criteria.</p>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="explore.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&location=<?php echo urlencode($location_filter); ?>&specialty=<?php echo urlencode($specialty_filter); ?>&sort=<?php echo $sort; ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="explore.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&location=<?php echo urlencode($location_filter); ?>&specialty=<?php echo urlencode($specialty_filter); ?>&sort=<?php echo $sort; ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>