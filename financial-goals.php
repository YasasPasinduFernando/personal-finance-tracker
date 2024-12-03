<?php
require_once 'config.php';
session_start();
date_default_timezone_set('Asia/Colombo');

// Function to get user data
function getUserData($userId) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch goals
function fetchGoals($conn, $userId, $status) {
    $sql = "SELECT * FROM financial_goals WHERE user_id = ? AND status = ? ORDER BY deadline ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $status);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get time-based greeting
function getTimeBasedGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return "Good Morning";
    } elseif ($hour >= 12 && $hour < 18) {
        return "Good Afternoon";
    } else {
        return "Good Evening";
    }
}

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);
$userName = $userData['username'];

$conn = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new financial goal
    if (isset($_POST['add_goal'])) {
        $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
        $target_amount = $_POST['target_amount'];
        $current_amount = $_POST['current_amount'];
        $deadline = $_POST['deadline'];

        $sql = "INSERT INTO financial_goals (user_id, title, target_amount, current_amount, deadline, status) 
                VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issis", $userId, $title, $target_amount, $current_amount, $deadline);
        $stmt->execute();
    }

    // Mark goal as failed
    if (isset($_POST['mark_goal_failed'])) {
        $goalId = $_POST['goal_id'];
        $sql = "UPDATE financial_goals SET status = 'failed' WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $goalId, $userId);
        $stmt->execute();
    }

    // Try again (move failed goal back to active)
    if (isset($_POST['try_again'])) {
        $goalId = $_POST['goal_id'];
        $sql = "UPDATE financial_goals SET status = 'active' WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $goalId, $userId);
        $stmt->execute();
    }

    // Update daily increment
    if (isset($_POST['add_daily_increment'])) {
        $goalId = $_POST['goal_id'];
        $increment = $_POST['increment_amount'];
        
        $sql = "UPDATE financial_goals 
                SET current_amount = current_amount + ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dii", $increment, $goalId, $userId);
        $stmt->execute();
    }

    // Extend deadline
    if (isset($_POST['extend_deadline'])) {
        $goalId = $_POST['goal_id'];
        $newDeadline = $_POST['new_deadline'];
        
        $sql = "UPDATE financial_goals SET deadline = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $newDeadline, $goalId, $userId);
        $stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$activeGoals = fetchGoals($conn, $userId, 'active');
$failedGoals = fetchGoals($conn, $userId, 'failed');
$greeting = getTimeBasedGreeting();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Goals - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .hover-scale {
            transition: transform 0.2s;
        }
        
        .hover-scale:hover {
            transform: scale(1.02);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .custom-shadow {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.5s ease-in-out;
        }

        .modal {
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
    <!-- Navigation -->
    <nav class="gradient-bg text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8">
                    <div class="flex items-center">
                        <i class="fas fa-chart-line text-2xl mr-2"></i>
                        <span class="text-xl font-bold">Finance Tracker</span>
                    </div>
                    <a href="dashboard.php" class="flex items-center hover:bg-white hover:text-blue-600 px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-home mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
                <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                    <span class="glass-effect px-4 py-2 rounded-lg w-full md:w-auto text-center">
                        <?php echo $greeting; ?>, <?php echo htmlspecialchars($userName); ?>!
                    </span>
                    <button onclick="openLogoutModal()" class="hover:bg-red-500 px-4 py-2 rounded-lg transition duration-300 w-full md:w-auto">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Add New Goal Button -->
        <button onclick="openAddGoalModal()" class="w-full md:w-auto mb-8 gradient-bg text-white px-8 py-4 rounded-lg hover:shadow-lg transition duration-300 hover-scale">
            <i class="fas fa-plus mr-2"></i>Add New Goal
        </button>

        <!-- Active Goals Section -->
        <div class="bg-white rounded-xl custom-shadow p-4 md:p-8 mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-target text-blue-600 mr-3"></i>
                Active Goals
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
                <?php while($goal = $activeGoals->fetch_assoc()): 
                    $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                    $progress = min(100, max(0, $progress));
                ?>
                    <div class="goal-card bg-white rounded-lg custom-shadow p-6 hover-scale" data-goal-id="<?php echo $goal['id']; ?>" data-deadline="<?php echo $goal['deadline']; ?>">
                        <h3 class="text-xl font-semibold mb-3 text-blue-600"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span class="font-medium">Progress</span>
                                <span class="font-bold text-blue-600"><?php echo number_format($progress, 1); ?>%</span>
                            </div>
                            <div class="progress-bar bg-gray-200 rounded-full">
                                <div class="progress-fill rounded-full <?php echo $progress >= 100 ? 'bg-green-500' : 'bg-blue-500'; ?>" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4 space-y-2">
                            <p class="flex justify-between">
                                <span>Current:</span>
                                <span class="font-medium">LKR <?php echo number_format($goal['current_amount'], 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Target:</span>
                                <span class="font-medium">LKR <?php echo number_format($goal['target_amount'], 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Deadline:</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime($goal['deadline'])); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Remaining:</span>
                                <span id="countdown-<?php echo $goal['id']; ?>" class="countdown font-medium"></span>
                            </p>
                        </div>
                        <div class="flex justify-between border-t pt-4">
                            <button onclick="openDailyIncrementModal(<?php echo $goal['id']; ?>)" class="text-blue-600 hover:text-blue-800 flex items-center">
                                <i class="fas fa-plus-circle mr-1"></i> Add Progress
                            </button>
                            <button onclick="openExtendGoalModal(<?php echo $goal['id']; ?>)" class="text-yellow-600 hover:text-yellow-800 flex items-center">
                                <i class="fas fa-clock mr-1"></i> Extend
                            </button>
                            <button onclick="markGoalFailed(<?php echo $goal['id']; ?>)" class="text-red-600 hover:text-red-800 flex items-center">
                                <i class="fas fa-times-circle mr-1"></i> Failed
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Failed Goals Section -->
        <div class="bg-white rounded-xl custom-shadow p-4 md:p-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-times-circle text-red-600 mr-3"></i>
                Failed Goals
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
                <?php while($goal = $failedGoals->fetch_assoc()): 
                    $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                    $progress = min(100, max(0, $progress));
                ?>
                    <div class="bg-red-50 rounded-lg custom-shadow p-6 hover-scale">
                        <h3 class="text-xl font-semibold mb-3 text-red-600"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Final Progress</span>
                                <span class="font-bold text-red-600"><?php echo number_format($progress, 1); ?>%</span>
                            </div>
                            <div class="progress-bar bg-red-200 rounded-full">
                                <div class="progress-fill bg-red-500 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 space-y-2">
                            <p class="flex justify-between">
                                <span>Reached:</span>
                                <span class="font-medium">LKR <?php echo number_format($goal['current_amount'], 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Target:</span>
                                <span class="font-medium">LKR <?php echo number_format($goal['target_amount'], 2); ?></span>
                            </p>
                            <p class="flex justify-between">
                                <span>Failed on:</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime($goal['deadline'])); ?></span>
                            </p>
                        </div>
                        <div class="border-t pt-4 mt-4">
                            <form method="POST" action="">
                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                <button type="submit" name="try_again" 
                                    class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                                    <i class="fas fa-redo mr-2"></i>Try Again
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Add Goal Modal -->
    <div id="addGoalModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4">Add New Goal</h2>
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                        Goal Title
                    </label>
                    <input type="text" id="title" name="title" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="target_amount">
                        Target Amount (LKR)
                    </label>
                    <input type="number" id="target_amount" name="target_amount" required min="0" step="0.01"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="current_amount">
                        Current Amount (LKR)
                    </label>
                    <input type="number" id="current_amount" name="current_amount" required min="0" step="0.01"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="deadline">
                        Deadline
                    </label>
                    <input type="date" id="deadline" name="deadline" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeAddGoalModal()" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                    <button type="submit" name="add_goal" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add Goal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-96 max-w-[90%] mx-auto p-6">
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

    <!-- Daily Increment Modal -->
    <div id="dailyIncrementModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4">Add Daily Progress</h2>
            <form method="POST" action="">
                <input type="hidden" name="goal_id" id="incrementGoalId">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="increment_amount">
                        Amount (LKR)
                    </label>
                    <input type="number" id="increment_amount" name="increment_amount" required min="0" step="0.01"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeDailyIncrementModal()" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                    <button type="submit" name="add_daily_increment" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add Progress
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Extend Goal Modal -->
    <div id="extendGoalModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4">Extend Goal Deadline</h2>
            <form method="POST" action="">
                <input type="hidden" name="goal_id" id="extensionGoalId">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="new_deadline">
                        New Deadline
                    </label>
                    <input type="date" id="new_deadline" name="new_deadline" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeExtendGoalModal()" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                    <button type="submit" name="extend_deadline" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Extend
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full mt-8">
        <div class="container mx-auto px-4 text-center">
            <p class="mb-2 text-sm md:text-base">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-xs md:text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400 text-xl md:text-2xl">
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
</body>

    <script>
        function openAddGoalModal() {
            document.getElementById('addGoalModal').style.display = 'flex';
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('deadline').min = tomorrow.toISOString().split('T')[0];
        }

        function closeAddGoalModal() {
            document.getElementById('addGoalModal').style.display = 'none';
        }

        function openDailyIncrementModal(goalId) {
            document.getElementById('incrementGoalId').value = goalId;
            document.getElementById('dailyIncrementModal').style.display = 'flex';
        }

        function closeDailyIncrementModal() {
            document.getElementById('dailyIncrementModal').style.display = 'none';
        }

        function openExtendGoalModal(goalId) {
            document.getElementById('extensionGoalId').value = goalId;
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('new_deadline').min = tomorrow.toISOString().split('T')[0];
            document.getElementById('extendGoalModal').style.display = 'flex';
        }

        function closeExtendGoalModal() {
            document.getElementById('extendGoalModal').style.display = 'none';
        }

        function markGoalFailed(goalId) {
            if (confirm('Are you sure you want to mark this goal as failed?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="goal_id" value="${goalId}">
                    <input type="hidden" name="mark_goal_failed" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Initialize countdown timers
        document.addEventListener("DOMContentLoaded", () => {
            const goalCards = document.querySelectorAll('.goal-card');
            goalCards.forEach(card => {
                const deadline = new Date(card.dataset.deadline).getTime();
                const countdownElement = document.getElementById(`countdown-${card.dataset.goalId}`);
                
                const updateCountdown = () => {
                    const now = new Date().getTime();
                    const distance = deadline - now;

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    if (countdownElement) {
                        if (distance < 0) {
                            countdownElement.innerHTML = "EXPIRED";
                        } else {
                            countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                        }
                    }
                };

                updateCountdown();
                setInterval(updateCountdown, 1000);
            });
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



    </script>
</body>
</html>