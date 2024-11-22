<?php
session_start();
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get transaction ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$transactionId = $_GET['id'];
$transaction = getTransactionById($transactionId);

// Verify transaction exists and belongs to user
if (!$transaction || $transaction['user_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = trim($_POST['description']); // Add trim() to clean the input
    
    // For debugging
    error_log("Updating transaction: ID=$transactionId, Amount=$amount, Category=$category, Date=$date, Type=$type, Description=$description");
    
    if (updateTransaction($transactionId, $amount, $category, $date, $type, $description)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update transaction";
    }
}

// Get categories for the dropdown
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-12 flex-grow max-w-md">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <h1 class="text-2xl font-bold text-blue-800 mb-6 flex items-center">
                <i class="fas fa-edit mr-3 text-blue-500"></i>
                Edit Transaction
            </h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Transaction Type
                    </label>
                    <select name="type" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <option value="income" <?php echo ($transaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                        <option value="expense" <?php echo ($transaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Amount
                    </label>
                    <input type="number" name="amount" step="0.01" required 
                           value="<?php echo htmlspecialchars($transaction['amount']); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Category
                    </label>
                    <select name="category" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>"
                                    <?php echo ($transaction['category'] == $category['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Date
                    </label>
                    <input type="date" name="date" required 
                           value="<?php echo htmlspecialchars($transaction['date']); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
                    <i class="fas fa-align-left mr-2 text-purple-600"></i> Description
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="3" 
                        placeholder="Enter a description for this transaction (optional)" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 leading-tight whitespace-pre-wrap break-words indent-0"
                    ><?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                    </textarea>
                    </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Transaction
                    </button>
                    <a href="dashboard.php" class="text-blue-500 hover:text-blue-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full">
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
