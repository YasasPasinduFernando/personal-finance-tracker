<?php
session_start();

$client_id = '';
$redirect_uri = 'https://financetracker.great-site.net';

$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?";
$google_login_url .= "client_id=" . urlencode($client_id);
$google_login_url .= "&redirect_uri=" . urlencode($redirect_uri);
$google_login_url .= "&response_type=code";
$google_login_url .= "&scope=" . urlencode("email profile");
$google_login_url .= "&access_type=online";

header("Location: " . $google_login_url);
exit();