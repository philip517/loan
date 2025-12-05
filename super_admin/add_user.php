<?php
require 'auth_admin.php';
require '../db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $NRC = $_POST['NRC'];
        $gender = $_POST['gender'];
        $occupation = $_POST['occupation'];
        $date_of_birth = $_POST['date_of_birth'];
        $nationality = $_POST['nationality'];
        $role = $_POST['role'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
       

        // Validate required fields
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
            throw new Exception("All required fields must be filled");
        }

        // Check if passwords match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // Check if username already exists
        $check_username_sql = "SELECT user_id FROM user_table WHERE username = ?";
        $check_username_stmt = $pdo->prepare($check_username_sql);
        $check_username_stmt->execute([$username]);
        if ($check_username_stmt->fetch()) {
            throw new Exception("Username already exists");
        }

        // Check if email already exists
        $check_email_sql = "SELECT user_id FROM user_table WHERE email = ?";
        $check_email_stmt = $pdo->prepare($check_email_sql);
        $check_email_stmt->execute([$email]);
        if ($check_email_stmt->fetch()) {
            throw new Exception("Email already exists");
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert into user_table
            $user_sql = "INSERT INTO user_table (username, email, first_name, last_name, phone, address, NRC, gender, occupation, date_of_birth, nationality, role, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([
                $username, $email, $first_name, $last_name, $phone, $address, 
                $NRC, $gender, $occupation, $date_of_birth, $nationality, $role
            ]);

            $new_user_id = $pdo->lastInsertId();

            // Hash password and insert into login_details
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $login_sql = "INSERT INTO login_details (username, password, password_reset_phrase, user_id) 
                          VALUES (?, ?, ?, ?)";
            $login_stmt = $pdo->prepare($login_sql);
            $login_stmt->execute([$username, $hashed_password, $reset_phrase, $new_user_id]);

            // Commit transaction
            $pdo->commit();

            $_SESSION['success_message'] = "User created successfully!";
            header("Location: table.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Add New User</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
    /* Fix the sidebar position */
    #wrapper {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        z-index: 1000;
        overflow-y: auto;
        transition: all 0.3s;
    }

    /* Fix the content wrapper position */
    #content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
    }

    /* Fix the top navbar position */
    .topbar {
        position: fixed !important;
        top: 0;
        left: 250px;
        right: 0;
        z-index: 999;
        height: 70px;
    }

    /* Main content area with proper spacing */
    #content {
        margin-top: 70px; /* Height of top navbar */
        padding: 25px;
        min-height: calc(100vh - 70px);
        overflow-y: auto;
        background: rgba(255,255,255,0.09);
    }

    /* Mobile responsive sidebar */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-250px);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar {
            left: 0;
        }
        
        #content {
            padding: 15px;
            margin-top: 70px;
        }
    }

    /* Ensure content scrolls properly */
    html, body {
        overflow-x: hidden;
    }

    /* Existing styles (keep your original styles) */
    .card-header {
        font-weight: 600;
    }
    .form-control-user {
        border-radius: 0.35rem;
        padding: 0.75rem 1rem;
    }
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .profile-section {
        margin-bottom: 2rem;
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
</style>
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <?php require 'navbar.php'; ?>
    <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
        <div id="content" style="background: rgba(255,255,255,0.09);">
            <div class="container-fluid" style="margin-top: 80px;">
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-dark mb-0"><strong>ADD NEW USER</strong></h3>
                    <a href="table.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                    </a>
                </div>
                
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-10">
                        <!-- Error Message -->
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Success Message -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <!-- User Creation Form -->
                        <form method="post" class="user">
                            
                            <!-- User Credentials -->
                            <div class="profile-section">
                                <div class="card shadow-lg border-0 border-left-primary">
                                    <div class="card-header bg-primary text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Credentials</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="username"><strong>Username</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                                    <small class="form-text text-muted">Unique username for login</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="email"><strong>Email Address</strong></label>
                                                    <input class="form-control form-control-user" type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="first_name"><strong>First Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="last_name"><strong>Last Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Details -->
                            <div class="profile-section">
                                <div class="card shadow-lg border-0 border-left-info">
                                    <div class="card-header bg-info text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Personal Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="date_of_birth"><strong>Date of Birth</strong></label>
                                                    <input class="form-control form-control-user" type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="phone"><strong>Phone Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="address"><strong>Address</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="NRC"><strong>NRC Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="NRC" name="NRC" value="<?= htmlspecialchars($_POST['NRC'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="gender"><strong>Gender</strong></label>
                                                    <select class="form-select form-control-user" name="gender">
                                                        <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="occupation"><strong>Occupation</strong></label>
                                                    <select class="form-select form-control-user" name="occupation">
                                                        <option value="student" <?= ($_POST['occupation'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                                                        <option value="business" <?= ($_POST['occupation'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option>
                                                        <option value="worker" <?= ($_POST['occupation'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                                                        <option value="entreprenuer" <?= ($_POST['occupation'] ?? '') === 'entreprenuer' ? 'selected' : '' ?>>Entrepreneur</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="nationality"><strong>Nationality</strong></label>
                                                    <select class="form-select form-control-user" name="nationality">
                                                        <option value="Zambian" <?= ($_POST['nationality'] ?? '') === 'Zambian' ? 'selected' : '' ?>>Zambian</option>
                                                        <option value="Other" <?= ($_POST['nationality'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="role"><strong>User Role</strong></label>
                                                    <select class="form-select form-control-user" name="role" required>
                                                        <option value="">Select Role</option>
                                                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="super_admin" <?= ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Settings -->
                            <div class="profile-section">
                                <div class="card shadow-lg border-0 border-left-warning">
                                    <div class="card-header bg-warning text-dark py-3">
                                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="password"><strong>Password</strong></label>
                                                    <input class="form-control form-control-user" type="password" id="password" name="password" required>
                                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="confirm_password"><strong>Confirm Password</strong></label>
                                                    <input class="form-control form-control-user" type="password" id="confirm_password" name="confirm_password" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Section -->
                            <div class="profile-section">
                                <div class="card shadow-lg border-0 border-left-success">
                                    <div class="card-header bg-success text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create User Account</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Information:</strong> Please review all the information before creating the user account. 
                                            Once created, the user will be able to log in with the provided credentials.
                                        </div>
                                        <div class="text-center mt-4">
                                            <button type="reset" class="btn btn-secondary px-4 me-3">
                                                <i class="fas fa-redo me-2"></i>Reset Form
                                            </button>
                                            <button type="submit" class="btn btn-success px-4">
                                                <i class="fas fa-user-plus me-2"></i>Create User Account
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/js/script.min.js"></script>
<script>
    // Handle sidebar toggle for mobile screens
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggleTop-1');
        const sidebar = document.querySelector('.sidebar');
        const contentWrapper = document.getElementById('content-wrapper');
        const topbar = document.querySelector('.topbar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && 
                    sidebar.classList.contains('show') &&
                    event.target !== sidebarToggle) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Adjust layout on window resize
        function adjustLayout() {
            if (window.innerWidth > 768) {
                // On larger screens, ensure sidebar is visible
                sidebar.classList.remove('show');
                contentWrapper.style.marginLeft = '250px';
                topbar.style.left = '250px';
            } else {
                // On mobile screens, sidebar is hidden by default
                if (!sidebar.classList.contains('show')) {
                    contentWrapper.style.marginLeft = '0';
                    topbar.style.left = '0';
                }
            }
        }

        window.addEventListener('resize', adjustLayout);
        adjustLayout(); // Initial adjustment

        // Password confirmation validation (your existing code)
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity("");
            }
        }
        
        if (password && confirmPassword) {
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        }
    });
</script>
<script>
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords do not match");
            } else {
                confirmPassword.setCustomValidity("");
            }
        }
        
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    });
</script>
</body>
</html>