<?php
require 'auth_admin.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Get paid loans
$paid_query = "
    SELECT 
        l.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        DATEDIFF(l.loan_end_date, l.loan_start_date) as loan_duration
    FROM loan l
    JOIN user_table u ON l.user_id = u.user_id
    WHERE l.status = 'approved' 
    AND l.progress = 'paid'
    ORDER BY l.loan_end_date DESC
";

$paid_stmt = $pdo->query($paid_query);
$paid_loans = $paid_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_revenue = 0;
$total_principal = 0;
$total_interest = 0;
$total_penalties = 0;
foreach ($paid_loans as $loan) {
    $total_principal += $loan['amount'];
    $total_interest += $loan['interest'];
    $total_penalties += $loan['penalty_fee'];
    $total_revenue += ($loan['interest'] + $loan['penalty_fee']);
}

// Get monthly revenue
$monthly_revenue_query = "
    SELECT 
        MONTH(loan_end_date) as month,
        SUM(interest + penalty_fee) as revenue
    FROM loan 
    WHERE status = 'approved' 
    AND progress = 'paid'
    AND YEAR(loan_end_date) = YEAR(CURDATE())
    GROUP BY MONTH(loan_end_date)
    ORDER BY month
";

$monthly_revenue_stmt = $pdo->query($monthly_revenue_query);
$monthly_revenue = $monthly_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Paid Loans - Admin</title>
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
            margin-left:100px;
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
        }
        
        /* Card styling */
        .finance-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .finance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .border-success {
            border-top: 4px solid #28a745 !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.05);
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
                        <h3 class="text-dark mb-0"><strong>PAID LOANS</strong></h3>
                        <p class="text-muted mb-0">Completed loans with full repayment</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="finance_main.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <span class="badge bg-success fs-6 p-2">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo count($paid_loans); ?> Completed Loans
                        </span>
                    </div>
                </div>

                <!-- Revenue Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card shadow finance-card border-success">
                            <div class="card-body text-center">
                                <h1 class="text-success mb-1">K<?php echo number_format($total_revenue, 2); ?></h1>
                                <p class="text-muted mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card shadow finance-card border-success">
                            <div class="card-body text-center">
                                <h1 class="text-success mb-1">K<?php echo number_format($total_interest, 2); ?></h1>
                                <p class="text-muted mb-0">Interest Earned</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card shadow finance-card border-success">
                            <div class="card-body text-center">
                                <h1 class="text-success mb-1">K<?php echo number_format($total_penalties, 2); ?></h1>
                                <p class="text-muted mb-0">Penalties Collected</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card shadow finance-card border-success">
                            <div class="card-body text-center">
                                <h1 class="text-success mb-1"><?php echo count($paid_loans); ?></h1>
                                <p class="text-muted mb-0">Loans Completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paid Loans Table -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Completed Loans History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($paid_loans)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">No completed loans yet</h4>
                                        <p class="text-muted">Loan completion history will appear here</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Borrower</th>
                                                    <th>Loan Number</th>
                                                    <th>Principal</th>
                                                    <th>Interest</th>
                                                    <th>Penalties</th>
                                                    <th>Total Paid</th>
                                                    <th>Duration</th>
                                                    <th>Completion Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paid_loans as $index => $loan): 
                                                    $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
                                                ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo $full_name; ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($loan['email']); ?></small>
                                                    </td>
                                                    <td><code>LID<?php echo str_pad($loan['loan_Number'], 6, '0', STR_PAD_LEFT); ?></code></td>
                                                    <td>K<?php echo number_format($loan['amount'], 2); ?></td>
                                                    <td class="text-success">K<?php echo number_format($loan['interest'], 2); ?></td>
                                                    <td class="text-warning">K<?php echo number_format($loan['penalty_fee'], 2); ?></td>
                                                    <td>
                                                        <strong>K<?php echo number_format($loan['amount'] + $loan['interest'] + $loan['penalty_fee'], 2); ?></strong>
                                                    </td>
                                                    <td><?php echo $loan['loan_duration']; ?> days</td>
                                                    <td><?php echo date('M d, Y', strtotime($loan['loan_end_date'])); ?></td>
                                                    <td>
                                                        <a href="view_loan.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-receipt me-1"></i>Receipt
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Monthly Revenue (<?php echo date('Y'); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr class="bg-light">
                                                <th>Month</th>
                                                <th>Revenue (K)</th>
                                                <th>Percentage</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                            $monthly_data = array_fill(0, 12, 0);
                                            
                                            foreach ($monthly_revenue as $data) {
                                                $monthly_data[$data['month'] - 1] = $data['revenue'];
                                            }
                                            
                                            $max_revenue = max($monthly_data) ?: 1;
                                            
                                            for ($i = 0; $i < 12; $i++): 
                                                $revenue = $monthly_data[$i];
                                                $percentage = ($revenue / $max_revenue) * 100;
                                            ?>
                                            <tr>
                                                <td><strong><?php echo $month_names[$i]; ?></strong></td>
                                                <td>K<?php echo number_format($revenue, 2); ?></td>
                                                <td><?php echo number_format($percentage, 1); ?>%</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%;" 
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
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