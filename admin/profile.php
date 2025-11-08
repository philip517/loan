<?php
require 'auth_admin.php';
require '../db_connect.php'; // PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update credentials
    if (isset($_POST['update_credentials'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        $stmt = $pdo->prepare("UPDATE user_table SET username=?, email=?, first_name=?, last_name=? WHERE user_id=?");
        $stmt->execute([$username, $email, $first_name, $last_name, $user_id]);
        header("Location: profile.php");
        exit;
    }

    // Update personal details
    if (isset($_POST['update_personal'])) {
        $date_of_birth = $_POST['date_of_birth'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $NRC = $_POST['NRC'];
        $gender = $_POST['gender'];
        $occupation = $_POST['occupation'];

        $stmt = $pdo->prepare("UPDATE user_table SET date_of_birth=?, phone=?, address=?, NRC=?, gender=?, occupation=? WHERE user_id=?");
        $stmt->execute([$date_of_birth, $phone, $address, $NRC, $gender, $occupation, $user_id]);
        header("Location: profile.php");
        exit;
    }

 
    // Update password and/or reset phrase
    if (isset($_POST['update_password'])) {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['com_password']);

        if ($new_password !== '' || $confirm_password !== '') {
            // Password fields have data, so update password
            if ($new_password === $confirm_password) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE login_details SET password=? WHERE user_id=?");
                $stmt->execute([$hashedPassword, $reset_phrase, $user_id]);
                header("Location: profile.php");
                exit;
            } else {
                $password_error = "Passwords do not match!";
            }
        } 
    }
}

// Fetch existing user data
$stmt = $pdo->prepare("
    SELECT u.*
    FROM user_table u
    LEFT JOIN login_details l ON u.user_id = l.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user data found, redirect
if (!$user_data) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Profile</title>
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
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        .profile-section {
            margin-bottom: 2rem;
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
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-dark mb-0"><strong>MY PROFILE</strong></h3>
                </div>
                
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-10">
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $password_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- User Credentials -->
                        <div class="profile-section">
                            <div class="card shadow-lg border-0 border-left-primary">
                                <div class="card-header bg-primary text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Credentials</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="user">
                                        <input type="hidden" name="update_credentials" value="1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="username"><strong>Username</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="email"><strong>Email Address</strong></label>
                                                    <input class="form-control form-control-user" type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="first_name"><strong>First Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="last_name"><strong>Last Name</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <button class="btn btn-primary px-4" type="submit">
                                                <i class="fas fa-save me-2"></i>Save Credentials
                                            </button>
                                        </div>
                                    </form>
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
                                    <form method="post" class="user">
                                        <input type="hidden" name="update_personal" value="1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="date_of_birth"><strong>Date of Birth</strong></label>
                                                    <input class="form-control form-control-user" type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($user_data['date_of_birth'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="phone"><strong>Phone Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="phone" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="address"><strong>Address</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="address" name="address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="NRC"><strong>NRC Number</strong></label>
                                                    <input class="form-control form-control-user" type="text" id="NRC" name="NRC" value="<?= htmlspecialchars($user_data['NRC'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="gender"><strong>Gender</strong></label>
                                                    <select class="form-select form-control-user" name="gender">
                                                        <option value="Male" <?= ($user_data['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="Female" <?= ($user_data['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="occupation"><strong>Occupation</strong></label>
                                                    <select class="form-select form-control-user" name="occupation">
                                                        <option value="student" <?= ($user_data['occupation'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                                                        <option value="business" <?= ($user_data['occupation'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option>
                                                        <option value="worker" <?= ($user_data['occupation'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                                                        <option value="entreprenuer" <?= ($user_data['occupation'] ?? '') === 'entreprenuer' ? 'selected' : '' ?>>Entrepreneur</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <button class="btn btn-primary px-4" type="submit">
                                                <i class="fas fa-save me-2"></i>Save Personal Details
                                            </button>
                                        </div>
                                    </form>
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
                                    <form method="post" class="user">
                                        <input type="hidden" name="update_password" value="1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="new_password"><strong>New Password</strong></label>
                                                    <input class="form-control form-control-user" type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="com_password"><strong>Confirm New Password</strong></label>
                                                    <input class="form-control form-control-user" type="password" id="com_password" name="com_password" placeholder="Confirm new password">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-4">
                                            <button class="btn btn-primary px-4" type="submit">
                                                <i class="fas fa-key me-2"></i>Update Security Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Account Summary -->
                        <div class="profile-section">
                            <div class="card shadow-lg border-0 border-left-success">
                                <div class="card-header bg-success text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Account Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user_data['created_at'] ?? 'now')) ?></p>
                                                <p><strong>Account Status:</strong> <span class="badge bg-success">Active</span></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p><strong>User Role:</strong> <span class="badge bg-primary"><?= ucfirst($user_data['role'] ?? 'Client') ?></span></p>
                                          
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-4">
                                        <a href="index.php" class="btn btn-primary px-4">
                                            <i class="fas fa-home me-2"></i>Go to Dashboard
                                        </a>    
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