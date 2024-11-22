<?php
// config.php
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'finance_tracker');

function getDB() {
    $dbConnection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($dbConnection->connect_error) {
        die("Connection failed: " . $dbConnection->connect_error);
    }
    return $dbConnection;
}
?>