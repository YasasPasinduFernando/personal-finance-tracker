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

    if (updateTransaction($transactionId, $amount, $category, $date, $type)) {
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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8 max-w-md">
        <h1 class="text-2xl font-bold mb-6">Edit Transaction</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Transaction Type
                </label>
                <select name="type" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                    <option value="income" <?php echo ($transaction['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                    <option value="expense" <?php echo ($transaction['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Amount
                </label>
                <input type="number" name="amount" step="0.01" required 
                       value="<?php echo htmlspecialchars($transaction['amount']); ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="mb-4">
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

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Date
                </label>
                <input type="date" name="date" required 
                       value="<?php echo htmlspecialchars($transaction['date']); ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Transaction
                </button>
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-800">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>