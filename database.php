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
    $pdf->SetAuthor('Finance Tracker');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Financial Report', date('Y-m-d'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array('helvetica', '', 12));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Write summary section
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Financial Summary', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Create summary table
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetFillColor(240, 240, 240);
    
    // Summary boxes with different colors
    $pdf->SetFillColor(230, 247, 230);
    $pdf->Cell(0, 12, 'Total Income: LKR ' . number_format($summary['income'], 2), 1, 1, 'L', true);
    
    $pdf->SetFillColor(252, 230, 230);
    $pdf->Cell(0, 12, 'Total Expenses: LKR ' . number_format($summary['expenses'], 2), 1, 1, 'L', true);
    
    $pdf->SetFillColor(230, 240, 250);
    $pdf->Cell(0, 12, 'Net Balance: LKR ' . number_format($summary['balance'], 2), 1, 1, 'L', true);
    
    $pdf->Ln(10);
    
    // Transactions table header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Transaction History', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table headers
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    
    // Define column widths
    $dateWidth = 30;
    $typeWidth = 25;
    $categoryWidth = 35;
    $amountWidth = 35;
    $descriptionWidth = $pdf->GetPageWidth() - $pdf->GetX() - $pdf->GetX() - $dateWidth - $typeWidth - $categoryWidth - $amountWidth;
    
    // Print table headers
    $pdf->Cell($dateWidth, 10, 'Date', 1, 0, 'C', true);
    $pdf->Cell($typeWidth, 10, 'Type', 1, 0, 'C', true);
    $pdf->Cell($categoryWidth, 10, 'Category', 1, 0, 'C', true);
    $pdf->Cell($amountWidth, 10, 'Amount', 1, 0, 'C', true);
    $pdf->Cell($descriptionWidth, 10, 'Description', 1, 1, 'C', true);
    
    // Table content
    $pdf->SetFont('helvetica', '', 10);
    foreach ($transactions as $transaction) {
        $startY = $pdf->GetY();
        $currentY = $startY;
        $maxHeight = 0;
        
        // Calculate required height for description
        $description = $transaction['description'] ?: 'No description';
        $descriptionHeight = $pdf->getStringHeight($descriptionWidth, $description);
        $rowHeight = max(10, $descriptionHeight);
        
        // Set fill color based on transaction type
        $pdf->SetFillColor(
            $transaction['type'] == 'income' ? 240 : 255,
            $transaction['type'] == 'income' ? 255 : 240,
            240
        );
        
        // Print row with uniform height
        $pdf->Cell($dateWidth, $rowHeight, $transaction['date'], 1, 0, 'L', true);
        $pdf->Cell($typeWidth, $rowHeight, ucfirst($transaction['type']), 1, 0, 'L', true);
        $pdf->Cell($categoryWidth, $rowHeight, $transaction['category'], 1, 0, 'L', true);
        $pdf->Cell($amountWidth, $rowHeight, 'LKR ' . number_format($transaction['amount'], 2), 1, 0, 'R', true);
        
        // Multi-cell for description to handle line breaks
        $currentX = $pdf->GetX();
        $pdf->MultiCell($descriptionWidth, $rowHeight, $description, 1, 'L', true);
        
        // Move to next line if not already moved by MultiCell
        if ($pdf->GetY() == $currentY) {
            $pdf->Ln();
        }
    }
    
    // Generate filename with date
    $filename = 'financial_report_' . date('Y-m-d_His') . '.pdf';

     // Add footer
     $pdf->Ln(10);
     $pdf->SetFont('helvetica', 'I', 10);
     $pdf->Cell(0, 10, 'Generated by Finance Tracker - SLTC Research University', 0, 1, 'C');
     $pdf->Cell(0, 10, 'Created by Yasas Pasindu Fernando (23da2-0318)', 0, 1, 'C');
    
    // Output PDF
    $pdf->Output($filename, 'D');
}
