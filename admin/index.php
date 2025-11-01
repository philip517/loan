<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch budget data (if you have a budget table)
$budget_query = "SELECT amount, balance FROM budget ORDER BY start_date DESC LIMIT 1";
$budget_stmt = $pdo->query($budget_query);
$budget = $budget_stmt->fetch(PDO::FETCH_ASSOC);

$original_budget_amount = $budget ? $budget['amount'] : 0;

// Fetch loan statistics using correct table structure
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

// Calculate current balance
$current_budget_balance = $original_budget_amount - ($loan_stats['total_approved_loan_amount'] ?? 0);
if ($current_budget_balance < 0) {
    $current_budget_balance = 0;
}

$total_earnings = $loan_stats['total_interest_earnings'] ?? 0;

// Calculate loan usage percentage
$loan_usage = 0;
if ($original_budget_amount > 0) {
    $loan_usage = (($loan_stats['total_approved_loan_amount'] ?? 0) / $original_budget_amount) * 100;
}

// Fetch overdue loans
$overdue_loans_query = "
    SELECT COUNT(*) as overdue_count 
    FROM loan 
    WHERE status = 'approved' 
    AND loan_end_date < CURDATE()
";
$overdue_loans_stmt = $pdo->query($overdue_loans_query);
$overdue_loans = $overdue_loans_stmt->fetch(PDO::FETCH_ASSOC);

// Since there's no loan_reviews table, let's get admin's reviewed loans from loan table
// We'll assume admin_id is stored in loan table or we'll need to modify the approach
$admin_reviews_query = "
    SELECT 
        COUNT(*) as admin_review_count,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as admin_approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as admin_rejected,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as admin_pending
    FROM loan 
    WHERE admin_notes IS NOT NULL
    -- If you have an admin_id field in loan table, use: WHERE admin_id = ?
";
$admin_reviews_stmt = $pdo->prepare($admin_reviews_query);
$admin_reviews_stmt->execute();
$admin_reviews = $admin_reviews_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent overdue loans for display
$recent_overdue_query = "
    SELECT l.loan_id, l.loan_number, l.amount, l.loan_end_date,
           DATEDIFF(CURDATE(), l.loan_end_date) as days_overdue,
           u.first_name, u.last_name
    FROM loan l 
    JOIN admin u ON l.user_id = u.user_id 
    WHERE l.status = 'approved' 
    AND l.loan_end_date < CURDATE()
    ORDER BY l.loan_end_date ASC 
    LIMIT 5
