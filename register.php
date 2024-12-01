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
        $mail->Password = 'jigz ebsf clqp zgbj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('yasasnew@gmail.com', 'Finance Tracker');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        
       
        $mail->Subject = "Verify Your Finance Tracker Account";

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; line-height: 1.6; }
                .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background-color: #4A90E2; color: white; text-align: center; padding: 20px; border-radius: 6px 6px 0 0; }
                .content { padding: 30px; background-color: #ffffff; }
                .btn {
                    display: inline-block; 
                    padding: 12px 30px; 
                    background-color: #4A90E2; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    font-weight: bold;
                    margin: 20px 0;
                    text-align: center;
                    transition: background-color 0.3s;
                }
                .btn:hover {
                    background-color: #357ABD;
                }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
                .verification-link { word-break: break-all; color: #666; font-size: 12px; margin-top: 10px; }
                .features { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .otp-box { background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0; }
                .creator-info { font-style: italic; color: #666; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Finance Tracker</h1>
                </div>
                <div class='content'>
                    <p>Hi {$name},</p>
                    <p>Welcome to Finance Tracker! Simplify your financial journey with ease.</p>
                    
                    <div class='features'>
                        <p><strong>What You Can Do:</strong></p>
                        <ul>
                            <li>Monitor your income and expenses in real-time</li>
                            <li>Set financial goals and track progress</li>
                            <li>Enjoy secure and private financial management</li>
                        </ul>
                    </div>
        
                    <div class='otp-box'>
                        <p><strong>Your OTP:</strong></p>
                        <h2 style='color: #4A90E2; margin: 10px 0;'>{$otp}</h2>
                        <p style='color: #666;'>(Expires in 10 minutes)</p>
                    </div>
        
                    <p>Verify your account by clicking the button below:</p>
                    <p style='text-align: center;'><a href='{$verificationLink}' class='btn'>Verify My Account</a></p>
                    <p class='verification-link'>Or copy and paste this link: {$verificationLink}</p>
                    <p style='color: #666;'>(Expires in 24 hours)</p>
                    <p>If you didn't create this account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Finance Tracker. All rights reserved.</p>
                    <p>Powered by SLTC Research University</p>
                    <p class='creator-info'>Created by: Yasas Pasindu Fernando (23DA2-0318)<br>Student @ SLTC Research University</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hi {$name},\n\n
        Welcome to Finance Tracker! Simplify your financial journey with ease.\n\n
        What You Can Do:\n
        - Monitor your income and expenses in real-time\n
        - Set financial goals and track progress\n
        - Enjoy secure and private financial management\n\n
        Your OTP: {$otp} (Expires in 10 minutes)\n\n
        To verify your account, click the following link:\n
        {$verificationLink}\n\n
        (Expires in 24 hours)
        If you didn't create this account, please ignore this email.\n\n
        © 2024 Finance Tracker\n
        Powered by SLTC Research University\n
        Created by: Yasas Pasindu Fernando (23DA2-0318)";
        


        
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
                <!-- Google Sign-in Button -->
<div class="text-center my-6">
    <a href="google_login.php" 
       class="group bg-white hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-lg transition-all duration-300 w-full inline-flex items-center justify-center border border-gray-300 shadow-sm hover:shadow-md">
        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google
    </a>
</div>

<!-- Register Button -->
<button type="submit" 
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-all duration-300 w-full flex items-center justify-center shadow-lg hover:shadow-xl font-medium">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
    </svg>
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