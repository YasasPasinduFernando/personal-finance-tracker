<?php
// register.php
session_start();
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (checkUserExists($email, $name)) {
        $_SESSION['message'] = "An account with this email or username already exists. Please login.";
        header("Location: login.php");
        exit();
    }
    

    if (registerUser($name, $email, $password)) {
        $_SESSION['message'] = "Registration successful! Please login.";
        header("Location: login.php");
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
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
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-user-plus mr-4 text-green-500"></i>
                Register
            </h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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
                <button type="submit" class=" btn-add-transaction bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 w-full flex items-center justify-center">
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