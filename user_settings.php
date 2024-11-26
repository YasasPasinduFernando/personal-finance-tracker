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
                
                // Verify password
                if (password_verify($password, $userData['password'])) {
                    $db = getDB();
                    // Begin transaction
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
                        
                        // Clear session and redirect
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .settings-card {
            max-width: 800px;
            margin: 2rem auto;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .alert {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Account Settings</h3>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <!-- Alert Messages -->
                <div class="alert alert-success" id="successAlert"></div>
                <div class="alert alert-danger" id="errorAlert"></div>
                
                <!-- Profile Update Form -->
                <form id="updateProfileForm">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
                
                <hr class="my-4">
                
                <!-- Delete Account Section -->
                <div class="text-danger">
                    <h4>Danger Zone</h4>
                    <p>Once you delete your account, there is no going back. Please be certain.</p>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">This action cannot be undone. All your data will be permanently deleted.</p>
                    <form id="deleteAccountForm">
                        <input type="hidden" name="action" value="delete_account">
                        <div class="form-group">
                            <label for="confirm_password">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete Account</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            // Update Profile Form
            document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(this);
            });
            
            // Delete Account
            document.getElementById('confirmDelete').addEventListener('click', function() {
                submitForm(document.getElementById('deleteAccountForm'));
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
                            successAlert.textContent = data.message;
                            successAlert.style.display = 'block';
                            errorAlert.style.display = 'none';
                            
                            // Hide the delete account modal if it's open
                            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
                            if (deleteModal) {
                                deleteModal.hide();
                            }
                        }
                    } else {
                        errorAlert.textContent = data.message;
                        errorAlert.style.display = 'block';
                        successAlert.style.display = 'none';
                    }
                })
                .catch(error => {
                    errorAlert.textContent = 'An error occurred. Please try again.';
                    errorAlert.style.display = 'block';
                    successAlert.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>