<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch loan statistics for the cards
$stats_query = "
    SELECT 
        COUNT(*) as total_loans,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_loans,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_loans,
        SUM(CASE WHEN status = 'pending' OR status IS NULL THEN 1 ELSE 0 END) as pending_loans
    FROM loan
";

$stats_stmt = $pdo->query($stats_query);
$loan_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all approved loans with overdue status and days calculations
$approved_loans_query = "
    SELECT 
        l.loan_id,
        l.loan_number,
        l.amount,
        l.interest,
        l.loan_start_date,
        l.loan_end_date,
        l.penalty_fee,
        l.status,
        u.first_name,
        u.last_name,
        u.phone,
        u.email,
        CASE 
            WHEN l.loan_end_date < CURDATE() THEN DATEDIFF(CURDATE(), l.loan_end_date)
            ELSE 0
        END as days_overdue,
        CASE 
            WHEN l.loan_end_date >= CURDATE() THEN DATEDIFF(l.loan_end_date, CURDATE())
            ELSE 0
        END as days_remaining,
        CASE 
            WHEN l.loan_end_date < CURDATE() THEN 'overdue'
            ELSE 'current'
        END as loan_status
    FROM loan l 
    JOIN user_table u ON l.user_id = u.user_id 
    WHERE l.status IN ('approved', 'overdue')
    ORDER BY l.loan_end_date ASC
";

$approved_loans_stmt = $pdo->query($approved_loans_query);
$all_approved_loans = $approved_loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate overdue and current loans
$overdue_loans = array_filter($all_approved_loans, function($loan) {
    return $loan['loan_status'] === 'overdue';
});

$current_loans = array_filter($all_approved_loans, function($loan) {
    return $loan['loan_status'] === 'current';
});

// Calculate totals
$total_overdue_amount = 0;
$total_overdue_interest = 0;
foreach ($overdue_loans as $loan) {
    $total_overdue_amount += $loan['amount'];
    $total_overdue_interest += $loan['interest'] + $loan['penalty_fee'];
}

