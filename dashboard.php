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

// Handle Full Transactions PDF generation
if (isset($_GET['generate_full_pdf'])) {
    generateFullSummaryPDF($userId, $transactions); // New function for all transactions
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

        
        <a href="#"
   id="openModal"
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


<!--Report Modal -->
<div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80">
        <h2 class="text-lg font-bold mb-4">Generate Report</h2>
        <p class="text-sm text-gray-600 mb-6">Choose the type of PDF you want to generate:</p>
        <!-- PDF Export Buttons -->
        <div class="space-y-4">
            <!-- Monthly Summary PDF -->
            <a href="?generate_pdf=true" 
               class="block text-center text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-sm font-medium shadow-lg">
                <i class="fas fa-file-pdf mr-2"></i> Export Monthly Report
            </a>
            <!-- Full Transactions PDF -->
            <a href="?generate_full_pdf=true" 
               class="block text-center text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm font-medium shadow-lg">
                <i class="fas fa-file-pdf mr-2"></i> Export Full Report
            </a>
        </div>
        <!-- Close Button -->
        <button id="closeModal" 
                class="mt-4 w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
            Cancel
        </button>
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
    <style>
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulseButton {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }

        .modal-content {
            animation: modalFadeIn 0.5s ease-out forwards;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .add-button {
            animation: pulseButton 2s infinite;
            transition: all 0.3s ease;
        }

        .add-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
    </style>

    <div id="emptyDataModal" class="fixed inset-0 flex items-center justify-center modal-backdrop z-50">
        <div class="bg-white rounded-xl modal-content p-8 w-full max-w-md">
            <?php 
                $hour = date('H');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            ?>
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($greeting); ?>!</h2>
                <div class="w-16 h-1 bg-blue-500 mx-auto rounded-full mb-4"></div>
            </div>
            
            <h3 class="text-xl font-semibold text-gray-700 mb-3">Welcome to Your Financial Tracker</h3>
            <p class="text-gray-600 mb-8 leading-relaxed">
                It looks like you're just getting started! Add your first transaction to begin tracking your finances and take control of your money.
            </p>
            
            <div class="text-center">
                <a href="add_transaction.php" class="add-button inline-flex items-center justify-center bg-blue-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Your First Transaction
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

// Report Modal

// Open Modal
document.getElementById('openModal').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('pdfModal').classList.remove('hidden');
    });

    // Close Modal
    document.getElementById('closeModal').addEventListener('click', function () {
        document.getElementById('pdfModal').classList.add('hidden');
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', function (e) {
        const modal = document.getElementById('pdfModal');
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });



    </script>
</body>
</html>


