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
    // Get monthly data
    $monthlyData = getMonthlyTransactions($userId);
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Document settings
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Financial Report');
    $pdf->SetAuthor('Finance Tracker');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add page
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Financial Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('Y-m-d'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Overall Summary Section
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Overall Summary', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Summary boxes with enhanced styling
    $pdf->SetFont('helvetica', '', 12);
    
    // Income Box
    $pdf->SetFillColor(230, 247, 230);
    $pdf->Cell(0, 15, 'Total Income: LKR ' . number_format($summary['income'], 2), 1, 1, 'L', true);
    
    // Expenses Box
    $pdf->SetFillColor(252, 230, 230);
    $pdf->Cell(0, 15, 'Total Expenses: LKR ' . number_format($summary['expenses'], 2), 1, 1, 'L', true);
    
    // Net Balance Box
    $pdf->SetFillColor(230, 240, 250);
    $pdf->Cell(0, 15, 'Net Balance: LKR ' . number_format($summary['balance'], 2), 1, 1, 'L', true);
    
    // Monthly Summary Section
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Monthly Summary', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Monthly Summary Table Headers
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    
    // Column widths for monthly summary
    $monthWidth = 45;
    $numberWidth = 45;
    
    // Monthly Summary Headers
    $pdf->Cell($monthWidth, 10, 'Month', 1, 0, 'C', true);
    $pdf->Cell($numberWidth, 10, 'Income', 1, 0, 'C', true);
    $pdf->Cell($numberWidth, 10, 'Expenses', 1, 0, 'C', true);
    $pdf->Cell($numberWidth, 10, 'Net', 1, 1, 'C', true);
    
    // Monthly Summary Data
    $pdf->SetFont('helvetica', '', 10);
    foreach ($monthlyData as $month) {
        $monthName = date('F Y', strtotime($month['month'] . '-01'));
        $pdf->Cell($monthWidth, 10, $monthName, 1, 0, 'L');
        $pdf->Cell($numberWidth, 10, 'LKR ' . number_format($month['income'], 2), 1, 0, 'R');
        $pdf->Cell($numberWidth, 10, 'LKR ' . number_format($month['expenses'], 2), 1, 0, 'R');
        
        // Set color for net amount
        if ($month['net'] >= 0) {
            $pdf->SetTextColor(0, 128, 0); // Green for positive
        } else {
            $pdf->SetTextColor(255, 0, 0); // Red for negative
        }
        $pdf->Cell($numberWidth, 10, 'LKR ' . number_format($month['net'], 2), 1, 1, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset to black
    }
    
    // Category Breakdown
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Category Breakdown', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Calculate and display category breakdown
    $categoryTotals = [];
    foreach ($transactions as $transaction) {
        $category = $transaction['category'];
        if (!isset($categoryTotals[$category])) {
            $categoryTotals[$category] = ['income' => 0, 'expense' => 0];
        }
        if ($transaction['type'] == 'income') {
            $categoryTotals[$category]['income'] += $transaction['amount'];
        } else {
            $categoryTotals[$category]['expense'] += $transaction['amount'];
        }
    }
    
    // Display category breakdown
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($monthWidth, 10, 'Category', 1, 0, 'C', true);
    $pdf->Cell($numberWidth, 10, 'Income', 1, 0, 'C', true);
    $pdf->Cell($numberWidth, 10, 'Expenses', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 10);
    foreach ($categoryTotals as $category => $totals) {
        if ($totals['income'] > 0 || $totals['expense'] > 0) {
            $pdf->Cell($monthWidth, 10, $category, 1, 0, 'L');
            $pdf->Cell($numberWidth, 10, 'LKR ' . number_format($totals['income'], 2), 1, 0, 'R');
            $pdf->Cell($numberWidth, 10, 'LKR ' . number_format($totals['expense'], 2), 1, 1, 'R');
        }
    }
    
    // Transaction History
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Transaction History', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Table headers for transactions
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    
    // Column widths for transactions
    $dateWidth = 30;
    $typeWidth = 25;
    $categoryWidth = 35;
    $amountWidth = 35;
    $descriptionWidth = $pdf->GetPageWidth() - $pdf->GetX() - $pdf->GetX() - $dateWidth - $typeWidth - $categoryWidth - $amountWidth;
    
    // Transaction headers
    $pdf->Cell($dateWidth, 10, 'Date', 1, 0, 'C', true);
    $pdf->Cell($typeWidth, 10, 'Type', 1, 0, 'C', true);
    $pdf->Cell($categoryWidth, 10, 'Category', 1, 0, 'C', true);
    $pdf->Cell($amountWidth, 10, 'Amount', 1, 0, 'C', true);
    $pdf->Cell($descriptionWidth, 10, 'Description', 1, 1, 'C', true);
    
    // Transactions
    $pdf->SetFont('helvetica', '', 10);
    foreach ($transactions as $transaction) {
        $description = $transaction['description'] ?: 'No description';
        $rowHeight = max(10, $pdf->getStringHeight($descriptionWidth, $description));
        
        $pdf->SetFillColor(
            $transaction['type'] == 'income' ? 240 : 255,
            $transaction['type'] == 'income' ? 255 : 240,
            240
        );
        
        $pdf->Cell($dateWidth, $rowHeight, $transaction['date'], 1, 0, 'L', true);
        $pdf->Cell($typeWidth, $rowHeight, ucfirst($transaction['type']), 1, 0, 'L', true);
        $pdf->Cell($categoryWidth, $rowHeight, $transaction['category'], 1, 0, 'L', true);
        $pdf->Cell($amountWidth, $rowHeight, 'LKR ' . number_format($transaction['amount'], 2), 1, 0, 'R', true);
        $pdf->MultiCell($descriptionWidth, $rowHeight, $description, 1, 'L', true);
    }
    
    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated by Finance Tracker - SLTC Research University', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Created by Yasas Pasindu Fernando (23da2-0318)', 0, 1, 'C');
    
    // Generate filename and output
    $filename = 'financial_report_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'D');
}

function getMonthlyTransactions($userId) {
    $db = getDB(); // Use getDB() instead of global $conn
    $sql = "SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net
            FROM transactions 
            WHERE user_id = ? 
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $db->close();
    
    return $data;
}
