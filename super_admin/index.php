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

$original_budget_amount = $budget ? $budget['amount'] : 0;

// Fetch loan statistics
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

$total_earnings = $loan_stats['total_interest_earnings'];

// Calculate loan usage percentage
$loan_usage = 0;
if ($original_budget_amount > 0) {
    $loan_usage = (($loan_stats['total_approved_loan_amount'] ?? 0) / $original_budget_amount) * 100;
}

$budget_usage = (($loan_stats['total_approved_loan_amount'] ?? 0) / $original_budget_amount) * 100;
$available_for_loans = $original_budget_amount - ($loan_stats['total_approved_loan_amount'] ?? 0);

// Fetch loan applications by month for the current year
$current_year = date('Y');
$monthly_loans_query = "
    SELECT 
        MONTH(loan_start_date) as month,
        COUNT(*) as loan_count
    FROM loan 
    WHERE loan_start_date IS NOT NULL 
    AND YEAR(loan_start_date) = ?
    GROUP BY MONTH(loan_start_date)
    ORDER BY month
";
$monthly_loans_stmt = $pdo->prepare($monthly_loans_query);
$monthly_loans_stmt->execute([$current_year]);
$monthly_loans = $monthly_loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data for January to December
$chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$chart_data = array_fill(0, 12, 0); // Initialize all months with 0

// Fill in actual data for months that have loans
foreach ($monthly_loans as $monthly_loan) {
    $month_index = $monthly_loan['month'] - 1; // Convert month (1-12) to array index (0-11)
    if ($month_index >= 0 && $month_index < 12) {
        $chart_data[$month_index] = $monthly_loan['loan_count'];
    }
}

// Fetch ALL loan requests for ALL admins
try {
    $all_requests_query = "
        SELECT 
            lr.request_id,
            lr.request_message,
            lr.status as request_status,
            lr.date_of_request,
            lr.date_updated,
            lr.admin_notes,
            l.loan_id,
            l.loan_number,
            l.amount,
            l.status as loan_status,
            u.first_name as client_first_name,
            u.last_name as client_last_name,
            u.phone as client_phone,
            a.first_name as admin_first_name,
            a.last_name as admin_last_name,
            a.user_id as admin_id
        FROM loan_requests lr
        JOIN loan l ON lr.loan_id = l.loan_id
        JOIN user_table u ON l.user_id = u.user_id
        JOIN user_table a ON lr.admin_id = a.user_id
        ORDER BY lr.date_of_request DESC 
        LIMIT 8
    ";
    $all_requests_stmt = $pdo->query($all_requests_query);
    $all_requests = $all_requests_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_requests = [];
}

// Fetch request statistics for ALL admins
try {
    $all_request_stats_query = "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests
        FROM loan_requests
    ";
    $all_request_stats_stmt = $pdo->query($all_request_stats_query);
    $all_request_stats = $all_request_stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_request_stats = [
        'total_requests' => 0,
        'pending_requests' => 0,
        'approved_requests' => 0,
        'rejected_requests' => 0,
        'completed_requests' => 0
    ];
}

// Fetch recent loan applications
$recent_loans_query = "
    SELECT 
        l.loan_id,
        l.loan_number,
        l.amount,
        l.interest,
        l.loan_start_date,
        l.status,
        u.first_name,
        u.last_name,
        u.phone,
        DATEDIFF(CURDATE(), l.loan_start_date) as days_since_application
    FROM loan l 
    JOIN admin u ON l.user_id = u.user_id 
    ORDER BY l.loan_start_date DESC 
    LIMIT 8
