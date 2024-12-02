<?php
session_start();
include 'database.php';
date_default_timezone_set('Asia/Colombo');

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

        // Validate deadline format
        if (!DateTime::createFromFormat('Y-m-d', $deadline)) {
            die("Invalid deadline format.");
        }

        // Prepare and execute insert query
        $sql = "INSERT INTO financial_goals (title, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sdds", $title, $target_amount, $current_amount, $deadline);
            $stmt->execute();
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }
    }

    // Archive goal
    if (isset($_POST['archive_goal'])) {
        $goalId = $_POST['goal_id'];
        $sql = "UPDATE financial_goals SET is_archived = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $goalId);
            $stmt->execute();
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }
    }

    // Update progress
    if (isset($_POST['update_progress'])) {
        $goalId = $_POST['goal_id'];
        $newAmount = $_POST['current_amount'];
        $sql = "UPDATE financial_goals SET current_amount = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("di", $newAmount, $goalId);
            $stmt->execute();
            $stmt->close();
        } else {
            die("SQL error: " . $conn->error);
        }
    }
}

// Fetch active goals
$activeGoals = $conn->query("SELECT * FROM financial_goals WHERE is_archived = 0 ORDER BY deadline ASC");
if (!$activeGoals) {
    die("Error fetching active goals: " . $conn->error);
}

// Fetch archived goals
$archivedGoals = $conn->query("SELECT * FROM financial_goals WHERE is_archived = 1 ORDER BY deadline DESC");
if (!$archivedGoals) {
    die("Error fetching archived goals: " . $conn->error);
}

// Time-based greeting
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
    <title>Financial Goals - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">

<!-- Header Section -->
<div class="bg-white/80 backdrop-blur-sm shadow-sm">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-user-circle text-blue-600 text-2xl"></i>
                <span class="font-medium text-gray-700 text-lg">
                    <?php echo htmlspecialchars($userName); ?>
                </span>
                <span class="text-gray-500 text-sm">(<?php echo $greeting; ?>)</span>
            </div>
            <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Add New Goal Button -->
    <button onclick="openAddGoalModal()" class="mb-8 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300">
        <i class="fas fa-plus mr-2"></i>Add New Goal
    </button>

    <!-- Active Goals Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Active Goals</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($goal = $activeGoals->fetch_assoc()): ?>
                <div class="bg-gray-50 rounded-lg p-4 shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <button onclick="openUpdateModal(<?php echo $goal['id']; ?>, <?php echo $goal['current_amount']; ?>)" 
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    
                    <?php 
                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                        $progressColor = $progress >= 75 ? 'bg-green-500' : ($progress >= 50 ? 'bg-yellow-500' : 'bg-blue-500');
                    ?>
                    
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="<?php echo $progressColor; ?> h-2.5 rounded-full" 
                                 style="width: <?php echo min(100, $progress); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 mt-1">
                            <span>LKR <?php echo number_format($goal['current_amount'], 2); ?></span>
                            <span>LKR <?php echo number_format($goal['target_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">
                            <i class="far fa-calendar mr-1"></i>
                            <?php echo date('M d, Y', strtotime($goal['deadline'])); ?>
                        </span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                            <button type="submit" name="archive_goal" 
                                    class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-archive"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Archived Goals Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Archived Goals</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($goal = $archivedGoals->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($goal['title']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($goal['current_amount'], 2); ?> / 
                                <?php echo number_format($goal['target_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($goal['deadline'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                Archived
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div id="addGoalModal" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4">Add New Goal</h3>
        <form method="POST">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Goal Title</label>
                <input type="text" id="title" name="title" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="target_amount" class="block text-sm font-medium text-gray-700">Target Amount</label>
                <input type="number" id="target_amount" name="target_amount" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="current_amount" class="block text-sm font-medium text-gray-700">Current Amount</label>
                <input type="number" id="current_amount" name="current_amount" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                <input type="date" id="deadline" name="deadline" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <button type="submit" name="add_goal" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Add Goal</button>
        </form>
        <button onclick="closeAddGoalModal()" class="mt-4 text-blue-600 hover:text-blue-800">Close</button>
    </div>
</div>

<script>
    function openAddGoalModal() {
        document.getElementById('addGoalModal').classList.remove('hidden');
    }

    function closeAddGoalModal() {
        document.getElementById('addGoalModal').classList.add('hidden');
    }
</script>

</body>
</html>
