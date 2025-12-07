<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Function to calculate totals for a set of loans
function calculateLoanTotals($loans) {
    $total_amount = 0;
    $total_interest = 0;
    $total_loans = count($loans);
    $total_penalty = 0;
    
    foreach ($loans as $loan) {
        $total_amount += $loan['amount'];
        $total_interest += $loan['interest'] ?? 0;
        $total_penalty += $loan['penalty_fee'] ?? 0;
    }
    
    return [
        'total_amount' => $total_amount,
        'total_interest' => $total_interest,
        'total_loans' => $total_loans,
        'total_penalty' => $total_penalty
    ];
}

// Fetch loans with user information
try {
    // Pending loans
    $pending_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'pending' OR l.status IS NULL
    ");
    $pending_stmt->execute();
    $pending_loans = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending_totals = calculateLoanTotals($pending_loans);
    
    // Approved loans - only those with progress = 'current'
    $approved_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'approved' 
        AND l.progress = 'current'
    ");
    $approved_stmt->execute();
    $approved_loans = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);
    $approved_totals = calculateLoanTotals($approved_loans);
    
    // Rejected loans
    $rejected_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'rejected'
    ");
    $rejected_stmt->execute();
    $rejected_loans = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);
    $rejected_totals = calculateLoanTotals($rejected_loans);
    
    // Paid loans - loans with progress = 'paid'
    $paid_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'approved' 
        AND l.progress = 'paid'
    ");
    $paid_stmt->execute();
    $paid_loans = $paid_stmt->fetchAll(PDO::FETCH_ASSOC);
    $paid_totals = calculateLoanTotals($paid_loans);
    
    // Overdue loans - approved loans with progress = 'overdue'
    $overdue_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation,
               DATEDIFF(CURDATE(), l.loan_end_date) as days_overdue
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'approved' 
        AND l.progress = 'overdue'
    ");
    $overdue_stmt->execute();
    $overdue_loans = $overdue_stmt->fetchAll(PDO::FETCH_ASSOC);
    $overdue_totals = calculateLoanTotals($overdue_loans);
    
} catch (PDOException $e) {
    die("Error fetching loans: " . $e->getMessage());
}

