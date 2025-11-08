<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get user ID from URL parameter
$target_user_id = $_GET['user_id'] ?? null;

if (!$target_user_id) {
    header("Location: table.php");
    exit;
}

// Check if current user has permission to edit this user
$current_user_id = $_SESSION['user_id'];

// Fetch current user's role to check permissions
$current_user_sql = "SELECT role FROM user_table WHERE user_id = ?";
$current_user_stmt = $pdo->prepare($current_user_sql);
$current_user_stmt->execute([$current_user_id]);
$current_user_role = $current_user_stmt->fetchColumn();

// Fetch target user data
$user_sql = "
    SELECT u.*, l.password_reset_phrase 
    FROM user_table u
    LEFT JOIN login_details l ON u.user_id = l.user_id
    WHERE u.user_id = ?
";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$target_user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// If no user data found, redirect
if (!$user_data) {
    header("Location: table.php");
    exit;
}

require 'edit_user_form.php';
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>User Profile - <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
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
        .border-left-danger {
            border-left: 4px solid #dc3545 !important;
        }
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        .profile-section {
            margin-bottom: 2rem;
        }
        .user-actions {
            position: sticky;
            top: 100px;
            z-index: 100;
        }
        .status-badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <?php require 'navbar.php'; ?>
    <div class="d-flex flex-column" id="content-wrapper">
        <div id="content" style="background: rgba(255,255,255,0.09);">
            <div class="container-fluid" style="margin-top: 80px;">
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-9">
                        <div class="d-sm-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-dark mb-0">
                                <strong>
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>'s Profile
                                </strong>
                                <?php if ($user_status === 'deactivated'): ?>
                                    <span class="badge bg-danger status-badge ms-2">Deactivated</span>
                                <?php else: ?>
                                    <span class="badge bg-success status-badge ms-2">Active</span>
                                <?php endif; ?>
                            </h3>
                            <a href="table.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Users
                            </a>
                        </div>

                        <!-- User Details Form -->
                        <div class="profile-section">
                            <div class="card shadow-lg border-0 border-left-primary">
                                <div class="card-header bg-primary text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User Details</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="user">
                                        <input type="hidden" name="update_user" value="1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Username</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="username" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Email Address</strong></label>
                                                    <input class="form-control form-control-user" type="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>First Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Last Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Phone Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>NRC Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="NRC" value="<?= htmlspecialchars($user_data['NRC'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Gender</strong></label>
                                                    <select class="form-select form-control-user" name="gender">
                                                        <option value="Male" <?= ($user_data['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="Female" <?= ($user_data['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Occupation</strong></label>
                                                    <select class="form-select form-control-user" name="occupation">
                                                        <option value="student" <?= ($user_data['occupation'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                                                        <option value="business" <?= ($user_data['occupation'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option>
                                                        <option value="worker" <?= ($user_data['occupation'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                                                        <option value="entreprenuer" <?= ($user_data['occupation'] ?? '') === 'entreprenuer' ? 'selected' : '' ?>>Entrepreneur</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Date of Birth</strong></label>
                                                    <input class="form-control form-control-user" type="date" name="date_of_birth" value="<?= htmlspecialchars($user_data['date_of_birth'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Nationality</strong></label>
                                                    <select class="form-select form-control-user" name="nationality">
                                                        <option value="Zambian" <?= ($user_data['nationality'] ?? '') === 'Zambian' ? 'selected' : '' ?>>Zambian</option>
                                                        <option value="Other" <?= ($user_data['nationality'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Address</strong></label>
                                                    <input class="form-control form-control-user" type="text" name="address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>User Role</strong></label>
                                                    <select class="form-select form-control-user" name="role">
                                                        <option value="client" <?= ($user_data['role'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                                                        <option value="admin" <?= ($user_data['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="super_admin" <?= ($user_data['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <button class="btn btn-primary px-4" type="submit">
                                                <i class="fas fa-save me-2"></i>Update User Details
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Actions Sidebar -->
                    <div style="margin-top:80px;"class="col-lg-3">
                        <div class="user-actions">
                            <div class="card shadow-lg border-0 border-left-danger">
                                <div class="card-header bg-danger text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>User Management</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Status Toggle -->
                                    <form method="post" class="mb-4">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="user_status" value="<?= $user_status ?>">
                                        <?php if ($user_status === 'active'): ?>
                                            <button type="submit" class="btn btn-warning w-100 mb-2" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                <i class="fas fa-user-slash me-2"></i>Deactivate User
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Are you sure you want to activate this user?')">
                                                <i class="fas fa-user-check me-2"></i>Activate User
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete User -->
                                    <form method="post">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('WARNING: This will permanently delete the user and all their data. This action cannot be undone. Are you sure?')">
                                            <i class="fas fa-trash me-2"></i>Delete User
                                        </button>
                                    </form>

                                    <hr class="my-4">

                                    <!-- User Info -->
                                    <div class="text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-user-circle fa-3x text-primary mb-2"></i>
                                            <h5><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h5>
                                            <p class="text-muted">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                                        </div>
                                        <div class="text-start">
                                            <p><strong>Role:</strong> <span class="badge bg-primary"><?= ucfirst($user_data['role'] ?? 'client') ?></span></p>
                                            <p><strong>Status:</strong> 
                                                <?php if ($user_status === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Deactivated</span>
                                                <?php endif; ?>
                                            </p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/js/script.min.js"></script>
</body>
</html>