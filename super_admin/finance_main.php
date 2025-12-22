<?php
require 'auth_admin.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Get summary statistics for all loan statuses
$summary_query = "
    SELECT 
        COUNT(*) as total_loans,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_loans,
        COUNT(CASE WHEN status = 'approved' AND progress = 'current' THEN 1 END) as current_loans,
        COUNT(CASE WHEN status = 'approved' AND progress = 'paid' THEN 1 END) as paid_loans,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_loans,
        
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'approved' AND progress = 'current' THEN amount ELSE 0 END) as current_amount,
        SUM(CASE WHEN status = 'approved' AND progress = 'paid' THEN amount + interest + penalty_fee ELSE 0 END) as paid_amount,
        SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as rejected_amount,
        
        SUM(CASE WHEN status = 'approved' AND progress = 'paid' THEN interest + penalty_fee ELSE 0 END) as total_revenue
    FROM loan
";

$summary_stmt = $pdo->query($summary_query);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Financial Dashboard - Admin</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <style>
        /* Fixed Layout Styles */
        body {
            overflow-x: hidden;
            padding-top: 70px; /* For fixed top navbar */
        }
        
        #wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Fixed Sidebar */
        .sidebar {
            position: fixed !important;
            top: 0;
            left: 0;
            width: 250px !important;
            height: 100vh;
            z-index: 1030;
            overflow-y: auto;
        }
        
        /* Content Wrapper - takes remaining space */
        #content-wrapper {
            flex: 1;
            width: calc(100% - 250px) !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin-left: 100px;
        }
        
        /* Fixed Top Navbar */
        .topbar {
            position: fixed !important;
            top: 0;
            left: 250px !important;
            right: 0;
            height: 70px;
            width: calc(100% - 250px) !important;
            z-index: 1020;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Main Content Area */
        #content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            margin-top: 70px; /* Space for fixed navbar */
            background: #f8f9fa;
        }
        
        /* Footer adjustment */
        footer.bg-white.sticky-footer {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        /* Finance Cards Styling */
        .finance-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .finance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-detail {
            font-size: 0.85rem;
            color: #868e96;
        }
        
        .pending-card {
            border-top: 4px solid #ffc107 !important;
        }
        
        .current-card {
            border-top: 4px solid #17a2b8 !important;
        }
        
        .paid-card {
            border-top: 4px solid #28a745 !important;
        }
        
        .rejected-card {
            border-top: 4px solid #dc3545 !important;
        }
        
        .view-btn {
            margin-top: 15px;
            padding: 8px 20px;
            font-weight: 600;
            width: 100%;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 0;
            }
            
            .sidebar {
                position: relative !important;
                width: 100% !important;
                height: auto;
                max-height: 300px;
            }
            
            #content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .topbar {
                position: relative !important;
                left: 0 !important;
                width: 100% !important;
            }
            
            #content {
                margin-top: 0;
                padding: 15px;
            }
            
            footer.bg-white.sticky-footer {
                margin-left: 0;
                width: 100%;
            }
            
            .finance-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        
        <div id="content-wrapper">
            <nav class="navbar navbar-expand fixed-top bg-white shadow z-1 mb-4 topbar">
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none me-3 rounded-circle" id="sidebarToggleTop-1" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="navbar-nav flex-nowrap ms-auto">
                        <li class="nav-item mx-1 dropdown no-arrow">
                            <div class="nav-item dropdown no-arrow">
                                <a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                    <?php 
                                    // Count unread messages
                                    $count_stmt = $pdo->prepare("
                                        SELECT COUNT(*) as unread_count 
                                        FROM message 
                                        WHERE status = 'sent' AND type = 'user_to_admin'
                                    ");
                                    $count_stmt->execute();
                                    $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
                                    ?>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="badge bg-danger badge-counter"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                    <i class="fas fa-envelope fa-fw"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-list animated--grow-in">
                                    <h6 class="dropdown-header">Messages Center</h6>
                                    <?php if ($unread_count == 0): ?>
                                        <a class="dropdown-item text-center small text-gray-500" href="#">
                                            <div class="py-3">
                                                <i class="fas fa-envelope-open fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">No new messages</p>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <a class="dropdown-item text-center small text-gray-500" href="message.php">Show All Messages</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown no-arrow">
                            <div class="nav-item dropdown no-arrow">
                                <a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                    <span class="d-none d-lg-inline me-2 text-gray-600 small">
                                        <?php 
                                        $stmt = $pdo->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ?");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo htmlspecialchars($user['first_name']." ".$user['last_name']);
                                        ?>
                                    </span>
                                    <i class="fas fa-user-circle"></i>
                                </a>
                                <div class="dropdown-menu shadow dropdown-menu-end animated--grow-in">
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Profile
                                    </a>
                                    <a class="dropdown-item" href="message.php">
                                        <i class="fas fa-envelope me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Messages
                                    </a>
                                    <a class="dropdown-item" href="user.php">
                                        <i class="fas fa-list me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Clients
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php">
                                        <i class="fas fa-sign-out-alt me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Logout
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div id="content">
                <!-- Header -->
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="text-dark mb-0"><strong>FINANCIAL DASHBOARD</strong></h3>
                        <p class="text-muted mb-0">Overview of all loan categories</p>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-6 p-2">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?php echo date('F Y'); ?>
                        </span>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <!-- Pending Loans Card -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow finance-card pending-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="card-icon text-warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="card-title">PENDING LOANS</div>
                                        <div class="card-value text-warning">
                                            K<?php echo number_format($summary['pending_amount'] ?? 0, 2); ?>
                                        </div>
                                        <div class="card-detail">
                                            <?php echo $summary['pending_loans'] ?? 0; ?> applications awaiting review
                                        </div>
                                    </div>
                                </div>
                                <a href="finance_pending.php" class="btn btn-warning view-btn">
                                    View Details <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Loans Card -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow finance-card current-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="card-icon text-info">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="card-title">CURRENT LOANS</div>
                                        <div class="card-value text-info">
                                            K<?php echo number_format($summary['current_amount'] ?? 0, 2); ?>
                                        </div>
                                        <div class="card-detail">
                                            <?php echo $summary['current_loans'] ?? 0; ?> active loans
                                        </div>
                                    </div>
                                </div>
                                <a href="finance_current.php" class="btn btn-info view-btn">
                                    View Details <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paid Loans Card -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow finance-card paid-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="card-icon text-success">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="card-title">PAID LOANS</div>
                                        <div class="card-value text-success">
                                            K<?php echo number_format($summary['paid_amount'] ?? 0, 2); ?>
                                        </div>
                                        <div class="card-detail">
                                            Revenue: K<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="finance_paid.php" class="btn btn-success view-btn">
                                    View Details <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rejected Loans Card -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow finance-card rejected-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="card-icon text-danger">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="card-title">REJECTED LOANS</div>
                                        <div class="card-value text-danger">
                                            K<?php echo number_format($summary['rejected_amount'] ?? 0, 2); ?>
                                        </div>
                                        <div class="card-detail">
                                            <?php echo $summary['rejected_loans'] ?? 0; ?> applications rejected
                                        </div>
                                    </div>
                                </div>
                                <a href="finance_rejected.php" class="btn btn-danger view-btn">
                                    View Details <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Statistics -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Quick Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h2 class="text-primary"><?php echo $summary['total_loans'] ?? 0; ?></h2>
                                            <p class="text-muted mb-0">Total Loans</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h2 class="text-success"><?php echo $summary['paid_loans'] ?? 0; ?></h2>
                                            <p class="text-muted mb-0">Completed Loans</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h2 class="text-info"><?php echo $summary['current_loans'] ?? 0; ?></h2>
                                            <p class="text-muted mb-0">Active Loans</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h2 class="text-warning"><?php echo $summary['pending_loans'] ?? 0; ?></h2>
                                            <p class="text-muted mb-0">Pending Review</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <footer class="bg-white sticky-footer">
                <div class="container my-auto">
                    <div class="text-center my-auto copyright">
                        <span>Copyright © SEFA SATTY 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <a class="border rounded d-inline scroll-to-top" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebarToggleTop-1').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const contentWrapper = document.getElementById('content-wrapper');
            const topbar = document.querySelector('.topbar');
            const footer = document.querySelector('footer');
            
            if (window.innerWidth <= 768) {
                if (sidebar.style.display === 'none' || sidebar.style.display === '') {
                    sidebar.style.display = 'block';
                    contentWrapper.style.marginLeft = '0';
                    topbar.style.left = '0';
                    topbar.style.width = '100%';
                    if (footer) {
                        footer.style.marginLeft = '0';
                        footer.style.width = '100%';
                    }
                } else {
                    sidebar.style.display = 'none';
                    contentWrapper.style.marginLeft = '0';
                    topbar.style.left = '0';
                    topbar.style.width = '100%';
                    if (footer) {
                        footer.style.marginLeft = '0';
                        footer.style.width = '100%';
                    }
                }
            } else {
                if (sidebar.style.width === '250px') {
                    sidebar.style.width = '0';
                    contentWrapper.style.marginLeft = '0';
                    contentWrapper.style.width = '100%';
                    topbar.style.left = '0';
                    topbar.style.width = '100%';
                    if (footer) {
                        footer.style.marginLeft = '0';
                        footer.style.width = '100%';
                    }
                } else {
                    sidebar.style.width = '250px';
                    contentWrapper.style.marginLeft = '250px';
                    contentWrapper.style.width = 'calc(100% - 250px)';
                    topbar.style.left = '250px';
                    topbar.style.width = 'calc(100% - 250px)';
                    if (footer) {
                        footer.style.marginLeft = '250px';
                        footer.style.width = 'calc(100% - 250px)';
                    }
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const contentWrapper = document.getElementById('content-wrapper');
            const topbar = document.querySelector('.topbar');
            const footer = document.querySelector('footer');
            
            if (window.innerWidth > 768) {
                // Desktop: show sidebar
                sidebar.style.display = 'block';
                sidebar.style.width = '250px';
                sidebar.style.position = 'fixed';
                contentWrapper.style.marginLeft = '250px';
                contentWrapper.style.width = 'calc(100% - 250px)';
                topbar.style.left = '250px';
                topbar.style.width = 'calc(100% - 250px)';
                topbar.style.position = 'fixed';
                if (footer) {
                    footer.style.marginLeft = '250px';
                    footer.style.width = 'calc(100% - 250px)';
                }
                document.body.style.paddingTop = '70px';
            } else {
                // Mobile: hide sidebar by default
                sidebar.style.display = 'none';
                sidebar.style.width = '100%';
                sidebar.style.position = 'relative';
                contentWrapper.style.marginLeft = '0';
                contentWrapper.style.width = '100%';
                topbar.style.left = '0';
                topbar.style.width = '100%';
                topbar.style.position = 'relative';
                if (footer) {
                    footer.style.marginLeft = '0';
                    footer.style.width = '100%';
                }
                document.body.style.paddingTop = '0';
            }
        });

        // Initialize on load
        window.dispatchEvent(new Event('resize'));
    </script>
</body>
</html>