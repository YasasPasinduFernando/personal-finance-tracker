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
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #4299e1;
            transition: width 0.3s ease;
        }
        
        .countdown {
            color: #e53e3e;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-chart-line text-2xl mr-2"></i>
                    <span class="text-xl font-bold">Finance Tracker</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span><?php echo $greeting; ?>, <?php echo htmlspecialchars($userName); ?>!</span>
                    <a href="logout.php" class="hover:text-gray-200">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <button onclick="openAddGoalModal()" class="mb-8 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Add New Goal
        </button>

        <!-- Add Goal Modal -->
        <div id="addGoalModal" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">Add New Goal</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Target Amount</label>
                        <input type="number" step="0.01" name="target_amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Current Amount</label>
                        <input type="number" step="0.01" name="current_amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Deadline</label>
                        <input type="date" name="deadline" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeAddGoalModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" name="add_goal" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Goal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daily Increment Modal -->
        <div id="dailyIncrementModal" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">Add Daily Progress</h2>
                <form method="POST">
                    <input type="hidden" id="incrementGoalId" name="goal_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Amount to Add</label>
                        <input type="number" step="0.01" name="increment_amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeDailyIncrementModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" name="add_daily_increment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Progress</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Extend Deadline Modal -->
        <div id="extendGoalModal" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold mb-4">Extend Deadline</h2>
                <form method="POST">
                    <input type="hidden" id="extensionGoalId" name="goal_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">New Deadline</label>
                        <input type="date" name="new_deadline" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeExtendGoalModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" name="extend_deadline" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Extend Deadline</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Goals Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Active Goals</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($goal = $activeGoals->fetch_assoc()): 
                    $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                    $progress = min(100, max(0, $progress));
                ?>
                    <div class="goal-card bg-white rounded-lg shadow p-6" data-goal-id="<?php echo $goal['id']; ?>" data-deadline="<?php echo $goal['deadline']; ?>">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Progress</span>
                                <span><?php echo number_format($progress, 1); ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <p>Current: $<?php echo number_format($goal['current_amount'], 2); ?></p>
                            <p>Target: $<?php echo number_format($goal['target_amount'], 2); ?></p>
                            <p>Deadline: <?php echo date('M d, Y', strtotime($goal['deadline'])); ?></p>
                            <p>Time Remaining: <span id="countdown-<?php echo $goal['id']; ?>" class="countdown"></span></p>
                        </div>
                        <div class="flex justify-between">
                            <button onclick="openDailyIncrementModal(<?php echo $goal['id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-plus-circle"></i> Add Progress
                            </button>
                            <button onclick="openExtendGoalModal(<?php echo $goal['id']; ?>)" class="text-yellow-600 hover:text-yellow-800">
                                <i class="fas fa-clock"></i> Extend
                            </button>
                            <button onclick="markGoalFailed(<?php echo $goal['id']; ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times-circle"></i> Failed
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Failed Goals Section -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Failed Goals</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($goal = $failedGoals->fetch_assoc()): 
                    $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                    $progress = min(100, max(0, $progress));
                ?>
                    <div class="bg-red-50 rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Final Progress</span>
                                <span><?php echo number_format($progress, 1); ?>%</span>
                            </div>
                            <div class="progress-bar bg-red-200">
                                <div class="progress-fill bg-red-500" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <p>Reached: $<?php echo number_format($goal['current_amount'], 2); ?></p>
                            <p>Target: $<?php echo number_format($goal['target_amount'], 2); ?></p>
                            <p>Failed on: <?php echo date('M d, Y', strtotime($goal['deadline'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function openAddGoalModal() {
            document.getElementById('addGoalModal').style.display = 'flex';
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
    </script>
</body>
</html>