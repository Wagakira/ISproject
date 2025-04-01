<?php
$host="localhost";
$username="hannah_b";
$password="hannah1234$$";
$database="catering_system";
$conn= new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo " ";
?>