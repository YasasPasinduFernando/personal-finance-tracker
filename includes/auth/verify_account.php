<?php
require_once 'config.php';
session_start();

// Check if token is provided
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "No verification token provided.";
    header("Location: register.php");
    exit();
}

$token = $_GET['token'];
$conn = getDB();

try {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT * FROM pending_verifications 
                           WHERE verification_token = ? 
                           AND expires_at > NOW() 
                           AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Invalid or expired verification link. Please register again.";
        header("Location: register.php");
        exit();
    }

    // Get the pending verification data
    $verification = $result->fetch_assoc();
    
    // Start transaction
    $conn->begin_transaction();

    // Insert into users table
    $insertStmt = $conn->prepare("INSERT INTO users (username, email, password) 
                                 VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", 
        $verification['username'],
        $verification['email'],
        $verification['password_hash']
    );

// In verify_account.php, modify the success part:
if (!$insertStmt->execute()) {
    throw new Exception("Failed to create user account.");
}

// Mark verification as complete
$updateStmt = $conn->prepare("UPDATE pending_verifications 
                             SET is_verified = 1 
                             WHERE verification_token = ?");
$updateStmt->bind_param("s", $token);

if (!$updateStmt->execute()) {
    throw new Exception("Failed to update verification status.");
}

// Commit transaction
$conn->commit();

// Store the username/email for auto-fill and success message
$_SESSION['last_username'] = $verification['username'];
$_SESSION['message'] = "Email verified successfully! You can now login with your credentials.";

header("Location: login.php");
exit();

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    error_log("Verification error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred during verification. Please try again.";
    header("Location: register.php");
    exit();
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
            <h2 class="mt-4 text-xl font-semibold text-gray-700">Verifying your account...</h2>
            <p class="mt-2 text-gray-500">Please wait while we verify your email address.</p>
        </div>
    </div>
</body>
</html>