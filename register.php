<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Must be at the top of the file
require_once 'config.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP() {
    // Generate a 6-digit OTP
    return sprintf("%06d", mt_rand(1, 999999));
}

function checkUserExists($conn, $email, $username) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yasasnew@gmail.com';
        $mail->Password = 'idzj luaf cxwn rvtq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email details
        $mail->setFrom('yasasnew@gmail.com', 'Finance Tracker');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Registration';
        $mail->Body = "
            <h2>Email Verification</h2>
            <p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>
            <p>This OTP will expire in 10 minutes.</p>
        ";
        
        // Send email and return result
        return $mail->send();
    } catch (Exception $e) {
        // Log the error
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $conn = getDB();
    
    // Check for connection error
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    // Check if user already exists
    if (checkUserExists($conn, $email, $name)) {
        $_SESSION['error'] = "An account with this email or username already exists.";
        $conn->close();
        header("Location: register.php");
        exit();
    }

    // Generate and send OTP
    $otp = generateOTP();
    
    // Send OTP email
    if (sendOTPEmail($email, $otp)) {
        // Store registration details in session
        $_SESSION['reg_name'] = $name;
        $_SESSION['reg_email'] = $email;
        $_SESSION['reg_password'] = password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_created_at'] = time();
        $_SESSION['registration_step'] = 'verify_otp';

        $conn->close();
        header("Location: verify_otp.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send OTP. Please try again.";
        $conn->close();
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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