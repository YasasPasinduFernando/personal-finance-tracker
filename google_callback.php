<?php
session_start();
include 'database.php';

$client_id = '463085803378-lilic35gmnmk0omh7nncsm9icji49dlp.apps.googleusercontent.com';
$client_secret = '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';
$redirect_uri = 'https://financetracker.great-site.net/google_callback.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for access token
    $token_url = "https://oauth2.googleapis.com/token";
    $response = file_get_contents($token_url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code',
            ]),
        ],
    ]));
    
    $token_data = json_decode($response, true);
    $access_token = $token_data['access_token'];
    
    // Fetch user info
    $user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";
    $user_info = json_decode(file_get_contents($user_info_url . "?access_token=" . $access_token), true);
    
    // Get database connection
    $db = getDB();
    
    // Check if user exists in the database
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $user_info['email'], $user_info['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            // User exists - update Google ID if necessary
            if (!$user['google_id']) {
                $stmt = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $user_info['id'], $user['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $_SESSION['user_id'] = $user['id'];
        } else {
            // Create new user
            $stmt = $db->prepare("INSERT INTO users (username, email, google_id, password) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $random_password = 'GOOGLE_USER_' . bin2hex(random_bytes(16));
                $stmt->bind_param(
                    "ssss",
                    $user_info['name'],
                    $user_info['email'],
                    $user_info['id'],
                    $random_password
                );
                $stmt->execute();
                $_SESSION['user_id'] = $stmt->insert_id;
                $stmt->close();
            }
        }
    } else {
        die("Error preparing statement: " . $db->error);
    }
    
    // Close the database connection
    $db->close();
    
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php?error=google_auth_failed');
    exit();
}
