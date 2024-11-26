<?php
// add_transaction.php
session_start();
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = isset($_POST['description']) ? $_POST['description'] : null;

    if (addTransaction($userId, $amount, $category, $date, $type, $description)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add transaction";
    }
}

// Get categories for the dropdown
$categories = getCategories();

// Sort the $categories array by 'id' in ascending order
usort($categories, function ($a, $b) {
    return $a['id'] <=> $b['id'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-12 flex-grow max-w-md sm:max-w-lg">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-4 sm:space-y-0">
                <!-- Title -->
                <h1 class="text-2xl sm:text-3xl font-bold text-blue-800 flex items-center">
                    <i class="fas fa-plus-circle text-green-500 mr-3"></i>
                    Add New Transaction
                </h1>
                <!-- Add Type Button -->
                <a href="add_transaction_type.php" 
                   class="bg-blue-500 text-white text-sm sm:text-base font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 transition">
                    <i class="fas fa-plus"></i> Add New Type
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-exchange-alt mr-2 text-blue-600"></i> Transaction Type
                    </label>
                    <select name="type" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                    Amount
                    (<i class="fas fa-rupee-sign"></i>)
                    </label>
                    <input type="number" name="amount" step="0.01" required 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-list-alt mr-2 text-yellow-600"></i> Category
                    </label>
                    <select name="category" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-calendar-alt mr-2 text-purple-600"></i> Date
                    </label>
                    <input 
                        type="date" 
                        name="date" 
                        required 
                        value="<?php echo date('Y-m-d'); ?>" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                    >
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                        <i class="fas fa-align-left mr-2 text-purple-600"></i> Description
                    </label>
                    <textarea 
                        name="description" 
                        rows="3" 
                        placeholder="Enter a description for this transaction (optional)" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 leading-tight"
                    ></textarea>
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i> Add Transaction
                </button>
            </form>

            <a href="dashboard.php" class="text-blue-500 mt-4 inline-block hover:underline flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full mt-auto">
        <div class="container mx-auto text-center">
            <p class="mb-2">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400 text-2xl flex justify-center space-x-4">
                <a href="https://github.com/YasasPasinduFernando" target="_blank" class="hover:text-white">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://www.linkedin.com/in/yasas-pasindu-fernando-893b292b2/" target="_blank" class="hover:text-white">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://x.com/YPasiduFernando?s=09" target="_blank" class="hover:text-white">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
        </div>
    </footer>
</body>
</html>
