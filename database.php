<?php
// database.php
include 'config.php';

function getCategories() {
    $db = getDB();
    $result = $db->query("SELECT * FROM categories");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTransactions($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTransactionById($id, $userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Return the transaction if found, otherwise null
    return $result->fetch_assoc() ?: null;
}

function addTransaction($userId, $amount, $category, $date) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, category, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $amount, $category, $date);
    $stmt->execute();
    $stmt->close();
    $db->close();
}

function updateTransaction($id, $amount, $category, $date) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE transactions SET amount = ?, category = ?, date = ? WHERE id = ?");
    $stmt->bind_param("isss", $amount, $category, $date, $id);
    $stmt->execute();
    $stmt->close();
    $db->close();
}

function deleteTransaction($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $db->close();
}