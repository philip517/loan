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
    /* Fix the sidebar position */
    #wrapper {
        display: flex;
        position: relative;
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
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Fix the content wrapper position */
    #content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
        position: relative;
    }

    /* Fix the top navbar position */
    .topbar {
        position: fixed !important;
        top: 0;
        left: 250px;
        right: 0;
        z-index: 999;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: 70px;
    }

    /* Main content area with proper spacing */
    #content {
        margin-top: 70px; /* Height of top navbar */
        padding: 20px;
        min-height: calc(100vh - 70px);
        overflow-y: auto;
        background: rgba(255,255,255,0.09);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            margin-left: -250px;
        }
        
        .sidebar.show {
            margin-left: 0;
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar {
            left: 0;
        }
    }

    /* Ensure main content doesn't overflow */
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }

    /* Scrollbar styling for sidebar */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Ensure profile content is properly spaced */
    .profile-section {
        margin-bottom: 2rem;
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Card styling enhancements */
    .card {
        border-radius: 10px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        font-weight: 600;
        border-radius: 10px 10px 0 0 !important;
        border-bottom: none;
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

    /* Form styling */
    .form-control-user {
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        border: 1px solid #dee2e6;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control-user:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #495057;
    }

    /* Button styling */
    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #0056b3, #004494);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }

    /* Alert styling */
    .alert {
        border-radius: 0.5rem;
        border: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    /* Badge styling */
    .badge {
        font-size: 0.8em;
        padding: 0.4em 0.8em;
        border-radius: 50px;
    }

    /* Responsive adjustments for mobile */
    @media (max-width: 576px) {
        #content {
            padding: 15px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .btn-primary {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .row > div {
            margin-bottom: 15px;
        }
    }

    /* Smooth scrolling for the entire page */
    html {
        scroll-behavior: smooth;
    }

    /* Loading animation for form submissions */
    .loading {
        position: relative;
        pointer-events: none;
        opacity: 0.7;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid #007bff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <?php require 'navbar.php'; ?>
 <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
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
<script>
    // Handle sidebar toggle button
    document.getElementById('sidebarToggleTop-1')?.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        
        if (document.querySelector('.sidebar').classList.contains('show')) {
            document.querySelector('#content-wrapper').style.marginLeft = '250px';
            document.querySelector('.topbar').style.left = '250px';
        } else {
            document.querySelector('#content-wrapper').style.marginLeft = '0';
            document.querySelector('.topbar').style.left = '0';
        }
    });

    // Auto-hide sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggleTop-1');
        
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !toggleBtn?.contains(event.target)) {
                sidebar.classList.remove('show');
                document.querySelector('#content-wrapper').style.marginLeft = '0';
                document.querySelector('.topbar').style.left = '0';
            }
        }
    });

    // Handle form submissions with loading state
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            }
        });
    });

    // Add smooth scroll to top functionality
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollToTopBtn.className = 'btn btn-primary scroll-to-top';
    scrollToTopBtn.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: none;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(scrollToTopBtn);

    // Show/hide scroll to top button
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollToTopBtn.style.display = 'block';
        } else {
            scrollToTopBtn.style.display = 'none';
        }
    });

    // Scroll to top functionality
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
</body>
</html>