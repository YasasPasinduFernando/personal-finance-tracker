<?php
// login.php
session_start();
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the submitted username/email and password
    $usernameOrEmail = $_POST['usernameOrEmail'];
    $password = $_POST['password'];
    
    // Call the updated loginUser function
    $userId = loginUser($usernameOrEmail, $password);
    if ($userId) {
        $_SESSION['user_id'] = $userId;
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid username/email or password";
        $_SESSION['login_error'] = true; // Add this line to track password error
        $_SESSION['last_username'] = $usernameOrEmail; // Store the username/email
        header("Location: login.php");
        exit();
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Login</title>
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
                <i class="fas fa-sign-in-alt mr-4 text-green-500"></i>
                Login
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

            <form method="POST" class="space-y-4">
                <div>
                    <div class="flex items-center bg-blue-50 rounded-lg p-2">
                        <i class="fas fa-user text-blue-600 mr-3"></i>
                        <input type="text" name="usernameOrEmail" placeholder="Username or Email" required 
                               class="bg-transparent w-full focus:outline-none text-blue-800 placeholder-blue-600">
                    </div>
                </div>


                <div>
    <div class="flex items-center <?php echo isset($_SESSION['login_error']) ? 'bg-red-50 border-red-500 border' : 'bg-green-50'; ?> rounded-lg p-2">
        <i class="fas fa-lock <?php echo isset($_SESSION['login_error']) ? 'text-red-600' : 'text-green-600'; ?> mr-3"></i>
        <input type="password" 
               name="password" 
               id="loginPassword" 
               placeholder="Password" 
               required 
               class="bg-transparent w-full focus:outline-none <?php echo isset($_SESSION['login_error']) ? 'text-red-800 placeholder-red-600' : 'text-green-800 placeholder-green-600'; ?>">
        <button type="button" 
                onclick="toggleLoginPassword()" 
                class="<?php echo isset($_SESSION['login_error']) ? 'text-red-600' : 'text-green-600'; ?> focus:outline-none">
            <i class="fas fa-eye" id="toggleLoginPassword"></i>
        </button>
    </div>
    <?php if (isset($_SESSION['login_error'])): ?>
    <div class="mt-2 text-red-600 text-sm flex items-center justify-between">
        <span class="flex items-center">
            <i class="fas fa-exclamation-circle mr-1"></i>
            Incorrect password
        </span>
        <a href="reset_password.php" class="text-blue-500 hover:text-blue-700 text-sm">
            Forgot password?
        </a>
    </div>
    <?php endif; ?>
</div>


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

                <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-all duration-300 w-full flex items-center justify-center shadow-lg hover:shadow-xl font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-blue-500 hover:text-blue-700 transition duration-300">
                        Register here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- google_auth_failed -->
<?php if (isset($_GET['error']) && $_GET['error'] == 'google_auth_failed'): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        Google authentication failed. Please try again.
    </div>
<?php endif; ?>

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
// Automatically fill in the username/email if there was a login error
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['last_username'])): ?>
    document.querySelector('input[name="usernameOrEmail"]').value = <?php echo json_encode($_SESSION['last_username']); ?>;
    <?php endif; ?>
});

// Clear error styling when user starts typing a new password
document.getElementById('loginPassword').addEventListener('input', function() {
    this.closest('div').classList.remove('border-red-500', 'border');
    this.classList.remove('text-red-800', 'placeholder-red-600');
    this.classList.add('text-green-800', 'placeholder-green-600');
});

// Function to toggle password visibility
function toggleLoginPassword() {
    const passwordInput = document.getElementById('loginPassword');
    const toggleIcon = document.getElementById('toggleLoginPassword');
    
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

<?php
// Clean up session variables after displaying them
unset($_SESSION['login_error']);
unset($_SESSION['last_username']);
?>
