<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
$userData = getUserData($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $response = array('success' => false, 'message' => '');
        
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                
                // Verify current password
                if (!password_verify($currentPassword, $userData['password'])) {
                    $response['message'] = 'Current password is incorrect';
                } else {
                    $db = getDB();
                    if ($newPassword) {
                        // Update with new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $username, $email, $hashedPassword, $_SESSION['user_id']);
                    } else {
                        // Update without changing password
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
                    }
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Profile updated successfully';
                        $userData = getUserData($_SESSION['user_id']); // Refresh user data
                    } else {
                        $response['message'] = 'Error updating profile';
                    }
                    $stmt->close();
                    $db->close();
                }
                break;
                
            case 'delete_account':
                $password = $_POST['confirm_password'];
                
                if (password_verify($password, $userData['password'])) {
                    $db = getDB();
                    $db->begin_transaction();
                    
                    try {
                        // Delete user's transactions
                        $stmt = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Delete user account
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $stmt->close();
                        
                        $db->commit();
                        
                        session_destroy();
                        $response['success'] = true;
                        $response['redirect'] = 'login.php';
                    } catch (Exception $e) {
                        $db->rollback();
                        $response['message'] = 'Error deleting account';
                    }
                    $db->close();
                } else {
                    $response['message'] = 'Incorrect password';
                }
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - Finance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Main Settings Card -->
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl mx-auto">
            <!-- Card Header -->
            <div class="border-b border-gray-200 p-6 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-blue-800 flex items-center">
                    <i class="fas fa-user-cog mr-3 text-blue-600"></i>
                    Account Settings
                </h1>
                <a href="dashboard.php" class="flex items-center text-gray-600 hover:text-blue-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <!-- Alert Messages -->
                <div class="mb-6 hidden" id="successAlert">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        <span class="block sm:inline" id="successMessage"></span>
                    </div>
                </div>
                <div class="mb-6 hidden" id="errorAlert">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <span class="block sm:inline" id="errorMessage"></span>
                    </div>
                </div>


            <div><a href="add_transaction_type.php" class=" btn-add-transaction  flex items-center text-gray-600 hover:text-blue-600 transition-colors">
            <i class="fas fa-receipt mr-2"></i>
            Update Your Personal Transaction Categories
             </a></div>

                <!-- Profile Update Form -->
                <form id="updateProfileForm" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($userData['username']); ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($userData['email']); ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="space-y-2">
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="space-y-2">
                        <label for="new_password" class="block text-sm font-medium text-gray-700">
                            New Password <span class="text-gray-500 text-xs">(leave blank to keep current)</span>
                        </label>
                        <input type="password" id="new_password" name="new_password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Profile
                    </button>
                </form>

                <!-- Danger Zone -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h2 class="text-xl font-bold text-red-600 mb-4 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Danger Zone
                    </h2>
                    <p class="text-gray-600 mb-4">
                        Once you delete your account, there is no going back. Please be certain.
                    </p>
                    <button onclick="openDeleteModal()" 
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-md mx-4 w-full">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Account</h3>
                <p class="text-red-600 mb-6">
                    This action cannot be undone. All your data will be permanently deleted.
                </p>
                <form id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter your password to confirm
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded-lg transition duration-200">
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 w-full mt-8">
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
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            const deleteModal = document.getElementById('deleteModal');
            
            // Update Profile Form
            document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(this);
            });
            
            // Delete Account Form
            document.getElementById('deleteAccountForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(this);
            });
            
            function submitForm(form) {
                const formData = new FormData(form);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            successMessage.textContent = data.message;
                            successAlert.classList.remove('hidden');
                            errorAlert.classList.add('hidden');
                            closeDeleteModal();
                            form.reset();
                        }
                    } else {
                        errorMessage.textContent = data.message;
                        errorAlert.classList.remove('hidden');
                        successAlert.classList.add('hidden');
                    }
                })
                .catch(error => {
                    errorMessage.textContent = 'An error occurred. Please try again.';
                    errorAlert.classList.remove('hidden');
                    successAlert.classList.add('hidden');
                });
            }
        });

        function openDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>