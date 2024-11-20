<?php
// getFollowerCount.php

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evanscode";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

// Get follower count
$sql = "SELECT COUNT(*) as count FROM subscribers";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'count' => $row['count']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch follower count']);
}

$conn->close();
?>
