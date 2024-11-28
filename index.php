<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-wallet text-green-500 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-blue-800">Finance Tracker</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-600 hover:text-blue-600 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login
                    </a>
                    <a href="register.php" class="btn-add-transaction bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12 flex-grow">
        <div class="max-w-xl mx-auto bg-white shadow-2xl rounded-xl p-8 text-center">
            <div class="flex justify-end mb-4">
                <a href="unguide.pdf" download class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>Download User Guide</span>
                </a>
            </div>
            
            <h1 class="text-4xl font-extrabold text-blue-800 mb-4 flex items-center justify-center">
                Welcome to Finance Tracker
            </h1>
            
            <p class="text-gray-600 mb-6 text-lg">
                Simplify your financial journey. Track, manage, and grow your money with ease.
            </p>
            
            <div class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-lg flex items-center">
                    <i class="fas fa-chart-line mr-3 text-blue-600"></i>
                    <p class="text-blue-800">Monitor your income and expenses in real-time</p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg flex items-center">
                    <i class="fas fa-piggy-bank mr-3 text-green-600"></i>
                    <p class="text-green-800">Set financial goals and track your progress</p>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg flex items-center">
                    <i class="fas fa-lock mr-3 text-purple-600"></i>
                    <p class="text-purple-800">Secure and private financial management</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Registration Reminder Modal -->
    <div id="registerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-blue-800">Welcome to Finance Tracker!</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-gray-600 mb-6">
                It looks like you don't have an account yet. Register now to start tracking your finances and achieving your financial goals!
            </p>
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-blue-600 mr-3"></i>
                        <span class="text-blue-800">New to Finance Tracker?</span>
                    </div>
                    <a href="unguide.pdf" download class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        Download Guide
                    </a>
                </div>
            </div>
            <div class="flex justify-end space-x-4">
                <button onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Maybe Later
                </button>
                <a href="register.php" class=" btn-add-transaction bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded transition duration-300">
                    Register Now
                </a>
            </div>
        </div>
    </div>
    
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto text-center">
            <p class="mb-2">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400">
                <a href="https://github.com/YasasPasinduFernando" class="mx-2 hover:text-white"><i class="fab fa-github"></i></a>
                <a href="https://www.linkedin.com/in/yasas-pasindu-fernando-893b292b2/" class="mx-2 hover:text-white"><i class="fab fa-linkedin"></i></a>
                <a href="https://x.com/YPasiduFernando?s=09" class="mx-2 hover:text-white"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>

    <script>
        // Show modal after 3 seconds if user hasn't registered
        setTimeout(() => {
            if (!localStorage.getItem('registered')) {
                document.getElementById('registerModal').classList.remove('hidden');
            }
        }, 3000);

        // Function to close modal
        function closeModal() {
            document.getElementById('registerModal').classList.add('hidden');
            localStorage.setItem('modalClosed', 'true');
        }

        // Check if user is coming back to the page
        window.onload = function() {
            if (!localStorage.getItem('registered') && !localStorage.getItem('modalClosed')) {
                setTimeout(() => {
                    document.getElementById('registerModal').classList.remove('hidden');
                }, 3000);
            }
        }
    </script>
</body>
</html>