$total_current_amount = 0;
$total_current_interest = 0;
foreach ($current_loans as $loan) {
    $total_current_amount += $loan['amount'];
    $total_current_interest += $loan['interest'];
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Approved Loans Overview - Admin</title>
    <meta name="description" content="Approved Loans Management">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .overdue-badge {
            background-color: #dc3545;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .current-badge {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .loan-card {
            transition: transform 0.2s ease-in-out;
        }
        .loan-card:hover {
            transform: translateY(-2px);
        }
        .quick-action-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            text-decoration: none;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-overdue {
            background-color: #dc3545;
        }
        .status-current {
            background-color: #28a745;
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.total { border-left-color: #6c757d; }
        .stat-card.approved { border-left-color: #28a745; }
        .stat-card.rejected { border-left-color: #dc3545; }
        .stat-card.pending { border-left-color: #ffc107; }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);">
                <div class="container-fluid" style="margin-top: 80px;">
                    <br><br>
                    <h3 class="text-dark mb-0"><strong>LOANS SUMMARY</strong></h3>
                    <br>

                    <!-- Row 1: Loan Statistics Cards -->
                    <div class="row mb-4">
                        <!-- Total Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow stat-card total loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-muted mb-1 fw-bold text-xs">
                                                <span>Total Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo $loan_stats['total_loans'] ?? 0; ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>All loans in system</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-invoice-dollar fa-2x text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow stat-card approved loan-card">
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
                                                <span>Active approved loans</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rejected Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow stat-card rejected loan-card">
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
                                                <span>Declined applications</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow stat-card pending loan-card">
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
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br><br>
                    <h3 class="text-dark mb-0"><strong>QUICK ACTIONS</strong></h3>
                    <br>

                    <!-- Row 2: Quick Action Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <a href="loan.php" class="card quick-action-card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title text-dark">All Loans</h6>
                                    <p class="text-muted small">Manage all loan applications</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="profile.php" class="card quick-action-card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-cog fa-3x text-info mb-3"></i>
                                    <h6 class="card-title text-dark">Profile</h6>
                                    <p class="text-muted small">Update your profile</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="message.php" class="card quick-action-card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-comments fa-3x text-success mb-3"></i>
                                    <h6 class="card-title text-dark">Messaging</h6>
                                    <p class="text-muted small">Communicate with clients</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="loan_request.php" class="card quick-action-card text-decoration-none">
                                <div class="card-body text-center">
                                    <i class="fas fa-plus-circle fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title text-dark">New Loan Request</h6>
                                    <p class="text-muted small">Create new loan application</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Header Section -->
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="text-dark mb-0"><strong>APPROVED LOANS OVERVIEW</strong></h3>
                            <p class="text-muted mb-0">Manage and monitor all approved loans</p>
                        </div>
                        <div>
                            <a href="loan.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to All Loans
                            </a>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <!-- Total Approved Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-primary loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary mb-1 fw-bold text-xs">
                                                <span>Total Approved Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo count($all_approved_loans); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>All active loans</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-danger loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-danger mb-1 fw-bold text-xs">
                                                <span>Overdue Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo count($overdue_loans); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Total: K<?php echo number_format($total_overdue_amount, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-success loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                                                <span>Current Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo count($current_loans); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Total: K<?php echo number_format($total_current_amount, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Interest Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-warning loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                                <span>Total Interest</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span>K<?php echo number_format($total_overdue_interest + $total_current_interest, 2); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>From all approved loans</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue Loans Section -->
                    <div class="row">
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Penalty Notice:</strong> Overdue loans incur a penalty of <strong>K15 per day</strong> added to the original interest amount.
                        </div>
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-danger text-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Overdue Loans (<?php echo count($overdue_loans); ?>)
                                    </h6>
                                    <span class="badge bg-light text-danger fs-6">
                                        Total Overdue: K<?php echo number_format($total_overdue_amount + $total_overdue_interest, 2); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($overdue_loans)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover" id="overdueTable">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>Days Overdue</th>
                                                        <th>Loan Number</th>
                                                        <th>Client Name</th>
                                                        <th>Loan Amount</th>
                                                        <th>Interest</th>
                                                        <th>Total Due</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($overdue_loans as $loan): ?>
                                                        <?php
                                                        $total_due = $loan['amount'] + $loan['interest'];
                                                        $days_overdue = $loan['days_overdue'];
                                                        ?>
                                                        <tr class="<?php echo $days_overdue > 30 ? 'table-danger' : 'table-warning'; ?>">
                                                            <td>
                                                                <span class="status-indicator status-overdue"></span>
                                                                <span class="overdue-badge">OVERDUE</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger fs-6">
                                                                    <?php echo $days_overdue; ?> days
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo $loan['loan_number'] ?? 'N/A'; ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?>
                                                                <br>
                                                                <small class="text-muted"><?php echo $loan['phone']; ?></small>
                                                            </td>
                                                            <td>
                                                                <strong>K<?php echo number_format($loan['amount'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                K<?php echo number_format($loan['interest'], 2); ?><br>
                                                                +K<strong><?php echo number_format($loan['penalty_fee'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <strong class="text-danger">K<?php echo number_format($total_due, 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_start_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_end_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="loan_review.php?loan_id=<?php echo $loan['loan_id']; ?>" 
                                                                       class="btn btn-primary" 
                                                                       title="View Loan Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="message.php?loan_id=<?php echo $loan['loan_id']; ?>" 
                                                                       class="btn btn-warning" 
                                                                       title="Send Reminder">
                                                                        <i class="fas fa-envelope"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-secondary">
                                                        <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                                        <td><strong>K<?php echo number_format($total_overdue_amount, 2); ?></strong></td>
                                                        <td><strong>K<?php echo number_format($total_overdue_interest, 2); ?></strong></td>
                                                        <td><strong class="text-danger">K<?php echo number_format($total_overdue_amount + $total_overdue_interest, 2); ?></strong></td>
                                                        <td colspan="3"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                            <h4 class="text-success">No Overdue Loans!</h4>
                                            <p class="text-muted">All approved loans are currently up to date with their payments.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Loans Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 fw-bold">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Current Loans (<?php echo count($current_loans); ?>)
                                    </h6>
                                    <span class="badge bg-light text-success fs-6">
                                        Total Current: K<?php echo number_format($total_current_amount, 2); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($current_loans)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover" id="currentTable">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>Days Remaining</th>
                                                        <th>Loan Number</th>
                                                        <th>Client Name</th>
                                                        <th>Loan Amount</th>
                                                        <th>Interest</th>
                                                        <th>Total Due</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($current_loans as $loan): ?>
                                                        <?php
                                                        $total_due = $loan['amount'] + $loan['interest'];
                                                        $days_remaining = -$loan['days_overdue']; // Negative days_overdue means days remaining
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <span class="status-indicator status-current"></span>
                                                                <span class="current-badge">CURRENT</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success fs-6">
                                                                    <?php echo $days_remaining; ?> days
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo $loan['loan_number'] ?? 'N/A'; ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?>
                                                                <br>
                                                                <small class="text-muted"><?php echo $loan['phone']; ?></small>
                                                            </td>
                                                            <td>
                                                                <strong>K<?php echo number_format($loan['amount'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                K<?php echo number_format($loan['interest'], 2); ?>
                                                            </td>
                                                            <td>
                                                                <strong class="text-success">K<?php echo number_format($total_due, 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_start_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_end_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="loan_review.php?loan_id=<?php echo $loan['loan_id']; ?>" 
                                                                       class="btn btn-primary" 
                                                                       title="View Loan Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-secondary">
                                                        <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                                        <td><strong>K<?php echo number_format($total_current_amount, 2); ?></strong></td>
                                                        <td><strong>K<?php echo number_format($total_current_interest, 2); ?></strong></td>
                                                        <td><strong class="text-success">K<?php echo number_format($total_current_amount + $total_current_interest, 2); ?></strong></td>
                                                        <td colspan="3"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-info-circle fa-4x text-info mb-3"></i>
                                            <h4 class="text-info">No Current Loans</h4>
                                            <p class="text-muted">There are no approved loans that are currently active.</p>
                                        </div>
                                    <?php endif; ?>
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
    </div>
    
    <a class="border rounded d-inline scroll-to-top" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Simple client-side sorting and filtering could be added here
        document.addEventListener('DOMContentLoaded', function() {
            // Add any interactive functionality here
            console.log('Approved Loans page loaded');
            
            // Example: Add row click functionality
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const loanId = this.querySelector('a.btn-primary')?.getAttribute('href')?.split('loan_id=')[1];
                    if (loanId) {
                        window.location.href = `loan_review.php?loan_id=${loanId}`;
                    }
                });
            });
        });
    </script>
</body>
</html>