<?php
// config.php
define('DB_HOST', 'abcd');
define('DB_USER', 'asas');
define('DB_PASS', 'asssss');
define('DB_NAME', 'as');

function getDB() {
    $dbConnection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($dbConnection->connect_error) {
        die("Connection failed: " . $dbConnection->connect_error);
    }
    return $dbConnection;
}
?>