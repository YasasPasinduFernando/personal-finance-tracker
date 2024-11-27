<?php
session_start();
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
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS -->
</head>
<body>
    <h1>Manage Your Categories</h1>

    <!-- Create New Category Form -->
    <h2>Create New Category</h2>
    <form method="POST">
        <label for="name">Category Name:</label>
        <input type="text" id="name" name="name" required><br>
        <label for="type">Category Type:</label>
        <select name="type" id="type">
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select><br>
        <button type="submit" name="create">Create Category</button>
    </form>

    <!-- List Existing Categories -->
    <h2>Your Categories</h2>
    <?php if ($categories): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Category Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <form method="POST">
                            <td><input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required></td>
                            <td>
                                <select name="type">
                                    <option value="income" <?php echo ($category['type'] == 'income') ? 'selected' : ''; ?>>Income</option>
                                    <option value="expense" <?php echo ($category['type'] == 'expense') ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="update">Update</button>
                                <button type="submit" name="delete">Delete</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no categories yet.</p>
    <?php endif; ?>
</body>
</html>
