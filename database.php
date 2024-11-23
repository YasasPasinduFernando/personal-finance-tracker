<?php
// database.php
include 'config.php';

function registerUser($name, $email, $password) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    $result = $stmt->execute();
    $stmt->close();
    $db->close();
    return $result;
}

function checkUserExists($email, $username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}


function loginUser($usernameOrEmail, $password) {
    $db = getDB();
    
    // Query to check if input matches either a username or email
    $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify the password
        if (password_verify($password, $row['password'])) {
            return $row['id']; // Return user ID if password matches
        }
    }
    return false; // Return false if credentials are invalid
}


function getCategories() {
    $db = getDB();
    $result = $db->query("SELECT * FROM categories ORDER BY type, name");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $db->close();
    return $categories;
}



function getTransactions($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
    $db->close();
    return $transactions;
}

function getTransactionById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    $db->close();
    return $transaction;
}

function addTransaction($userId, $amount, $category, $date, $type, $description = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, category, date, type, description) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("idssss", $userId, $amount, $category, $date, $type, $description);
    $result = $stmt->execute();
    $stmt->close();
    $db->close();
    return $result;
}



function getTransactionSummary($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expenses
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    $summary['balance'] = $summary['income'] - $summary['expenses'];
    $stmt->close();
    $db->close();
    return $summary;
}


function updateTransaction($id, $amount, $category, $date, $type, $description) {
    $db = getDB();
    // Modified SQL query to explicitly include description
    $stmt = $db->prepare("UPDATE transactions SET amount = ?, category = ?, date = ?, type = ?, description = ? WHERE id = ?");
    if (!$stmt) {
        // Handle preparation error
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    
    // Bind all parameters including description
    if (!$stmt->bind_param("dssssi", $amount, $category, $date, $type, $description, $id)) {
        // Handle binding error
        error_log("Binding parameters failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->execute();
    if (!$result) {
        // Handle execution error
        error_log("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $db->close();
    return $result;
}


function deleteTransaction($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    $db->close();
    return $result;
}


function generatePDF($userId, $transactions, $summary) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Financial Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Financial Report', date('Y-m-d'));
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Write summary
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Financial Summary', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Total Income: LKR ' . number_format($summary['income'], 2), 0, 1);
    $pdf->Cell(0, 10, 'Total Expenses: LKR ' . number_format($summary['expenses'], 2), 0, 1);
    $pdf->Cell(0, 10, 'Net Balance: LKR ' . number_format($summary['balance'], 2), 0, 1);
    
    $pdf->Ln(10);
    
    // Transactions table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Transaction History', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Date', 1);
    $pdf->Cell(40, 10, 'Type', 1);
    $pdf->Cell(60, 10, 'Category', 1);
    $pdf->Cell(40, 10, 'Amount', 1);
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 12);
    foreach ($transactions as $transaction) {
        $pdf->Cell(40, 10, $transaction['date'], 1);
        $pdf->Cell(40, 10, ucfirst($transaction['type']), 1);
        $pdf->Cell(60, 10, $transaction['category'], 1);
        $pdf->Cell(40, 10, 'LKR ' . number_format($transaction['amount'], 2), 1);
        $pdf->Ln();
    }
    
    // Output PDF
    $pdf->Output('financial_report_' . date('Y-m-d') . '.pdf', 'D');
}
