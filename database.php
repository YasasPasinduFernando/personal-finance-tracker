<?php
// database.php
include 'config.php';

function addTransaction($userId, $amount, $category, $date) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, category, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $userId, $amount, $category, $date);
    $stmt->execute();
    $stmt->close();
    $db->close();
}

function getTransactions($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $db->close();
    return $transactions;
}
?>