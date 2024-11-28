<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

function checkUserExists($conn, $email, $username) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function storeVerificationData($conn, $email, $username, $hashedPassword, $verificationToken) {
    $stmt = $conn->prepare("INSERT INTO pending_verifications 
                           (email, username, password_hash, verification_token, created_at, expires_at) 
                           VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))");
    $stmt->bind_param("ssss", $email, $username, $hashedPassword, $verificationToken);
    return $stmt->execute();
}

function getBaseURL() {
    $host = $_SERVER['HTTP_HOST'];
    if ($host === 'localhost' || $host === 'localhost:3307') {
        return 'http://localhost/finance_tracker';
    } else {
        return 'https://financetracker.great-site.net';
    }
}

function sendOTPEmail($email, $otp, $name, $verificationToken) {
    $mail = new PHPMailer(true);
    $baseURL = getBaseURL();
    $verificationLink = $baseURL . "/verify_account.php?token=" . $verificationToken;

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yasasnew@gmail.com';
        $mail->Password = 'idzj luaf cxwn rvtq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('yasasnew@gmail.com', 'Finance Tracker');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Finance Tracker - Verify Your Account';
        
        // Your existing email template
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4A90E2; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f4f4f4; }
                .footer { text-align: center; color: #777; margin-top: 20px; }
                .btn { 
                    display: inline-block; 
                    padding: 10px 20px; 
                    background-color: #4A90E2; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Finance Tracker</h1>
                </div>
                <div class='content'>
                    <h2>Hi " . htmlspecialchars($name) . ",</h2>
                    
                    <p>Simplify your financial journey. Track, manage, and grow your money with ease.</p>
                    
                    <h3>What You Can Do:</h3>
                    <ul>
                        <li>Monitor your income and expenses in real-time</li>
                        <li>Set financial goals and track your progress</li>
                        <li>Secure and private financial management</li>
                    </ul>
                    
                    <p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>
                    
                    <p>To complete your registration, please verify your email by clicking the button below:</p>
                    
                    <a href='{$verificationLink}' class='btn'>Verify My Account</a>
                    
                    <p>If you didn't create an account, please ignore this email.</p>
                    
                    <p>The OTP and verification link will expire in 24 hours.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Finance Tracker. All rights reserved.</p>
                    <p>Powered by SLTC Research University</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDB();
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    if (checkUserExists($conn, $email, $name)) {
        $_SESSION['error'] = "An account with this email or username already exists.";
        $conn->close();
        header("Location: register.php");
        exit();
    }

    // Generate OTP and verification token
    $otp = generateOTP();
    $verificationToken = generateVerificationToken();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Store verification data

if (storeVerificationData($conn, $email, $name, $hashedPassword, $verificationToken)) {
    // Send email with both OTP and verification link
    if (sendOTPEmail($email, $otp, $name, $verificationToken)) {
        // Store ALL necessary registration data in session
        $_SESSION['reg_name'] = $name;
        $_SESSION['reg_email'] = $email;
        $_SESSION['reg_password'] = $hashedPassword; // Store the hashed password
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_created_at'] = time();
        $_SESSION['registration_step'] = 'verify_otp';

        $conn->close();
        header("Location: verify_otp.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send verification email. Please try again.";
    }
}
    
    $conn->close();
    header("Location: register.php");
    exit();
}

// Your existing HTML code remains the same...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
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
                <i class="fas fa-user-plus mr-4 text-green-500"></i>
                Register
            </h1>

            <?php 
            // Display any error messages
            if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']); 
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <div class="flex items-center bg-blue-50 rounded-lg p-2">
                        <i class="fas fa-user text-blue-600 mr-3"></i>
                        <input type="text" name="name" placeholder="Username (e.g., saman123)" required 
                               class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600">
                    </div>
                </div>
                <div>
                    <div class="flex items-center bg-green-50 rounded-lg p-2">
                        <i class="fas fa-envelope text-green-600 mr-3"></i>
                        <input type="email" name="email" placeholder="Email (e.g., saman@example.com)" required 
                               class="bg-transparent w-full focus:outline-none text-green-800 placeholder-green-600">
                    </div>
                </div>
                <div>
                    <div class="flex items-center bg-purple-50 rounded-lg p-2">
                        <i class="fas fa-lock text-purple-600 mr-3"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required 
                               class="bg-transparent w-full focus:outline-none text-purple-800 placeholder-purple-600">
                        <button type="button" onclick="togglePassword()" class="text-purple-600 focus:outline-none">
                            <i class="fas fa-eye" id="togglePassword"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-add-transaction bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 w-full flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Register
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-500 hover:text-blue-700 transition duration-300">
                        Login here
                    </a>
                </p>
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

    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePassword');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>