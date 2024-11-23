<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $transactionId = $_GET['id'];
    deleteTransaction($transactionId);
}

header("Location: dashboard.php");
exit();
?>