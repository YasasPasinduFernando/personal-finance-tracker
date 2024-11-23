<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-12 flex-grow">
        <div class="max-w-xl mx-auto bg-white shadow-2xl rounded-xl p-8 text-center">
            <h1 class="text-4xl font-extrabold text-blue-800 mb-4 flex items-center justify-center">
                <i class="fas fa-wallet mr-4 text-green-500"></i>
                Finance Tracker
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
            
            <div class="mt-8 flex justify-center space-x-4">
                <a href="register.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Register
                </a>
                <a href="login.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
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
</body>
</html>