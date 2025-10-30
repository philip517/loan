<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch budget data
$budget_query = "SELECT amount, balance FROM budget ORDER BY start_date DESC LIMIT 1";
$budget_stmt = $pdo->query($budget_query);
$budget = $budget_stmt->fetch(PDO::FETCH_ASSOC);

$original_budget_amount = $budget ? $budget['amount'] : 0;    // Original total budget

// Fetch loan statistics - calculate total approved loan amount
$loan_stats_query = "
    SELECT 
        COUNT(*) as total_loans,
        SUM(amount) as total_loan_amount,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved_loan_amount,
        SUM(CASE WHEN status = 'approved' THEN (interest) ELSE 0 END) as total_interest_earnings,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_loans,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_loans,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_loans
    FROM loan
";
$loan_stats_stmt = $pdo->query($loan_stats_query);
$loan_stats = $loan_stats_stmt->fetch(PDO::FETCH_ASSOC);

// CALCULATE CURRENT BALANCE: Budget Amount - Total Approved Loans
$current_budget_balance = $original_budget_amount - ($loan_stats['total_approved_loan_amount'] ?? 0);

// Ensure balance doesn't go negative
if ($current_budget_balance < 0) {
    $current_budget_balance = 0;
}

// CORRECT TOTAL EARNINGS CALCULATION:
$total_earnings = $loan_stats['total_interest_earnings'];

// Calculate loan usage percentage
$loan_usage = 0;
if ($original_budget_amount > 0) {
    $loan_usage = (($loan_stats['total_approved_loan_amount'] ?? 0) / $original_budget_amount) * 100;
}

$budget_usage = (($loan_stats['total_approved_loan_amount'] ?? 0) / $original_budget_amount) * 100;
// Calculate available for loans
$available_for_loans = $original_budget_amount - ($loan_stats['total_approved_loan_amount'] ?? 0);
// Fetch user statistics
$user_stats_query = "
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
        COUNT(CASE WHEN role = 'super_admin' THEN 1 END) as super_admin_users,
        COUNT(CASE WHEN role = 'client' OR role IS NULL THEN 1 END) as client_users
    FROM user_table
";
$user_stats_stmt = $pdo->query($user_stats_query);
$user_stats = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Admin Dashboard</title>
    <meta name="description" content="Home Page of Admin">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;filter: blur(0px);">
            <div class="container-fluid" style="opacity: 0.97;margin-top: 100px;">
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <h3 class="text-dark mb-0"><strong>ADMIN DASHBOARD</strong></h3>
                </div>
                
               <!-- Financial Overview Cards -->
