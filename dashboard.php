<?php
// dashboard.php
session_start();
include 'database.php';
require_once('tcpdf/tcpdf.php'); // Make sure to install TCPDF

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$transactions = getTransactions($userId);
$summary = getTransactionSummary($userId);

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
    <div class="container mx-auto px-4 py-12 flex-grow">
        <div class="bg-white shadow-2xl rounded-xl p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-extrabold text-blue-800 flex items-center">
                    <i class="fas fa-chart-line mr-4 text-green-500"></i>
                    Financial Dashboard
                </h1>
                <div class="space-x-4 flex items-center">
                    <a href="add_transaction.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-300 flex items-center inline-flex">
                        <i class="fas fa-plus mr-2"></i>
                        Add Transaction
                    </a>
                    <a href="?generate_pdf=true" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition duration-300 flex items-center inline-flex">
                        <i class="fas fa-file-download mr-2"></i>
                        Download Report
                    </a>
                    <button onclick="openLogoutModal()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition duration-300 flex items-center inline-flex">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-green-800 font-bold mb-2">Total Income</h2>
                            <p class="text-3xl text-green-600 font-extrabold">
                                LKR <?php echo number_format($summary['income'], 2); ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-up text-green-500 text-3xl"></i>
                    </div>
                </div>
                <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-red-800 font-bold mb-2">Total Expenses</h2>
                            <p class="text-3xl text-red-600 font-extrabold">
                                LKR <?php echo number_format($summary['expenses'], 2); ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-down text-red-500 text-3xl"></i>
                    </div>
                </div>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-blue-800 font-bold mb-2">Net Balance</h2>
                            <p class="text-3xl text-blue-600 font-extrabold">
                                LKR <?php echo number_format($summary['balance'], 2); ?>
                            </p>
                        </div>
                        <i class="fas fa-balance-scale text-blue-500 text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="mb-8 bg-white shadow-md rounded-lg p-6">
                <canvas id="transactionChart"></canvas>
            </div>

            <!-- Transactions List -->
            <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Recent Transactions</h2>
                </div>
                <div class="overflow-x-auto">
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
                                    <div class="flex items-center">
                                        <span class="<?php echo $transaction['type'] == 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-3 py-1 rounded-full text-sm font-medium">
                                            <?php echo $transaction['category']; ?>
                                        </span>
                                    </div>
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

    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-lg shadow-lg w-96 p-6">
                    <h2 class="text-xl font-bold mb-4 text-blue-600">Confirm Logout</h2>
                    <p class="text-gray-700 mb-6">Are you sure you want to logout from your account?</p>
                    <div class="flex justify-end space-x-4">
                        <button 
                            onclick="closeLogoutModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded">
                            Cancel
                        </button>
                        <a 
                            href="index.php" 
                            class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
                            Logout
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
        // Initialize Chart
        const ctx = document.getElementById('transactionChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Income', 'Expenses'],
                datasets: [{
                    label: 'Financial Summary',
                    data: [<?php echo $summary['income']; ?>, <?php echo $summary['expenses']; ?>],
                    backgroundColor: ['rgba(34, 197, 94, 0.2)', 'rgba(239, 68, 68, 0.2)'],
                    borderColor: ['rgb(34, 197, 94)', 'rgb(239, 68, 68)'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
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

        // Logout modal functions
        function openLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('hidden');
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.add('hidden');
        }

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
    </script>
</body>
</html>
