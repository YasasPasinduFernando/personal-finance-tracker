<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// dashboard.php
session_start();
include 'database.php';
date_default_timezone_set('Asia/Colombo'); // Set timezone to Sri Lanka

require_once('tcpdf/tcpdf.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$userId = $_SESSION['user_id'];
$userData = getUserData($userId); //get user data
$userName = $userData['username']; // Assuming the name field exists in your users table

$userId = $_SESSION['user_id'];
$transactions = getTransactions($userId);
$summary = getTransactionSummary($userId);
$monthlyData = getMonthlyTransactions($userId);

// Handle PDF generation
if (isset($_GET['generate_pdf'])) {
    generatePDF($userId, $transactions, $summary);
    exit();
}

$hasTransactions = count($transactions) > 0;


// Function to get time-based greeting
function getTimeBasedGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return "Good Morning";
    } elseif ($hour >= 12 && $hour < 17) {
        return "Good Afternoon";
    } elseif ($hour >= 17 && $hour < 22) {
        return "Good Evening";
    } else {
        return "Good Night";
    }
}

$greeting = getTimeBasedGreeting();

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>

<script>

        /** Clock Script -
         * this is added because i want to make clock
         *if i add last script it will not work because of dom is loaded but if this is in upper section this can change body elements by id
         */

        document.addEventListener("DOMContentLoaded", function () {
            function updateClock() {
                const now = new Date();
                const hours = now.getHours();
                const minutes = now.getMinutes();
                const seconds = now.getSeconds();

                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                const suffix = hours >= 12 ? 'PM' : 'AM';

                // Update the clock parts
                document.getElementById('clockHours').textContent = String(formattedHours).padStart(2, '0');
                document.getElementById('clockMinutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('clockSeconds').textContent = String(seconds).padStart(2, '0');
                document.getElementById('clockSuffix').textContent = ` ${suffix}`;
            }

            // Initialize and update clock every second
            updateClock();
            setInterval(updateClock, 1000);
        });
    </script>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">

<!-- this is user gerrting and current time section -->
<div class="bg-white/80 backdrop-blur-sm shadow-sm">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- User Section -->
            <div class="flex items-center space-x-3">
                <i class="fas fa-user-circle text-blue-600 text-2xl"></i>
                <span class="font-medium text-gray-700 text-lg">
                    <?php echo htmlspecialchars($userName); ?>
                </span>
                <span class="text-gray-500 text-sm">(<?php echo $greeting; ?>)</span>
            </div>

            <!-- Actions Section -->
            <div class="flex items-center space-x-6">
                <!-- Clock Display -->
                <div class="group relative flex items-center space-x-1 text-gray-700">
                    <span class="text-gray-600 group-hover:text-blue-600">
                        <i class="fas fa-clock"></i>
                    </span>
                    <span id="clockHours"></span>
                    <span>:</span>
                    <span id="clockMinutes"></span>
                    <span>:</span>
                    <span id="clockSeconds"></span>
                    <span id="clockSuffix"></span>
                </div>

                <!-- Settings Icon -->
                <a href="user_settings.php" class="group relative text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="fas fa-cog text-xl group-hover:rotate-90 transition-transform duration-500"></i>
                </a>
            </div>
        </div>
    </div>
</div>


<div class="container mx-auto px-4 py-12 flex-grow">
    <div class="bg-white shadow-2xl rounded-xl p-8">
    <div class="space-y-8 mb-12">

    <!-- Title Section with Gradient Border Bottom -->
    <div class="pb-6 border-b border-gradient-to-r from-blue-200 to-purple-200">
        <h1 class="text-4xl font-extrabold text-blue-800 flex items-center">
            <i class="fas fa-chart-line mr-4 text-green-500 transform hover:scale-110 transition-transform"></i>
            Financial Dashboard
        </h1>
    </div>
    
    <!-- Buttons Section with Shadow and Better Spacing -->
    <div class="flex flex-wrap gap-4 justify-end">
        
        
        <a href="add_transaction.php" class="btn-add-transaction">
            <i class="fas fa-plus mr-3"></i>
            Add New Transaction
        </a>


        <a href="transactions.php" 
           class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 
                  text-white px-6 py-3 rounded-lg transition duration-300 flex items-center shadow-md 
                  hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-list-ul mr-3"></i>
            View All Transactions
        </a>

        
        <a href="?generate_pdf=true" 
           class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 
                  text-white px-6 py-3 rounded-lg transition duration-300 flex items-center shadow-md 
                  hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-file-download mr-3"></i>
            Download Report
        </a>
        
        <button onclick="openLogoutModal()" 
                class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 
                       text-white px-6 py-3 rounded-lg transition duration-300 flex items-center shadow-md 
                       hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-sign-out-alt mr-3"></i>
            Logout
        </button>
    </div>
</div>



        <!-- Financial Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Income Card -->
    <div class="bg-gradient-to-br from-green-50 to-white rounded-2xl p-6 shadow-lg border border-green-100">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-emerald-800 text-lg font-semibold mb-3">Total Income</h2>
                <p class="text-emerald-600 text-3xl font-bold tracking-tight">
                    LKR <?php echo number_format($summary['income'], 2); ?>
                </p>
            </div>
            <div class="bg-emerald-100 p-3 rounded-xl">
                <i class="fas fa-arrow-up text-emerald-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Expenses Card -->
    <div class="bg-gradient-to-br from-red-50 to-white rounded-2xl p-6 shadow-lg border border-red-100">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-red-800 text-lg font-semibold mb-3">Total Expenses</h2>
                <p class="text-red-600 text-3xl font-bold tracking-tight">
                    LKR <?php echo number_format($summary['expenses'], 2); ?>
                </p>
            </div>
            <div class="bg-red-100 p-3 rounded-xl">
                <i class="fas fa-arrow-down text-red-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 shadow-lg border border-blue-100">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-blue-800 text-lg font-semibold mb-3">Net Balance</h2>
                <p class="text-blue-600 text-3xl font-bold tracking-tight">
                    LKR <?php echo number_format($summary['balance'], 2); ?>
                </p>
            </div>
            <div class="bg-blue-100 p-3 rounded-xl">
                <i class="fas fa-balance-scale text-blue-500 text-2xl"></i>
            </div>
        </div>
    </div>
</div>


        <!-- Chart -->
<div class="mb-8 bg-white shadow-md rounded-lg p-6">
    <div class="max-w-3xl mx-auto h-[300px]"> <!-- Added container with constraints -->
        <canvas id="transactionChart"></canvas>
    </div>
</div>
    <!-- Monthly Trend Chart -->
    <div class="bg-white shadow-md rounded-lg p-4 sm:p-6">
        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">Monthly Trends</h2>
        <div class="relative h-[300px] sm:h-[400px]"> <!-- Responsive height -->
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </div>

    <!-- Monthly Summary Table -->
<div class="bg-white shadow-lg rounded-xl p-4 sm:p-6 mt-8">
    <h2 class="text-xl font-bold text-blue-900 mb-6 flex items-center">
        <i class="fas fa-calendar-alt mr-3 text-emerald-500"></i>
        Monthly Summary
    </h2>
    
    <!-- Desktop Table (Hidden on Mobile) -->
    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-lg">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Month</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Income</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Expenses</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($monthlyData as $month): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-emerald-600 font-medium whitespace-nowrap">
                                LKR <?php echo number_format($month['income'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-red-600 font-medium whitespace-nowrap">
                                LKR <?php echo number_format($month['expenses'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-right text-sm <?php echo $month['net'] >= 0 ? 'text-emerald-600' : 'text-red-600'; ?> font-medium whitespace-nowrap">
                                LKR <?php echo number_format($month['net'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards (Shown only on Mobile) -->
    <div class="md:hidden space-y-4">
        <?php foreach ($monthlyData as $month): ?>
            <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-semibold text-blue-900">
                        <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                    </span>
                    <span class="<?php echo $month['net'] >= 0 ? 'text-emerald-600' : 'text-red-600'; ?> font-bold">
                        Net: LKR <?php echo number_format($month['net'], 2); ?>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Income</p>
                        <p class="text-emerald-600 font-medium">
                            LKR <?php echo number_format($month['income'], 2); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Expenses</p>
                        <p class="text-red-600 font-medium">
                            LKR <?php echo number_format($month['expenses'], 2); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
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


    <!-- Modal 0 Data  -->
<?php if (!$hasTransactions): ?>
    <div id="emptyDataModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">No Transactions Found</h2>
            <p class="text-gray-600 mb-6">It seems you have no transaction details. Please add a transaction to proceed.</p>
            <div class="text-center">
                <a href="add_transaction.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Add Transaction
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>


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
        maintainAspectRatio: true,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
        

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

        // monthly transaction table fuctions

        document.addEventListener('DOMContentLoaded', function() {
    const monthlyData = <?php echo json_encode(array_reverse($monthlyData)); ?>;
    
    const months = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    
    const incomeData = monthlyData.map(item => item.income);
    const expenseData = monthlyData.map(item => item.expenses);
    
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Expenses',
                    data: expenseData,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        // Make legend text smaller on mobile
                        font: {
                            size: window.innerWidth < 640 ? 10 : 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'LKR ' + context.parsed.y.toLocaleString();
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        // Make axis labels smaller on mobile
                        font: {
                            size: window.innerWidth < 640 ? 10 : 12
                        },
                        callback: function(value) {
                            return 'LKR ' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    ticks: {
                        // Make axis labels smaller on mobile
                        font: {
                            size: window.innerWidth < 640 ? 10 : 12
                        }
                    }
                }
            }
        }
    });
});

// Prevent 0 data the modal from being closed manually
// Disable close functionality (force user to go to the page)
const noTransactionModal = document.getElementById('emptyDataModal');
if (noTransactionModal) {
    noTransactionModal.addEventListener('click', function(e) {
        if (e.target !== this) return;
        e.preventDefault();
    });
}




    </script>
</body>
</html>
