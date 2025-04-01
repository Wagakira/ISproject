<?php
session_start();
include("configure.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $caterer_id = (int)$_POST['caterer_id'];
    $order_date = $conn->real_escape_string($_POST['order_date']);
    $guests = (int)$_POST['guests'];
    $order_details = $conn->real_escape_string($_POST['order_details']);
    $delivery_address = $conn->real_escape_string($_POST['delivery_address']);
    $order_status = 'pending';
    $order_timestamp = date('Y-m-d H:i:s');

    $insert_order_query = "INSERT INTO orders (user_id, caterer_id, order_date, guests, order_details, delivery_address, order_status, order_timestamp) 
                          VALUES ($user_id, $caterer_id, '$order_date', $guests, '$order_details', '$delivery_address', '$order_status', '$order_timestamp')";

    if ($conn->query($insert_order_query) === TRUE) {
        header("Location: profile.php?caterer_id=$caterer_id&order=success");
    } else {
        header("Location: profile.php?caterer_id=$caterer_id&order=error");
    }
    exit();
}
?>