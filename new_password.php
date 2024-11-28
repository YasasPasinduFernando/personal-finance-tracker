<?php
// new_password.php
session_start();
require_once 'config.php';

if (!isset($_GET['token'])) {
    header("Location: login.php");
    exit();
}

$conn = getDB();
$token = $conn->real_escape_string($_GET['token']);

// Verify token validity
$query = "SELECT * FROM password_resets WHERE token = '$token' AND used = 0 AND expiry > NOW()";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Invalid or expired reset link.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        $reset = $result->fetch_assoc();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset['email'];
        
        // Update password
        $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email'";
        if ($conn->query($updateQuery)) {
            // Mark reset token as used
            $markUsedQuery = "UPDATE password_resets SET used = 1 WHERE token = '$token'";
            $conn->query($markUsedQuery);
            
            $_SESSION['message'] = "Password has been successfully reset. Please login with your new password.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-lock mr-4 text-green-500"></i>
                New Password
            </h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="relative flex items-center bg-blue-50 rounded-lg p-2">
                    <i class="fas fa-lock text-blue-600 mr-3"></i>
                    <input type="password" 
                           id="password"
                           name="password" 
                           placeholder="New Password" 
                           required 
                           class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600">
                    <button type="button" class="text-blue-600 toggle-password" data-target="password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <div class="relative flex items-center bg-blue-50 rounded-lg p-2">
                    <i class="fas fa-lock text-blue-600 mr-3"></i>
                    <input type="password" 
                           id="confirm_password"
                           name="confirm_password" 
                           placeholder="Confirm New Password" 
                           required 
                           class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600">
                    <button type="button" class="text-blue-600 toggle-password" data-target="confirm_password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition duration-300 w-full flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i>
                    Set New Password
                </button>
            </form>
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
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const eyeIcon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>