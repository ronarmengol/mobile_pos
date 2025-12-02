<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '12345'); // Update with your DB password
define('DB_NAME', 'takeaway_pos');

// Attempt to connect to MySQL database
if (!defined('SKIP_DB_CONNECT')) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn === false) {
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }
}
?>