";
$recent_overdue_stmt = $pdo->query($recent_overdue_query);
$recent_overdue_loans = $recent_overdue_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get admin info for display
$admin_info_query = "SELECT first_name, last_name FROM admin WHERE user_id = ?";
$admin_info_stmt = $pdo->prepare($admin_info_query);
$admin_info_stmt->execute([$user_id]);
$admin_info = $admin_info_stmt->fetch(PDO::FETCH_ASSOC);
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
                    <span class="text-muted">Welcome, <?php echo $admin_info['first_name'] . ' ' . $admin_info['last_name']; ?></span>
                </div>
                
                <!-- Loan Status Overview Cards -->
                <div class="row">
                    <!-- Approved Loans Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow py-2 border-left-success">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                                            <span>Approved Loans</span>
                                        </div>
                                        <div class="text-dark mb-0 fw-bold h5">
                                            <span><?php echo $loan_stats['approved_loans'] ?? 0; ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Total: $<?php echo number_format($loan_stats['total_approved_loan_amount'] ?? 0, 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-check-circle fa-2x text-success"></i></div>
                                </div>
                                <div class="mt-2">
                                    <a href="loan.php?status=approved" class="btn btn-sm btn-outline-success w-100">
                                        View Approved Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Loans Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow py-2 border-left-warning">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                            <span>Pending Loans</span>
                                        </div>
                                        <div class="text-dark mb-0 fw-bold h5">
                                            <span><?php echo $loan_stats['pending_loans'] ?? 0; ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Awaiting review</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-clock fa-2x text-warning"></i></div>
                                </div>
                                <div class="mt-2">
                                    <a href="loan.php?status=pending" class="btn btn-sm btn-outline-warning w-100">
                                        Review Pending Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rejected Loans Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow py-2 border-left-danger">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-uppercase text-danger mb-1 fw-bold text-xs">
                                            <span>Rejected Loans</span>
                                        </div>
                                        <div class="text-dark mb-0 fw-bold h5">
                                            <span><?php echo $loan_stats['rejected_loans'] ?? 0; ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Not approved</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-times-circle fa-2x text-danger"></i></div>
                                </div>
                                <div class="mt-2">
                                    <a href="loan.php?status=rejected" class="btn btn-sm btn-outline-danger w-100">
                                        View Rejected Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overdue Loans Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow py-2 border-left-danger">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-uppercase text-danger mb-1 fw-bold text-xs">
                                            <span>Overdue Loans</span>
                                        </div>
                                        <div class="text-dark mb-0 fw-bold h5">
                                            <span><?php echo $overdue_loans['overdue_count'] ?? 0; ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Past due date</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-danger"></i></div>
                                </div>
                                <div class="mt-2">
                                    <a href="loan.php?status=approved&overdue=true" class="btn btn-sm btn-danger w-100">
                                        <i class="fas fa-eye me-1"></i>View Overdue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Activity & Quick Actions Section -->
                <div class="row">
                    <!-- My Activity Card -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="text-primary m-0 fw-bold">My Activity Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <div class="text-success fw-bold h4"><?php echo $admin_reviews['admin_approved'] ?? 0; ?></div>
                                            <div class="text-muted small">Approved</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <div class="text-danger fw-bold h4"><?php echo $admin_reviews['admin_rejected'] ?? 0; ?></div>
                                            <div class="text-muted small">Rejected</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-warning fw-bold h4"><?php echo $admin_reviews['admin_pending'] ?? 0; ?></div>
                                        <div class="text-muted small">Pending</div>
                                    </div>
                                </div>
                                
                                <!-- Budget Summary -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-2">Budget Overview</h6>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?php echo min($loan_usage, 100); ?>%;"
                                                 title="Loan Usage: <?php echo number_format($loan_usage, 1); ?>%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span>Available: $<?php echo number_format($current_budget_balance, 2); ?></span>
                                            <span>Used: <?php echo number_format($loan_usage, 1); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="loan.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-tasks me-1"></i>View All Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="text-primary m-0 fw-bold">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="loan.php" class="btn btn-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                                            <span>Manage Loans</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="message.php" class="btn btn-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="fas fa-envelope fa-2x mb-2"></i>
                                            <span>Messaging</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="profile.php" class="btn btn-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="fas fa-user fa-2x mb-2"></i>
                                            <span>My Profile</span>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="users.php" class="btn btn-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                            <i class="fas fa-users fa-2x mb-2"></i>
                                            <span>Users</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Loans List -->
                <?php if (!empty($recent_overdue_loans)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-danger text-white">
                                <h6 class="m-0 fw-bold">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Recent Overdue Loans
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Loan Number</th>
                                                <th>Client Name</th>
                                                <th>Amount</th>
                                                <th>Due Date</th>
                                                <th>Days Overdue</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_overdue_loans as $overdue_loan): ?>
                                            <tr>
                                                <td><?php echo $overdue_loan['loan_number'] ?? 'N/A'; ?></td>
                                                <td><?php echo $overdue_loan['first_name'] . ' ' . $overdue_loan['last_name']; ?></td>
                                                <td>$<?php echo number_format($overdue_loan['amount'], 2); ?></td>
                                                <td><?php echo $overdue_loan['loan_end_date']; ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?php echo $overdue_loan['days_overdue']; ?> days
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="loan_details.php?loan_id=<?php echo $overdue_loan['loan_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View Loan
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="loan.php?status=approved&overdue=true" class="btn btn-danger">
                                        <i class="fas fa-list me-1"></i>View All Overdue Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-success text-white">
                                <h6 class="m-0 fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>
                                    No Overdue Loans
                                </h6>
                            </div>
                            <div class="card-body text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">Great News!</h5>
                                <p class="text-muted">All approved loans are currently up to date with their payments.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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