// Function to display loan data in table rows
function displayLoans($loans, $tableId) {
    if (empty($loans)) {
        echo '<tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <p class="text-muted mb-0">No loans found</p>
                </td>
              </tr>';
        return;
    }
    
    foreach ($loans as $loan) {
        $loan_number = htmlspecialchars($loan['loan_number'] ?? 'N/A');
        $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
        $occupation = htmlspecialchars($loan['occupation'] ?? 'Not specified');
        $collateral = htmlspecialchars($loan['collateral_name'] ?? 'No collateral');
        $duration = htmlspecialchars($loan['duration'] . ' week(s)');
        $amount = 'K' . number_format($loan['amount'], 2);
        $loan_date = htmlspecialchars($loan['loan_start_date'] ?? 'N/A');
        $loan_id = $loan['loan_id'];
        
        // Format date for better display
        $formatted_date = $loan_date !== 'N/A' ? date('M j, Y', strtotime($loan_date)) : 'N/A';
        
        echo "
        <tr class='clickable-row' data-loan-number='$loan_number'>
            <td><strong>$loan_number</strong></td>
            <td>$full_name</td>
            <td>$occupation</td>
            <td>$collateral</td>
            <td>$duration</td>
            <td>$amount</td>
            <td>$formatted_date</td>
        </tr>";
    }
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loans Management</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <link rel="stylesheet" href="assets/css/loan.css">
       <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }
        .tab-pane {
            padding-top: 1rem;
        }
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .status-badge {
            font-size: 0.7em;
            padding: 0.3em 0.6em;
        }
        .overdue-row {
            background-color: rgba(220, 53, 69, 0.05);
        }
        .overdue-row:hover {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .paid-row {
            background-color: rgba(40, 167, 69, 0.05);
        }
        .paid-row:hover {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }
        /* Card Styles */
        .summary-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s ease;
        }
        .summary-card:hover {
            transform: translateY(-2px);
        }
        .card-icon {
            font-size: 1.5rem;
            opacity: 0.8;
        }
        .card-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        .card-value {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .card-amount {
            color: #2c3e50;
        }
        .card-interest {
            color: #28a745;
        }
        .card-count {
            color: #007bff;
        }
        .card-penalty {
            color: #dc3545;
        }
        .summary-row {
            margin-bottom: 1.5rem;
        }
        
        /* Fixed layout styles */
        body {
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles - FIXED */
        .sidebar {
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px !important;
            overflow-y: auto;
            z-index: 1030;
        }
        
        /* Content wrapper - this wraps both topbar and main content */
        #content-wrapper {
            flex: 1;
            margin-left: 250px !important;
            width: calc(100% - 250px) !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Top navbar - FIXED */
        .topbar {
            position: fixed !important;
            top: 0;
            left: 250px !important;
            right: 0;
            z-index: 1020;
            height: 70px;
            width: calc(100% - 250px) !important;
        }
        
        /* Main content area */
        #content {
            margin-top: 70px; /* Space for fixed topbar */
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        
        /* Remove the inline margin-top from container-fluid */
        .container-fluid {
            opacity: 0.97;
            padding-top: 0 !important;
            margin-top: 0 !important;
        }
        
        /* Footer adjustment */
        footer.bg-white.sticky-footer {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: relative !important;
                width: 100% !important;
                height: auto;
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
            
            .summary-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                                Pending Loans 
                                <span class="badge bg-warning ms-1"><?php echo $pending_totals['total_loans']; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                                Current Loans 
                                <span class="badge bg-success ms-1"><?php echo $approved_totals['total_loans']; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab" aria-controls="overdue" aria-selected="false">
                                Overdue Loans 
                                <span class="badge bg-danger ms-1"><?php echo $overdue_totals['total_loans']; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid" type="button" role="tab" aria-controls="paid" aria-selected="false">
                                Paid Loans 
                                <span class="badge bg-info ms-1"><?php echo $paid_totals['total_loans']; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">
                                Rejected Loans 
                                <span class="badge bg-secondary ms-1"><?php echo $rejected_totals['total_loans']; ?></span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="loanTabsContent">
                        
                        <!-- Pending Loans Tab -->
                        <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Pending Loan Applications</p>
                                </div>
                                <div class="card-body">
                                    <!-- Summary Cards for Pending Loans -->
                                    <div class="row summary-row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loan Amount</div>
                                                            <div class="card-value card-amount">K<?php echo number_format($pending_totals['total_amount'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-wave card-icon text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-success shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Projected Interest</div>
                                                            <div class="card-value card-interest">K<?php echo number_format($pending_totals['total_interest'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage card-icon text-success"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loans</div>
                                                            <div class="card-value card-count"><?php echo $pending_totals['total_loans']; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-file-invoice card-icon text-info"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm pending-search" 
                                                       aria-controls="pendingTable" 
                                                       placeholder="Search pending loans..." 
                                                       style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="pendingTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Number</th>
                                                    <th>Applicant Name</th>
                                                    <th>Occupation</th>
                                                    <th>Collateral</th>
                                                    <th>Duration</th>
                                                    <th>Loan Amount</th>
                                                    <th>Application Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php displayLoans($pending_loans, 'pendingTable'); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 align-self-center">
                                            <p class="dataTables_info" role="status" aria-live="polite">
                                                Showing <?php echo $pending_totals['total_loans']; ?> pending loan(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Loans Tab (Current Loans) -->
                        <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Current Loans (Active & On-time)</p>
                                </div>
                                <div class="card-body">
                                    
                                    
                                    <!-- Summary Cards for Current Loans -->
                                    <div class="row summary-row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loan Amount</div>
                                                            <div class="card-value card-amount">K<?php echo number_format($approved_totals['total_amount'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-wave card-icon text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-success shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Interest</div>
                                                            <div class="card-value card-interest">K<?php echo number_format($approved_totals['total_interest'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage card-icon text-success"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loans</div>
                                                            <div class="card-value card-count"><?php echo $approved_totals['total_loans']; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-file-invoice card-icon text-info"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm approved-search" 
                                                       aria-controls="approvedTable" 
                                                       placeholder="Search current loans..." 
                                                       style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="approvedTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Number</th>
                                                    <th>Applicant Name</th>
                                                    <th>Occupation</th>
                                                    <th>Collateral</th>
                                                    <th>Duration</th>
                                                    <th>Loan Amount</th>
                                                    <th>Start Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php displayLoans($approved_loans, 'approvedTable'); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 align-self-center">
                                            <p class="dataTables_info" role="status" aria-live="polite">
                                                Showing <?php echo $approved_totals['total_loans']; ?> current loan(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Loans Tab -->
                        <div class="tab-pane fade" id="overdue" role="tabpanel" aria-labelledby="overdue-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3 bg-danger text-white">
                                    <p class="m-0 fw-bold">Overdue Loans</p>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning mb-4">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Attention:</strong> These approved loans are overdue and incur K15 daily penalties.
                                    </div>
                                    
                                    <!-- Summary Cards for Overdue Loans (4 cards including penalties) -->
                                    <div class="row summary-row">
                                        <div class="col-md-3 mb-3">
                                            <div class="card summary-card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loan Amount</div>
                                                            <div class="card-value card-amount">K<?php echo number_format($overdue_totals['total_amount'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-wave card-icon text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card summary-card border-left-success shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Interest</div>
                                                            <div class="card-value card-interest">K<?php echo number_format($overdue_totals['total_interest'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage card-icon text-success"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card summary-card border-left-danger shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Penalties</div>
                                                            <div class="card-value card-penalty">K<?php echo number_format($overdue_totals['total_penalty'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-exclamation-triangle card-icon text-danger"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="card summary-card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loans</div>
                                                            <div class="card-value card-count"><?php echo $overdue_totals['total_loans']; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-file-invoice card-icon text-info"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm overdue-search" 
                                                       aria-controls="overdueTable" 
                                                       placeholder="Search overdue loans..." 
                                                       style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="overdueTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Number</th>
                                                    <th>Applicant Name</th>
                                                    <th>Occupation</th>
                                                    <th>Collateral</th>
                                                    <th>Duration</th>
                                                    <th>Loan Amount</th>
                                                    <th>Due Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if (empty($overdue_loans)) {
                                                    echo '<tr>
                                                            <td colspan="7" class="empty-state">
                                                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                                                <h4 class="text-success">No Overdue Loans!</h4>
                                                                <p class="text-muted">All approved loans are currently up to date with their payments.</p>
                                                            </td>
                                                          </tr>';
                                                } else {
                                                    foreach ($overdue_loans as $loan) {
                                                        $loan_number = htmlspecialchars($loan['loan_number'] ?? 'N/A');
                                                        $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
                                                        $occupation = htmlspecialchars($loan['occupation'] ?? 'Not specified');
                                                        $collateral = htmlspecialchars($loan['collateral_name'] ?? 'No collateral');
                                                        $duration = htmlspecialchars($loan['duration'] . ' week(s)');
                                                        $amount = 'K' . number_format($loan['amount'], 2);
                                                        $due_date = htmlspecialchars($loan['loan_end_date'] ?? 'N/A');
                                                        $loan_id = $loan['loan_id'];
                                                        $days_overdue = $loan['days_overdue'];
                                                        
                                                        $formatted_date = $due_date !== 'N/A' ? date('M j, Y', strtotime($due_date)) : 'N/A';
                                                        $penalty_amount = $days_overdue * 15;
                                                        
                                                        echo "
                                                        <tr class='clickable-row overdue-row' data-loan-number='$loan_number'>
                                                            <td>
                                                                <strong>$loan_number</strong>
                                                                <br><small class='text-danger'><strong>$days_overdue days overdue</strong></small>
                                                                <br><small class='text-muted'>Penalty: K$penalty_amount</small>
                                                            </td>
                                                            <td>$full_name</td>
                                                            <td>$occupation</td>
                                                            <td>$collateral</td>
                                                            <td>$duration</td>
                                                            <td>$amount</td>
                                                            <td>$formatted_date</td>
                                                        </tr>";
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 align-self-center">
                                            <p class="dataTables_info" role="status" aria-live="polite">
                                                Showing <?php echo $overdue_totals['total_loans']; ?> overdue loan(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paid Loans Tab -->
                        <div class="tab-pane fade" id="paid" role="tabpanel" aria-labelledby="paid-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3 bg-success text-white">
                                    <p class="m-0 fw-bold">Paid Loans</p>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success mb-4">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Completed:</strong> These approved loans have been fully paid and completed successfully.
                                    </div>
                                    
                                    <!-- Summary Cards for Paid Loans -->
                                    <div class="row summary-row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loan Amount</div>
                                                            <div class="card-value card-amount">K<?php echo number_format($paid_totals['total_amount'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-wave card-icon text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-success shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Interest Earned</div>
                                                            <div class="card-value card-interest">K<?php echo number_format($paid_totals['total_interest'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage card-icon text-success"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loans</div>
                                                            <div class="card-value card-count"><?php echo $paid_totals['total_loans']; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-file-invoice card-icon text-info"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm paid-search" 
                                                       aria-controls="paidTable" 
                                                       placeholder="Search paid loans..." 
                                                       style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="paidTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Number</th>
                                                    <th>Applicant Name</th>
                                                    <th>Occupation</th>
                                                    <th>Collateral</th>
                                                    <th>Duration</th>
                                                    <th>Loan Amount</th>
                                                    <th>Completion Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if (empty($paid_loans)) {
                                                    echo '<tr>
                                                            <td colspan="7" class="empty-state">
                                                                <i class="fas fa-money-bill-wave fa-4x text-secondary mb-3"></i>
                                                                <h4 class="text-secondary">No Paid Loans Yet!</h4>
                                                                <p class="text-muted">There are no loans that have been fully paid yet.</p>
                                                            </td>
                                                          </tr>';
                                                } else {
                                                    foreach ($paid_loans as $loan) {
                                                        $loan_number = htmlspecialchars($loan['loan_number'] ?? 'N/A');
                                                        $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
                                                        $occupation = htmlspecialchars($loan['occupation'] ?? 'Not specified');
                                                        $collateral = htmlspecialchars($loan['collateral_name'] ?? 'No collateral');
                                                        $duration = htmlspecialchars($loan['duration'] . ' week(s)');
                                                        $amount = 'K' . number_format($loan['amount'], 2);
                                                        $payment_date = htmlspecialchars($loan['payment_date'] ?? 'N/A');
                                                        $loan_id = $loan['loan_id'];
                                                        
                                                        $formatted_date = $payment_date !== 'N/A' ? date('M j, Y', strtotime($payment_date)) : 'N/A';
                                                        
                                                        echo "
                                                        <tr class='clickable-row paid-row' data-loan-number='$loan_number'>
                                                            <td><strong>$loan_number</strong></td>
                                                            <td>$full_name</td>
                                                            <td>$occupation</td>
                                                            <td>$collateral</td>
                                                            <td>$duration</td>
                                                            <td>$amount</td>
                                                            <td>$formatted_date</td>
                                                        </tr>";
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 align-self-center">
                                            <p class="dataTables_info" role="status" aria-live="polite">
                                                Showing <?php echo $paid_totals['total_loans']; ?> paid loan(s)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rejected Loans Tab -->
                        <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Rejected Loans</p>
                                </div>
                                <div class="card-body">
                                    <!-- Summary Cards for Rejected Loans -->
                                    <div class="row summary-row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loan Amount</div>
                                                            <div class="card-value card-amount">K<?php echo number_format($rejected_totals['total_amount'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-money-bill-wave card-icon text-primary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-secondary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Projected Interest</div>
                                                            <div class="card-value" style="color: #6c757d;">K<?php echo number_format($rejected_totals['total_interest'], 2); ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage card-icon text-secondary"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card summary-card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="card-title">Total Loans</div>
                                                            <div class="card-value card-count"><?php echo $rejected_totals['total_loans']; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-file-invoice card-icon text-info"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm rejected-search" 
                                                       aria-controls="rejectedTable" 
                                                       placeholder="Search rejected loans..." 
                                                       style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="rejectedTable">
                                            <thead>
                                                <tr>
                                                    <th>Loan Number</th>
                                                    <th>Applicant Name</th>
                                                    <th>Occupation</th>
                                                    <th>Collateral</th>
                                                    <th>Duration</th>
                                                    <th>Loan Amount</th>
                                                    <th>Rejection Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php displayLoans($rejected_loans, 'rejectedTable'); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6 align-self-center">
                                            <p class="dataTables_info" role="status" aria-live="polite">
                                                Showing <?php echo $rejected_totals['total_loans']; ?> rejected loan(s)
                                            </p>
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
                    <div class="text-center my-auto copyright"><span>Copyright © SEFA SATTY 2025</span></div>
                </div>
            </footer>
        </div>
        <a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Make rows clickable and redirect to loan details
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const loanNum = this.getAttribute('data-loan-number');
                    if (loanNum) {
                        window.location.href = 'loan_review.php?loan_number=' + loanNum;
                    }
                });
            });

            // Search functionality for all tables
            const searchInputs = {
                'pending-search': 'pendingTable',
                'approved-search': 'approvedTable',
                'overdue-search': 'overdueTable',
                'paid-search': 'paidTable',
                'rejected-search': 'rejectedTable'
            };

            for (const [searchClass, tableId] of Object.entries(searchInputs)) {
                const searchInput = document.querySelector('.' + searchClass);
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const rows = document.querySelectorAll('#' + tableId + ' .clickable-row');
                        
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            if (text.includes(searchTerm)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                }
            }

            // Clear search when switching tabs
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Clear all search inputs
                    Object.keys(searchInputs).forEach(searchClass => {
                        const input = document.querySelector('.' + searchClass);
                        if (input) input.value = '';
                    });
                    
                    // Show all rows again
                    Object.values(searchInputs).forEach(tableId => {
                        const table = document.getElementById(tableId);
                        if (table) {
                            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                            for (let i = 0; i < rows.length; i++) {
                                rows[i].style.display = '';
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>