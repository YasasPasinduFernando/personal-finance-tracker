<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// dashboard.php
session_start();
include 'database.php';

require_once('tcpdf/tcpdf.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$transactions = getTransactions($userId);
$summary = getTransactionSummary($userId);
$monthlyData = getMonthlyTransactions($userId);

// Handle PDF generation
if (isset($_GET['generate_pdf'])) {
    generatePDF($userId, $transactions, $summary);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">


<div class="flex-grow container mx-auto px-4 py-8">

   
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                
            <div class="mb-8">
    <div class="mb-8 flex justify-between items-center">
    <!-- Title -->
    <h1 class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
        All Transactions
    </h1>
    
    <!-- PDF Export Button -->
    <a href="dashboard.php?generate_pdf=1" class="inline-flex items-center text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium shadow-lg">
        <i class="fas fa-file-pdf mr-2"></i> Export PDF
    </a>
</div>

<!-- Simple Back Link -->
    <a href="dashboard.php" class="inline-flex items-center text-blue-500 text-sm font-medium hover:underline mt-2">
        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
    </a>
</div>

            </div>
            

           <!-- Desktop Table (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Category</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Date</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Description</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Amount</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($transactions as $transaction): ?>
                <tr class="hover:bg-gray-50 transition duration-300">
                    <td class="px-6 py-4">
                        <span class="<?php echo $transaction['type'] == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo $transaction['category']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?php echo $transaction['date']; ?>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-600 max-w-xs truncate">
                            <?php echo $transaction['description'] ?: 'No description'; ?>
                        </p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="<?php echo $transaction['type'] == 'income' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                            LKR <?php echo number_format($transaction['amount'], 2); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" 
                           class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-300 mr-2">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <button onclick="openDeleteModal(<?php echo $transaction['id']; ?>)"
                                class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition duration-300">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            </div>

            <!-- Mobile Cards (visible only on mobile) -->

            <div class="md:hidden">
            <?php foreach ($transactions as $transaction): ?>
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-between items-start mb-2">
                <span class="<?php echo $transaction['type'] == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-3 py-1 rounded-full text-sm font-medium">
                    <?php echo $transaction['category']; ?>
                </span>
                <span class="<?php echo $transaction['type'] == 'income' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                    LKR <?php echo number_format($transaction['amount'], 2); ?>
                </span>
            </div>
            
            <div class="text-sm text-gray-600 mb-2">
                <?php echo $transaction['date']; ?>
            </div>
            
            <div class="text-sm text-gray-600 mb-3">
                <?php echo $transaction['description'] ?: 'No description'; ?>
            </div>
            
            <div class="flex justify-end space-x-2">
                <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" 
                   class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-300">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <button onclick="openDeleteModal(<?php echo $transaction['id']; ?>)"
                        class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition duration-300">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-lg shadow-lg w-96 p-6">
                    <h2 class="text-xl font-bold mb-4 text-red-600">Confirm Deletion</h2>
                    <p class="text-gray-700 mb-6">Are you sure you want to delete this transaction? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-4">
                        <button 
                            onclick="closeDeleteModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded">
                            Cancel
                        </button>
                        <a 
                            id="confirmDeleteBtn" 
                            href="#" 
                            class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
                            Delete
                        </a>
                    </div>
                </div>
            </div>

    <footer class="bg-gray-800 text-white py-6 w-full">
        <div class="container mx-auto text-center">
            <p class="mb-2">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400 text-2xl">
                <a href="https://github.com/YasasPasinduFernando" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://www.linkedin.com/in/yasas-pasindu-fernando-893b292b2/" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://x.com/YPasiduFernando?s=09" target="_blank" class="mx-2 hover:text-white">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
        </div>
    </footer>

    <script>
         // Close modals when clicking outside
         window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            const logoutModal = document.getElementById('logoutModal');
            
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            if (event.target === logoutModal) {
                closeLogoutModal();
            }
        }

        // Delete modal functions
        function openDeleteModal(transactionId) {
            const modal = document.getElementById('deleteModal');
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            deleteBtn.href = `delete_transaction.php?id=${transactionId}`;
            modal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }

    </script>
</body>
</html>
