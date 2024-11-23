<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$transactions = getTransactions($userId); // Fetch transactions for the logged-in user

// Calculate total amount
$totalAmount = 0;
foreach ($transactions as $transaction) {
    $totalAmount += $transaction['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Dashboard</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <h2 class="mt-4">Total Transactions: LKR <?php echo number_format($totalAmount, 2); ?></h2>
        <a href="add_transaction.php" class="bg-blue-500 text-white p-2 rounded">Add Transaction</a>
        <h2 class="mt-4">Your Transactions</h2>
        <div id="transactions" class="mt-2">
            <?php if (empty($transactions)): ?>
                <p>No transactions found.</p>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="border p-4 mb-2 flex justify-between">
                        <div>
                            <p><strong>Amount:</strong> LKR <?php echo number_format($transaction['amount'], 2); ?></p>
                            <p><strong>Category:</strong> <?php echo $transaction['category']; ?></p>
                            <p><strong>Date:</strong> <?php echo $transaction['date']; ?></p>
                        </div>
                        <div>
                            <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="text-blue-500">Edit</a>
                            <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" class="text-red-500">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>