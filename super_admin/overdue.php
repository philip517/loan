<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Function to calculate overdue penalty
function calculateOverduePenalty($days_overdue, $loan_amount, $original_interest) {
    $penalty_per_day = 15; // K15 per day
    $penalty_amount = $days_overdue * $penalty_per_day;
    $total_interest = $original_interest + $penalty_amount;
    $total_amount_due = $loan_amount + $total_interest;
    
    return [
        'penalty_per_day' => $penalty_per_day,
        'penalty_amount' => $penalty_amount,
        'total_interest' => $total_interest,
        'total_amount_due' => $total_amount_due
    ];
}

// Fetch all approved loans with overdue status first
$approved_loans_query = "
    SELECT 
        l.loan_id,
        l.loan_number,
        l.amount,
        l.interest,
        l.penalty_fee,
        l.loan_start_date,
        l.loan_end_date,
        l.status,
        l.payment_date,
        u.first_name,
        u.last_name,
        u.phone,
        u.email,
        DATEDIFF(CURDATE(), l.loan_end_date) as days_overdue,
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

// Calculate and update penalty fees and status for each overdue loan before displaying
$total_overdue_amount = 0;
$total_overdue_interest = 0;
$total_overdue_penalty = 0;
$total_overdue_total_due = 0;

foreach ($overdue_loans as &$loan) {
    // Calculate penalty fee: days overdue × 15
    $penalty_amount = $loan['days_overdue'] * 15;
    
    // Update the penalty fee and status in the database for this loan
    // Only update status to 'overdue' if it's currently 'approved'
    $update_loan_query = "
        UPDATE loan 
        SET penalty_fee = ?,
            status = CASE 
                WHEN status = 'approved' THEN 'overdue' 
                ELSE status 
            END
        WHERE loan_id = ? AND status IN ('approved', 'overdue')
    ";
    $update_loan_stmt = $pdo->prepare($update_loan_query);
    $update_loan_stmt->execute([$penalty_amount, $loan['loan_id']]);
    
    // Update the loan array with the new penalty fee and status
    $loan['penalty_fee'] = $penalty_amount;
    $loan['status'] = 'overdue'; // Update status in the array for display
    
    // Calculate totals
    $total_interest = $loan['interest'] + $penalty_amount;
    $total_due = $loan['amount'] + $total_interest;
    
    $total_overdue_amount += $loan['amount'];
    $total_overdue_interest += $total_interest;
    $total_overdue_penalty += $penalty_amount;
    $total_overdue_total_due += $total_due;
}
unset($loan); // Break the reference

// Calculate totals for current loans
$total_current_amount = 0;
$total_current_interest = 0;
$total_current_total_due = 0;

foreach ($current_loans as $loan) {
    $total_current_amount += $loan['amount'];
    $total_current_interest += $loan['interest'];
    $total_current_total_due += $loan['amount'] + $loan['interest'];
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
        .currency-symbol {
            font-weight: bold;
            color: #2c3e50;
        }
        .penalty-amount {
            color: #dc3545;
            font-weight: bold;
        }
        .penalty-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .stored-penalty {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);">
                <div class="container-fluid" style="margin-top: 80px;">
                    
                    <!-- Header Section -->
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="text-dark mb-0"><strong>APPROVED & OVERDUE LOANS OVERVIEW</strong></h3>
                            <p class="text-muted mb-0">Manage and monitor all active loans</p>
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
                                                <span>Total Active Loans</span>
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
                                                <span>Total Due: <span class="currency-symbol">K</span><?php echo number_format($total_overdue_total_due, 2); ?></span>
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
                                                <span>Total Due: <span class="currency-symbol">K</span><?php echo number_format($total_current_total_due, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Penalties Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-warning loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                                <span>Total Penalties</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><span class="currency-symbol">K</span><?php echo number_format($total_overdue_penalty, 2); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>From overdue loans</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue Loans Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-danger text-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Overdue Loans (<?php echo count($overdue_loans); ?>)
                                    </h6>
                                    <span class="badge bg-light text-danger fs-6">
                                        Total Due: <span class="currency-symbol">K</span><?php echo number_format($total_overdue_total_due, 2); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($overdue_loans)): ?>
                                        
                                        <div class="alert alert-warning mb-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Penalty Notice:</strong> Overdue loans incur a penalty of <strong>K15 per day</strong> added to the original interest amount.
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover" id="overdueTable">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>Days Overdue</th>
                                                        <th>Loan Number</th>
                                                        <th>Client Name</th>
                                                        <th>Loan Amount</th>
                                                        <th>Original Interest</th>
                                                        <th>Penalty Amount</th>
                                                        <th>Total Interest</th>
                                                        <th>Total Due</th>
                                                        <th>End Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($overdue_loans as $loan): ?>
                                                        <?php
                                                        $penalty_amount = $loan['penalty_fee'];
                                                        $total_interest = $loan['interest'] + $penalty_amount;
                                                        $total_due = $loan['amount'] + $total_interest;
                                                        ?>
                                                        <tr class="<?php echo $loan['days_overdue'] > 30 ? 'table-danger' : 'table-warning'; ?>">
                                                            <td>
                                                                <span class="status-indicator status-overdue"></span>
                                                                <span class="overdue-badge">OVERDUE</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger fs-6">
                                                                    <?php echo $loan['days_overdue']; ?> days
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
                                                                <strong><span class="currency-symbol">K</span><?php echo number_format($loan['amount'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="currency-symbol">K</span><?php echo number_format($loan['interest'], 2); ?>
                                                            </td>
                                                            <td class="penalty-amount">
                                                                +<span class="currency-symbol">K</span><?php echo number_format($penalty_amount, 2); ?>
                                                                <br>
                                                                <small class="text-muted">(K15 × <?php echo $loan['days_overdue']; ?> days)</small>
                                                                <br>
                                                            </td>
                                                            <td>
                                                                <strong><span class="currency-symbol">K</span><?php echo number_format($total_interest, 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <strong class="text-danger"><span class="currency-symbol">K</span><?php echo number_format($total_due, 2); ?></strong>
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
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_overdue_amount, 2); ?></strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format(array_sum(array_column($overdue_loans, 'interest')), 2); ?></strong></td>
                                                        <td class="penalty-amount"><strong>+<span class="currency-symbol">K</span><?php echo number_format($total_overdue_penalty, 2); ?></strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_overdue_interest, 2); ?></strong></td>
                                                        <td><strong class="text-danger"><span class="currency-symbol">K</span><?php echo number_format($total_overdue_total_due, 2); ?></strong></td>
                                                        <td colspan="2"></td>
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
                                        Total Due: <span class="currency-symbol">K</span><?php echo number_format($total_current_total_due, 2); ?>
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
                                                                <strong><span class="currency-symbol">K</span><?php echo number_format($loan['amount'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="currency-symbol">K</span><?php echo number_format($loan['interest'], 2); ?>
                                                            </td>
                                                            <td>
                                                                <strong class="text-success"><span class="currency-symbol">K</span><?php echo number_format($total_due, 2); ?></strong>
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
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_current_amount, 2); ?></strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_current_interest, 2); ?></strong></td>
                                                        <td><strong class="text-success"><span class="currency-symbol">K</span><?php echo number_format($total_current_total_due, 2); ?></strong></td>
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
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Approved Loans page loaded');
            
            // Add row click functionality
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