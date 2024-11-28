<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Must be at the top
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

function sendOTPEmail($email, $otp) {
  

    

    $mail = new PHPMailer(true);

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
        $mail->Subject = 'Your OTP for Registration';
        $mail->Body = "
            <h2>Email Verification</h2>
            <p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>
            <p>This OTP will expire in 10 minutes.</p>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

// Check if registration process has started
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] !== 'verify_otp') {
    header("Location: register.php");
    exit();
}

// Check OTP resend request
if (isset($_GET['resend']) && $_GET['resend'] == 'otp') {
    // Check if enough time has passed since last OTP (prevent spam)
    $resend_cooldown = 60; // 1 minute cooldown between OTP requests
    if (isset($_SESSION['last_otp_sent']) && (time() - $_SESSION['last_otp_sent']) < $resend_cooldown) {
        $_SESSION['error'] = "Please wait 1 minute before requesting a new OTP.";
        header("Location: verify_otp.php");
        exit();
    }

    // Generate new OTP
    $new_otp = generateOTP();
    
    // Send new OTP
    if (sendOTPEmail($_SESSION['reg_email'], $new_otp)) {
        // Update OTP and timestamp in session
        $_SESSION['otp'] = $new_otp;
        $_SESSION['otp_created_at'] = time();
        $_SESSION['last_otp_sent'] = time();
        
        $_SESSION['message'] = "A new OTP has been sent to your email.";
        header("Location: verify_otp.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send new OTP. Please try again.";
        header("Location: verify_otp.php");
        exit();
    }
}

// Check OTP expiration (10 minutes)
$otp_expiration = 600; // 10 minutes
if (time() - $_SESSION['otp_created_at'] > $otp_expiration) {
    $_SESSION['error'] = "OTP has expired. Please restart registration.";
    unset($_SESSION['registration_step']);
    header("Location: register.php");
    exit();
}

function registerUser($conn, $name, $email, $password) {
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    
    // Bind parameters
    $stmt->bind_param("sss", $name, $email, $password);
    
    // Execute and return result
    return $stmt->execute();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $conn = getDB();
    
    // Check for connection error
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $entered_otp = trim($_POST['otp']);

    // Verify OTP
    if ($entered_otp == $_SESSION['otp']) {
        // OTP is correct, complete registration
        $name = $_SESSION['reg_name'];
        $email = $_SESSION['reg_email'];
        $password = $_SESSION['reg_password'];

        // Register user
        if (registerUser($conn, $name, $email, $password)) {
            // Clear session data
            unset($_SESSION['registration_step']);
            unset($_SESSION['reg_name']);
            unset($_SESSION['reg_email']);
            unset($_SESSION['reg_password']);
            unset($_SESSION['otp']);
            unset($_SESSION['otp_created_at']);

            $conn->close();

            // Set success message
            $_SESSION['message'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $conn->close();
            $_SESSION['error'] = "Registration failed. Please try again.";
            header("Location: register.php");
            exit();
        }
    } else {
        $conn->close();
        $_SESSION['error'] = "Invalid OTP. Please try again.";
        header("Location: verify_otp.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-envelope-open-text mr-4 text-green-500"></i>
                Verify Email
            </h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <p class="text-gray-600 text-center mb-4">
    An OTP has been sent to <?php echo $_SESSION['reg_email']; ?>. 
    Please check your email 
    <span style="color: blue; font-weight: bold;">Inbox</span> or 
    <span style="color: red; font-weight: bold;">Spam</span> 
    and enter the 6-digit code below.
</p>


            <form method="POST" class="space-y-4">
                <div class="flex items-center bg-blue-50 rounded-lg p-2">
                    <i class="fas fa-key text-blue-600 mr-3"></i>
                    <input type="text" 
                           name="otp" 
                           maxlength="6" 
                           placeholder="Enter 6-digit OTP" 
                           required 
                           class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600 text-center tracking-widest">
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 w-full flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Verify OTP
                </button>
            </form>

            <div class="mt-4 text-center">
    <button 
        onclick="window.location.href='register.php'" 
        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg transition duration-300 w-full flex items-center justify-center">
        <i class="fas fa-times-circle mr-2"></i>
        Close and Go Back to Registration
    </button>
</div>


            <div class="mt-6 text-center space-y-4">
                <p class="text-gray-600">
                    Didn't receive the OTP? 
                    <a href="verify_otp.php?resend=otp" class="text-blue-500 hover:text-blue-700 transition duration-300">
                        <i class="fas fa-redo mr-2"></i>Resend OTP
                    </a>
                </p>
                <p class="text-gray-600 text-sm">
                    Note: You can request a new OTP once per minute.
                </p>
            </div>
        </div>
    </div>
</body>
</html>