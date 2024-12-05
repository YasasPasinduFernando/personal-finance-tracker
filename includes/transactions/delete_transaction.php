<?php
// delete_transaction.php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$transactionId = $_GET['id'] ?? null;
if (!$transactionId) {
    header("Location: dashboard.php");
    exit();
}

// Verify transaction belongs to user
$transaction = getTransactionById($transactionId);
if (!$transaction || $transaction['user_id'] != $_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}

// Delete the transaction
deleteTransaction($transactionId);
header("Location: dashboard.php");
exit();