<?php
session_start();
require_once "../includes/database_connect.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

if ($property_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid property ID"]);
    exit;
}

// Check if already favorited
$sql = "SELECT * FROM user_favorites WHERE user_id = $user_id AND property_id = $property_id";
$result = mysqli_query($conn, $sql);
if (!$result) {
    echo json_encode(["status" => "error", "message" => "DB error"]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    // Remove from favorites
    $sql = "DELETE FROM user_favorites WHERE user_id = $user_id AND property_id = $property_id";
    mysqli_query($conn, $sql);
    echo json_encode(["status" => "success", "action" => "removed"]);
    exit;
} else {
    // Add to favorites
    $sql = "INSERT INTO user_favorites (user_id, property_id) VALUES ($user_id, $property_id)";
    mysqli_query($conn, $sql);
    echo json_encode(["status" => "success", "action" => "added"]);
    exit;
} 