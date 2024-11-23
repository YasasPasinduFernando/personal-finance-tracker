<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$transactionId = $_GET['id'];

// Fetch the transaction for the logged-in user
$transaction = getTransactionById($transactionId, $userId);

if (!$transaction) {
    header("Location: dashboard.php");
    exit(); // Transaction not found or not authorized
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];

    updateTransaction($transactionId, $amount, $category, $date);
    header("Location: dashboard.php");
    exit();
}

$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Edit Transaction</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold">Edit Transaction</h1>
        <form method="POST">
            <input type="number" name="amount" value="<?php echo $transaction['amount']; ?>" required class="border p-2 mb-4 w-full">
            <select name="category" required class="border p-2 mb-4 w-full">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['name']; ?>" <?php echo ($cat['name'] == $transaction['category']) ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                <?php endforeach; ?>
                <option value="Other">Other</option>
            </select>
            <input type="date" name="date" value="<?php echo $transaction['date']; ?>" required class="border p-2 mb-4 w-full">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded">Update Transaction</button>
        </form>
    </div>
</body>
</html>