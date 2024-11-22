<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];

    addTransaction($userId, $amount, $category, $date);
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Add Transaction</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold">Add Transaction</h1>
        <form method="POST">
            <input type="number" name="amount" placeholder="Amount" required class="border p-2 mb-4 w-full">
            <input type="text" name="category" placeholder="Category" required class="border p-2 mb-4 w-full">
            <input type="date" name="date" required class="border p-2 mb-4 w-full">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded">Add Transaction</button>
        </form>
    </div>
</body>
</html>