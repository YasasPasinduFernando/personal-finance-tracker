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
        $mail->Password = '---------------';
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
    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account");
        }

        // Update any pending verifications
        $updateStmt = $conn->prepare("UPDATE pending_verifications 
                                    SET is_verified = 1 
                                    WHERE email = ?");
        $updateStmt->bind_param("s", $email);
        $updateStmt->execute();

        // Commit transaction
        $conn->commit();
        return true;

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDB();
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $entered_otp = trim($_POST['otp']);

    // Verify OTP
    if ($entered_otp == $_SESSION['otp']) {
        // Verify all required session data exists
        if (!isset($_SESSION['reg_name']) || !isset($_SESSION['reg_email']) || !isset($_SESSION['reg_password'])) {
            $_SESSION['error'] = "Registration session expired. Please register again.";
            header("Location: register.php");
            exit();
        }

        $name = $_SESSION['reg_name'];
        $email = $_SESSION['reg_email'];
        $password = $_SESSION['reg_password'];

        // Register user
        if (registerUser($conn, $name, $email, $password)) {
            // Store the username/email for auto-fill
            $_SESSION['last_username'] = $name; // or $email if you prefer
            
            // Store success message
            $_SESSION['message'] = "Account verified successfully! You can now login with your credentials.";
            
            // Clear registration session data
            $sessionVars = [
                'registration_step', 'reg_name', 'reg_email', 
                'reg_password', 'otp', 'otp_created_at'
            ];
            foreach ($sessionVars as $var) {
                unset($_SESSION[$var]);
            }
        
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid OTP. Please try again.";
        header("Location: verify_otp.php");
        exit();
    }
    $conn->close();}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Finance Tracker</title>
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
    An <b>OTP and a verification link</b> has been sent to <b> <?php echo $_SESSION['reg_email']; ?></b>. 
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