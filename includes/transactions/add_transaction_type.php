<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only update referrer on GET requests, not on POST
    if (!isset($_SESSION['referrer_url']) || 
        (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != $_SERVER['REQUEST_URI'])) {
        $_SESSION['referrer_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
    }
}

require_once('database.php'); // Include database functions

// Check if user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    die('You must be logged in to manage categories.');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        createCategory($userId, $name, $type);
    }

    if (isset($_POST['update'])) {
        $categoryId = $_POST['category_id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        updateCategory($categoryId, $userId, $name, $type);
    }

    if (isset($_POST['delete'])) {
        $categoryId = $_POST['category_id'];
        deleteCategory($categoryId, $userId);
    }
}

// Fetch user categories from the database
$categories = getUserCategories($userId);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-8 max-w-4xl">

        

        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">
            <i class="fas fa-tags mr-2"></i>Manage Your Categories
        </h1>

        <!-- Create New Category Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <a href="<?php echo isset($_SESSION['referrer_url']) ? htmlspecialchars($_SESSION['referrer_url']) : 'dashboard.php'; ?>" class=" text-blue-600 hover:text-blue-800 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Go Back
        </a>
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                <i class="fas fa-plus-circle mr-2"></i>Create New Category
            </h2>
            <form method="POST" class="space-y-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-4 md:space-y-0">
                    <div class="flex-1">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                        <input type="text" id="name" name="name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex-1">
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Category Type</label>
                        <select name="type" id="type" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create" 
                        class="btn-add-transaction w-full md:w-auto px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    <i class=" fas fa-plus mr-2"></i>Create Category
                </button>
            </form>
        </div>

        <!-- List Existing Categories -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                <i class="fas fa-list mr-2"></i>Your Categories
            </h2>
            <?php if ($categories): ?>
                <!-- Desktop View (Table) -->
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                                <tr class="hover:bg-gray-50">
                                    <form method="POST">
                                        <td class="px-6 py-4">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-6 py-4">
                                            <select name="type" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                                <option value="income" <?php echo ($category['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                                                <option value="expense" <?php echo ($category['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 space-x-2">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="update" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                <i class="fas fa-save mr-1"></i> Update
                                            </button>
                                            <button type="submit" name="delete" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View (Cards) -->
                <div class="md:hidden space-y-4">
                    <?php foreach ($categories as $category): ?>
                        <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                            <form method="POST" class="space-y-4">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category Type</label>
                                        <select name="type" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                            <option value="income" <?php echo ($category['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                                            <option value="expense" <?php echo ($category['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="update" 
                                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <i class="fas fa-save mr-1"></i> Update
                                    </button>
                                    <button type="submit" name="delete" 
                                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-trash-alt mr-1"></i> Delete
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-4"></i>
                    <p>You have no categories yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full mt-auto">
        <div class="container mx-auto text-center">
            <p class="mb-2">
                Created By Yasas Pasindu Fernando (23da2-0318)
            </p>
            <p class="text-sm text-gray-400">
                @ SLTC Research University
            </p>
            <div class="mt-4 text-gray-400 text-2xl flex justify-center space-x-4">
                <a href="https://github.com/YasasPasinduFernando" target="_blank" class="hover:text-white">
                    <i class="fab fa-github"></i>
                </a>
                <a href="https://www.linkedin.com/in/yasas-pasindu-fernando-893b292b2/" target="_blank" class="hover:text-white">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="https://x.com/YPasiduFernando?s=09" target="_blank" class="hover:text-white">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
        </div>
    </footer>

</body>
</html>