";
$recent_loans_stmt = $pdo->query($recent_loans_query);
$recent_loans = $recent_loans_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .loan-table {
            font-size: 0.875rem;
        }
        .table-hover tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-badge-pending { background-color: #ffc107; color: #000; }
        .request-badge-approved { background-color: #28a745; }
        .request-badge-rejected { background-color: #dc3545; }
        .request-badge-completed { background-color: #17a2b8; }
        .request-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .request-card-pending { border-left-color: #ffc107; }
        .request-card-approved { border-left-color: #28a745; }
        .request-card-rejected { border-left-color: #dc3545; }
        .request-card-completed { border-left-color: #17a2b8; }
        .admin-badge {
            background: linear-gradient(45deg, #6c757d, #495057);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .my-request-badge {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
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
                                            <span>K<?php echo number_format($current_budget_balance, 2); ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Original: K<?php echo number_format($original_budget_amount, 2); ?></span>
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
                                            <span>K<?php echo number_format($total_earnings, 2); ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>Budget + Interest</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-coins fa-2x text-gray-300"></i></div>
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
                                            <span>K<?php echo number_format($loan_stats['total_approved_loan_amount'] ?? 0, 2); ?> used</span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span>K<?php echo number_format($available_for_loans, 2); ?> available</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-chart-line fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- All Requests Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow py-2 border-left-warning">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                            <span>All Requests</span>
                                        </div>
                                        <div class="text-dark mb-0 fw-bold h5">
                                            <span><?php echo $all_request_stats['total_requests'] ?? 0; ?></span>
                                        </div>
                                        <div class="text-xs text-muted">
                                            <span><?php echo $all_request_stats['pending_requests'] ?? 0; ?> pending</span>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                                </div>
                                <div class="mt-2">
                                    <a href="loan_request.php" class="btn btn-sm btn-outline-warning w-100">
                                        View All Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 1: Charts Section -->
                <div class="row">
                    <!-- Loan Applications Overview Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="text-primary m-0 fw-bold">Loan Applications Overview (<?php echo $current_year; ?>)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="loanApplicationsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loan Status Distribution Chart -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
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

                <!-- Row 2: Loan Requests Section -->
                <div class="row">
                    <!-- Recent Loan Requests Table -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="text-primary m-0 fw-bold">Recent Loan Requests (All Admins)</h6>
                                <a href="loan_request.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover loan-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Loan Details</th>
                                                <th>Request Message</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($all_requests)): ?>
                                                <?php foreach ($all_requests as $request): 
                                                    $badge_class = 'request-badge-' . $request['request_status'];
                                                    $card_class = 'request-card-' . $request['request_status'];
                                                    $is_my_request = ($request['admin_id'] == $user_id);
                                                ?>
                                                <tr class="clickable-row <?php echo $card_class; ?>" onclick="window.location='loan_request.php?loan_id=<?php echo $request['loan_id']; ?>'">
                                                    <td>
                                                        <strong>#<?php echo $request['request_id']; ?></strong>
                                                        <?php if ($is_my_request): ?>
                                                            <br><span class="my-request-badge">My Request</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong>Loan #<?php echo $request['loan_number']; ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo $request['client_first_name'] . ' ' . $request['client_last_name']; ?>
                                                            </small>
                                                            <br>
                                                            <small class="text-muted">
                                                                K<?php echo number_format($request['amount'], 2); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                            <?php echo htmlspecialchars($request['request_message']); ?>
                                                        </div>
                                                        <?php if (!empty($request['admin_notes'])): ?>
                                                            <small class="text-info">
                                                                <i class="fas fa-sticky-note"></i> Has notes
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="admin-badge">
                                                            <?php echo $request['admin_first_name'] . ' ' . $request['admin_last_name']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $badge_class; ?> status-badge">
                                                            <?php echo ucfirst($request['request_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y', strtotime($request['date_of_request'])); ?>
                                                            <br>
                                                            <small><?php echo date('g:i A', strtotime($request['date_of_request'])); ?></small>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <a href="loan_request.php?loan_id=<?php echo $request['loan_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           onclick="event.stopPropagation()">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                                        <p class="text-muted mb-0">No loan requests found</p>
                                                        <small class="text-muted">Loan requests will appear here when created by admins</small>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- All Requests Summary -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="text-primary m-0 fw-bold">All Requests Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="border-end">
                                            <div class="text-warning fw-bold h4"><?php echo $all_request_stats['pending_requests'] ?? 0; ?></div>
                                            <div class="text-muted small">Pending</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="text-success fw-bold h4"><?php echo $all_request_stats['approved_requests'] ?? 0; ?></div>
                                        <div class="text-muted small">Approved</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border-end">
                                            <div class="text-danger fw-bold h4"><?php echo $all_request_stats['rejected_requests'] ?? 0; ?></div>
                                            <div class="text-muted small">Rejected</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-info fw-bold h4"><?php echo $all_request_stats['completed_requests'] ?? 0; ?></div>
                                        <div class="text-muted small">Completed</div>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="loan_request.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-list me-1"></i>Manage All Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: System Statistics and Todo List -->
                <div class="row">
                    <!-- System Statistics -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
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
                    
                    <!-- Todo List -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
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
                                            <span class="text-xs">Current: K<?php echo number_format($current_budget_balance, 2); ?></span>
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
                                            <h6 class="mb-0"><strong>Check All Requests</strong></h6>
                                            <span class="text-xs"><?php echo $all_request_stats['pending_requests'] ?? 0; ?> requests pending</span>
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
    <script>
        // Make table rows clickable
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const onclickAttr = this.getAttribute('onclick');
                    if (onclickAttr) {
                        const url = onclickAttr.replace('window.location=', '').replace(/'/g, '');
                        window.location = url;
                    }
                });
            });

            // Initialize Loan Applications Chart with real data
            const loanApplicationsChart = new Chart(
                document.getElementById('loanApplicationsChart'),
                {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Loan Applications',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            data: <?php echo json_encode($chart_data); ?>
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: {
                            display: false
                        },
                        scales: {
                            xAxes: [{
                                gridLines: {
                                    color: 'rgb(234, 236, 244)',
                                    zeroLineColor: 'rgb(234, 236, 244)',
                                    drawBorder: false,
                                    drawTicks: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2],
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    fontColor: '#858796',
                                    padding: 20
                                }
                            }],
                            yAxes: [{
                                gridLines: {
                                    color: 'rgb(234, 236, 244)',
                                    zeroLineColor: 'rgb(234, 236, 244)',
                                    drawBorder: false,
                                    drawTicks: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2]
                                },
                                ticks: {
                                    fontColor: '#858796',
                                    padding: 20,
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }]
                        }
                    }
                }
            );
        });
    </script>
</body>
</html>