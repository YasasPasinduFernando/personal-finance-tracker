<?php
session_start();

$client_id = '32323378-emtgfd8ja3mps29g633dd9vb4r948p23.apps.googleusercontent.com';
$redirect_uri = 'https://financetracker.wuaze.com/google_callback.php';

$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?";
$google_login_url .= "client_id=" . urlencode($client_id);
$google_login_url .= "&redirect_uri=" . urlencode($redirect_uri);
$google_login_url .= "&response_type=code";
$google_login_url .= "&scope=" . urlencode("email profile");
$google_login_url .= "&access_type=online";

header("Location: " . $google_login_url);
exit();