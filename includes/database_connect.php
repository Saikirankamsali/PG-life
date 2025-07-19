<?php
$conn = mysqli_connect("localhost", "root", "", "pglife");

if (mysqli_connect_errno()) {
    // Throw error message based on ajax or not
    echo "Failed to connect to MySQL! Please contact the admin.";
    return;
}

// ---
// SQL to create the user_favorites table for wishlist/favorites feature:
// CREATE TABLE user_favorites (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_id INT NOT NULL,
//     property_id INT NOT NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     UNIQUE KEY (user_id, property_id)
// );
// ---