<div class="row">
    <!-- Current Budget Card -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow py-2 border-left-primary">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-uppercase text-primary mb-1 fw-bold text-xs">
                            <span>Current Budget</span>
                        </div>
                        <div class="text-dark mb-0 fw-bold h5">
                            <span>$<?php echo number_format($current_budget_balance, 2); ?></span>
                        </div>
                        <div class="text-xs text-muted">
                            <span>Original: $<?php echo number_format($original_budget_amount, 2); ?></span>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-wallet fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Earnings Card -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow py-2 border-left-success">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                            <span>Total Earnings</span>
                        </div>
                        <div class="text-dark mb-0 fw-bold h5">
                            <span>$<?php echo number_format($total_earnings, 2); ?></span>
                        </div>
                        <div class="text-xs text-muted">
                            <span>Budget + Interest</span>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loan Usage Card -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow py-2 border-left-info">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-uppercase text-info mb-1 fw-bold text-xs">
                            <span>Loan Usage</span>
                        </div>
                        <div class="text-dark mb-0 fw-bold h5">
                            <span><?php echo number_format($loan_usage, 1); ?>%</span>
                        </div>
                        <div class="text-xs text-muted">
                            <span>$<?php echo number_format($loan_stats['total_approved_loan_amount'] ?? 0, 2); ?> used</span>
                        </div>
                        <div class="text-xs text-muted">
                            <span>$<?php echo number_format($available_for_loans, 2); ?> available</span>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-chart-line fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Loans Card -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow py-2 border-left-warning">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                            <span>Total Loans</span>
                        </div>
                        <div class="text-dark mb-0 fw-bold h5">
                            <span><?php echo $loan_stats['total_loans'] ?? 0; ?></span>
                        </div>
                        <div class="text-xs text-muted">
                            <span>All Time</span>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>
          

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-lg-7 col-xl-8">
                        <div class="card shadow mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="text-primary m-0 fw-bold">Loan Applications Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas data-bss-chart="{&quot;type&quot;:&quot;line&quot;,&quot;data&quot;:{&quot;labels&quot;:[&quot;Jan&quot;,&quot;Feb&quot;,&quot;Mar&quot;,&quot;Apr&quot;,&quot;May&quot;,&quot;Jun&quot;,&quot;Jul&quot;,&quot;Aug&quot;],&quot;datasets&quot;:[{&quot;label&quot;:&quot;Loan Applications&quot;,&quot;fill&quot;:true,&quot;data&quot;:[&quot;0&quot;,&quot;50&quot;,&quot;100&quot;,&quot;150&quot;,&quot;200&quot;,&quot;250&quot;,&quot;300&quot;,&quot;350&quot;,&quot;400&quot;],&quot;backgroundColor&quot;:&quot;rgba(78, 115, 223, 0.05)&quot;,&quot;borderColor&quot;:&quot;rgba(78, 115, 223, 1)&quot;}]},&quot;options&quot;:{&quot;maintainAspectRatio&quot;:false,&quot;legend&quot;:{&quot;display&quot;:false,&quot;labels&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;}},&quot;title&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;},&quot;scales&quot;:{&quot;xAxes&quot;:[{&quot;gridLines&quot;:{&quot;color&quot;:&quot;rgb(234, 236, 244)&quot;,&quot;zeroLineColor&quot;:&quot;rgb(234, 236, 244)&quot;,&quot;drawBorder&quot;:false,&quot;drawTicks&quot;:false,&quot;borderDash&quot;:[&quot;2&quot;],&quot;zeroLineBorderDash&quot;:[&quot;2&quot;],&quot;drawOnChartArea&quot;:false},&quot;ticks&quot;:{&quot;fontColor&quot;:&quot;#858796&quot;,&quot;fontStyle&quot;:&quot;normal&quot;,&quot;padding&quot;:20}}],&quot;yAxes&quot;:[{&quot;gridLines&quot;:{&quot;color&quot;:&quot;rgb(234, 236, 244)&quot;,&quot;zeroLineColor&quot;:&quot;rgb(234, 236, 244)&quot;,&quot;drawBorder&quot;:false,&quot;drawTicks&quot;:false,&quot;borderDash&quot;:[&quot;2&quot;],&quot;zeroLineBorderDash&quot;:[&quot;2&quot;]},&quot;ticks&quot;:{&quot;fontColor&quot;:&quot;#858796&quot;,&quot;fontStyle&quot;:&quot;normal&quot;,&quot;padding&quot;:20}}]}}}"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-xl-4">
                        <div class="card shadow mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="text-primary m-0 fw-bold">Loan Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas data-bss-chart="{&quot;type&quot;:&quot;doughnut&quot;,&quot;data&quot;:{&quot;labels&quot;:[&quot;Pending&quot;,&quot;Approved&quot;,&quot;Rejected&quot;],&quot;datasets&quot;:[{&quot;label&quot;:&quot;&quot;,&quot;backgroundColor&quot;:[&quot;#f6c23e&quot;,&quot;#1cc88a&quot;,&quot;#e74a3b&quot;],&quot;borderColor&quot;:[&quot;#ffffff&quot;,&quot;#ffffff&quot;,&quot;#ffffff&quot;],&quot;data&quot;:[&quot;<?php echo $loan_stats['pending_loans'] ?? 0; ?>&quot;,&quot;<?php echo $loan_stats['approved_loans'] ?? 0; ?>&quot;,&quot;<?php echo $loan_stats['rejected_loans'] ?? 0; ?>&quot;]}]},&quot;options&quot;:{&quot;maintainAspectRatio&quot;:false,&quot;legend&quot;:{&quot;display&quot;:false,&quot;labels&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;}},&quot;title&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;}}}"></canvas>
                                </div>
                                <div class="text-center mt-4 small">
                                    <span class="me-2"><i class="fas fa-circle text-warning"></i>&nbsp;Pending</span>
                                    <span class="me-2"><i class="fas fa-circle text-success"></i>&nbsp;Approved</span>
                                    <span class="me-2"><i class="fas fa-circle text-danger"></i>&nbsp;Rejected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bars Section -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="text-primary m-0 fw-bold">System Statistics</h6>
                            </div>
                            <div class="card-body">
                                <h4 class="small fw-bold">Budget Utilization<span class="float-end"><?php echo number_format($budget_usage, 1); ?>%</span></h4>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-<?php echo $budget_usage > 80 ? 'danger' : ($budget_usage > 60 ? 'warning' : 'success'); ?>" 
                                         aria-valuenow="<?php echo $budget_usage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100" 
                                         style="width: <?php echo $budget_usage; ?>%;">
                                        <span class="visually-hidden"><?php echo number_format($budget_usage, 1); ?>%</span>
                                    </div>
                                </div>
                                
                                <h4 class="small fw-bold">Loan Approval Rate<span class="float-end">
                                    <?php 
                                    $approval_rate = 0;
                                    if ($loan_stats['total_loans'] > 0) {
                                        $approval_rate = ($loan_stats['approved_loans'] / $loan_stats['total_loans']) * 100;
                                    }
                                    echo number_format($approval_rate, 1); ?>%
                                </span></h4>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-success" 
                                         aria-valuenow="<?php echo $approval_rate; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100" 
                                         style="width: <?php echo $approval_rate; ?>%;">
                                        <span class="visually-hidden"><?php echo number_format($approval_rate, 1); ?>%</span>
                                    </div>
                                </div>
                                
                                <h4 class="small fw-bold">System Performance<span class="float-end">85%</span></h4>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-info" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="width: 85%;">
                                        <span class="visually-hidden">85%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Todo List (Keep existing) -->
                    <div class="col">
                        <div class="row">
                            <div class="col col-lg-11">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="text-primary m-0 fw-bold">Todo List</h6>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <div class="row g-0 align-items-center">
                                                <div class="col me-2">
                                                    <h6 class="mb-0"><strong>Review Pending Loans</strong></h6>
                                                    <span class="text-xs"><?php echo $loan_stats['pending_loans'] ?? 0; ?> applications waiting</span>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="formCheck-1">
                                                        <label class="form-check-label" for="formCheck-1"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="row g-0 align-items-center">
                                                <div class="col me-2">
                                                    <h6 class="mb-0"><strong>Update Budget</strong></h6>
                                                    <span class="text-xs">Current: $<?php echo number_format($current_budget_balance, 2); ?></span>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="formCheck-2">
                                                        <label class="form-check-label" for="formCheck-2"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="row g-0 align-items-center">
                                                <div class="col me-2">
                                                    <h6 class="mb-0"><strong>Generate Monthly Report</strong></h6>
                                                    <span class="text-xs">Due today</span>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="formCheck-3">
                                                        <label class="form-check-label" for="formCheck-3"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
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
                    <span>Copyright © Brand 2025</span>
                </div>
            </div>
        </footer>
    </div>
    
    <a class="border rounded d-inline scroll-to-top" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script src="assets/js/chart.min.min.js"></script>
</body>
</html>