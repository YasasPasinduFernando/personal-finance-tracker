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


function getCategories($userId) {
    try {
        $mysqli = getDB();
        $stmt = $mysqli->prepare("SELECT id, name, type FROM categories WHERE user_id = ? OR user_id = 0 ORDER BY id ASC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (mysqli_sql_exception $e) {
        return [];
    }
}



function getTransactions($userId) {
    try {
        $mysqli = getDB();
        $stmt = $mysqli->prepare("
            SELECT t.id, t.amount, t.category, t.date, t.type, t.description 
            FROM transactions t 
            WHERE t.user_id = ? 
            ORDER BY t.date DESC
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = [
                'id' => $row['id'],
                'amount' => $row['amount'],
                'category' => $row['category'],
                'date' => $row['date'],
                'type' => $row['type'],
                'description' => $row['description'] ?: null
            ];
        }
        
        $stmt->close();
        $mysqli->close();
        return $transactions;
    } catch (Exception $e) {
        error_log("Error in getTransactions: " . $e->getMessage());
        return [];
    }
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
    
    // Monthly Summary Section
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Monthly Summary', 0, 1, 'L');
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
    
    // Monthly Statistics
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Monthly Statistics', 0, 1, 'L');
    
    // Calculate monthly statistics
    $categoryTotals = [];
    $highestExpense = ['amount' => 0, 'category' => ''];
    $highestIncome = ['amount' => 0, 'category' => ''];
    
    foreach ($transactions as $transaction) {
        $category = $transaction['category'];
        $amount = $transaction['amount'];
        
        if (!isset($categoryTotals[$category])) {
            $categoryTotals[$category] = ['income' => 0, 'expense' => 0];
        }
        
        if ($transaction['type'] == 'income') {
            $categoryTotals[$category]['income'] += $amount;
            if ($amount > $highestIncome['amount']) {
                $highestIncome = ['amount' => $amount, 'category' => $category];
            }
        } else {
            $categoryTotals[$category]['expense'] += $amount;
            if ($amount > $highestExpense['amount']) {
                $highestExpense = ['amount' => $amount, 'category' => $category];
            }
        }
    }
    
    // Display Statistics
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 8, 'Highest Income: ' . $highestIncome['category'] . ' (LKR ' . number_format($highestIncome['amount'], 2) . ')', 0, 1, 'L');
    $pdf->Cell(0, 8, 'Highest Expense: ' . $highestExpense['category'] . ' (LKR ' . number_format($highestExpense['amount'], 2) . ')', 0, 1, 'L');
    
    // Category Breakdown
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Category Breakdown', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 11);
    foreach ($categoryTotals as $category => $totals) {
        if ($totals['income'] > 0 || $totals['expense'] > 0) {
            $pdf->Cell(0, 8, $category . ':', 0, 1, 'L');
            if ($totals['income'] > 0) {
                $pdf->Cell(20, 8, '', 0, 0);
                $pdf->Cell(0, 8, 'Income: LKR ' . number_format($totals['income'], 2), 0, 1, 'L');
            }
            if ($totals['expense'] > 0) {
                $pdf->Cell(20, 8, '', 0, 0);
                $pdf->Cell(0, 8, 'Expense: LKR ' . number_format($totals['expense'], 2), 0, 1, 'L');
            }
        }
    }
    
    // Transaction History
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Transaction History', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Table headers
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    
    // Column widths
    $dateWidth = 30;
    $typeWidth = 25;
    $categoryWidth = 35;
    $amountWidth = 35;
    $descriptionWidth = $pdf->GetPageWidth() - $pdf->GetX() - $pdf->GetX() - $dateWidth - $typeWidth - $categoryWidth - $amountWidth;
    
    // Headers
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

function getTransactionsByMonth($userId, $month) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM transactions 
        WHERE user_id = ? 
        AND DATE_FORMAT(date, '%Y-%m') = ?
        ORDER BY date DESC
    ");
    $stmt->bind_param("is", $userId, $month);
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

function getCategoryTotals($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            category,
            type,
            SUM(amount) as total,
            COUNT(*) as count
        FROM transactions 
        WHERE user_id = ?
        GROUP BY category, type
        ORDER BY type, category
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
    $db->close();
    return $categories;
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

//get all user data fro geeting and aswel for user settings

// Add this to database.php
function getUserData($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// considering after showing to my friend i pand to improve user expirince so give them chance to add custom cthegoris

// Function to get categories for the logged-in user
function getUserCategories($userId) {
    $mysqli = getDB();
    $stmt = $mysqli->prepare("SELECT id, name, type FROM categories WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to create a new category for the logged-in user
function createCategory($userId, $name, $type) {
    try {
        $mysqli = getDB();
        $stmt = $mysqli->prepare("INSERT INTO categories (name, type, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $type, $userId);
        $stmt->execute();
        return ['success' => true, 'message' => 'Category created successfully!'];
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // MySQL duplicate entry error code
            return ['success' => false, 'message' => 'A category with this name already exists!'];
        }
        // Handle other potential errors
        return ['success' => false, 'message' => 'An error occurred while creating the category.'];
    }
}

// Function to update an existing category
function updateCategory($categoryId, $userId, $name, $type) {
    $mysqli = getDB();
    $stmt = $mysqli->prepare("UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $name, $type, $categoryId, $userId);
    $stmt->execute();
}

// Function to delete a category
function deleteCategory($categoryId, $userId) {
    $mysqli = getDB();
    $stmt = $mysqli->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $categoryId, $userId);
    $stmt->execute();
}
