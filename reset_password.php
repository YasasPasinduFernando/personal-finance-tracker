<?php
// reset_password.php
session_start();
function getBaseURL() {
    $host = $_SERVER['HTTP_HOST'];
    if ($host === 'localhost' || $host === 'localhost:3307') {
        return 'http://localhost/finance_tracker';
    } else {
        return 'https://financetracker.great-site.net';
    }
}
require_once 'config.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateResetToken() {
    return bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDB();
    $usernameOrEmail = $conn->real_escape_string($_POST['usernameOrEmail']);
    
    // Check if user exists
    $query = "SELECT email FROM users WHERE username = '$usernameOrEmail' OR email = '$usernameOrEmail'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userEmail = $user['email'];
        $token = generateResetToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing reset tokens for this email
        $deleteQuery = "DELETE FROM password_resets WHERE email = '$userEmail'";
        $conn->query($deleteQuery);
        
        // Insert new reset token
        $insertQuery = "INSERT INTO password_resets (email, token, expiry, created_at) 
                       VALUES ('$userEmail', '$token', '$expiry', NOW())";
        
        if ($conn->query($insertQuery)) {
            $mail = new PHPMailer(true);
            
            try {
                $mail = new PHPMailer(true);
                
                // SMTP settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'yasasnew@gmail.com';
                $mail->Password = 'idzj luaf cxwn rvtq';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Email content
                $mail->setFrom('yasasnew@gmail.com', 'Finance Tracker');
                $mail->addAddress($userEmail);
                
                // Create reset link using dynamic base URL
                $baseURL = getBaseURL();
                $resetLink = $baseURL . "/new_password.php?token=" . $token;
                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>You requested a password reset. Click the link below to reset your password:</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this reset, please ignore this email.</p>
                ";
                
                $mail->send();
                $_SESSION['message'] = "Password reset instructions have been sent to your email.";
                header("Location: login.php");
                exit();
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error sending reset email: " . $mail->ErrorInfo;
                header("Location: reset_password.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Database error. Please try again later.";
        }
    } else {
        $_SESSION['error'] = "No account found with that email/username.";
    }
    
    $conn->close();
    
    if (isset($_SESSION['error'])) {
        header("Location: reset_password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #1f2937; /* This is the gray-800 color you're using */
    color: white;
    padding: 1.5rem 0; /* py-6 equivalent */
    z-index: 10;
}

.w-full.max-w-md {
    margin-bottom: 200px;
}
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-key mr-4 text-yellow-500"></i>
                Reset Password
            </h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="flex items-center bg-blue-50 rounded-lg p-2">
                    <i class="fas fa-envelope text-blue-600 mr-3"></i>
                    <input type="text" 
                           name="usernameOrEmail" 
                           placeholder="Enter your email or username" 
                           required 
                           class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600">
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 w-full flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Reset Link
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-500 hover:text-blue-700 transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full mt-8">
        <div class="container mx-auto text-center">
            <p class="mb-2">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400 text-2xl">
                <a href="https://github.com/YasasPasinduFernando" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://www.linkedin.com/in/yasas-pasindu-fernando-893b292b2/" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://x.com/YPasiduFernando?s=09" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
        </div>
    </footer>

</body>
</html>