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
        'total_amount_due' => $total_amount_due,
        'days_overdue' => $days_overdue
    ];
}

// Fetch all loans with progress = 'paid'
$paid_loans_query = "
    SELECT 
        l.loan_id,
        l.loan_number,
        l.amount,
        l.interest,
        l.loan_start_date,
        l.loan_end_date,
        l.status,
        l.progress,
        l.payment_date,
        u.first_name,
        u.last_name,
        u.phone,
        u.email,
        (l.amount + l.interest) as total_paid,
        DATEDIFF(l.payment_date, l.loan_end_date) as days_overdue,
        CASE 
            WHEN l.payment_date > l.loan_end_date THEN 'overdue'
            ELSE 'paid'
        END as payment_status
    FROM loan l 
    JOIN user_table u ON l.user_id = u.user_id 
    WHERE l.progress = 'paid'
    ORDER BY l.payment_date DESC
";

$paid_loans_stmt = $pdo->query($paid_loans_query);
$paid_loans = $paid_loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate paid loans totals with penalties
$total_paid_amount = 0;
$total_paid_interest = 0;
$total_penalty_fees = 0;
$total_paid_total = 0;
$total_original_interest = 0;

foreach ($paid_loans as &$loan) {
    $total_paid_amount += $loan['amount'];
    $total_original_interest += $loan['interest'];
    
    // Calculate penalty if loan was overdue when paid
    if ($loan['payment_status'] === 'overdue' && $loan['days_overdue'] > 0) {
        $penalty_calc = calculateOverduePenalty($loan['days_overdue'], $loan['amount'], $loan['interest']);
        $loan['penalty_amount'] = $penalty_calc['penalty_amount'];
        $loan['total_interest_with_penalty'] = $penalty_calc['total_interest'];
        $loan['total_paid_with_penalty'] = $penalty_calc['total_amount_due'];
        $loan['original_interest'] = $loan['interest'];
        $loan['interest'] = $penalty_calc['total_interest']; // Update interest to include penalty
        
        $total_penalty_fees += $penalty_calc['penalty_amount'];
        $total_paid_interest += $penalty_calc['total_interest'];
        $total_paid_total += $penalty_calc['total_amount_due'];
    } else {
        $loan['penalty_amount'] = 0;
        $loan['total_interest_with_penalty'] = $loan['interest'];
        $loan['total_paid_with_penalty'] = $loan['total_paid'];
        $loan['original_interest'] = $loan['interest'];
        
        $total_paid_interest += $loan['interest'];
        $total_paid_total += $loan['total_paid'];
    }
}
unset($loan); // Break the reference
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Paid Loans - Admin</title>
    <meta name="description" content="Paid Loans Management">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .paid-badge {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .overdue-paid-badge {
            background-color: #fd7e14;
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
        .status-paid {
            background-color: #28a745;
        }
        .status-overdue-paid {
            background-color: #fd7e14;
        }
        .currency-symbol {
            font-weight: bold;
            color: #2c3e50;
        }
        .success-amount {
            color: #28a745;
            font-weight: bold;
        }
        .penalty-amount {
            color: #dc3545;
            font-weight: bold;
        }
        .success-info {
            background-color: #d1edff;
            border-left: 4px solid #007bff;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .penalty-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
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
                            <h3 class="text-dark mb-0"><strong>PAID LOANS OVERVIEW</strong></h3>
                            <p class="text-muted mb-0">View all loans with progress status as PAID</p>
                        </div>
                        <div>
                            <a href="loan.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to All Loans
                            </a>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <!-- Total Paid Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-success loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                                                <span>Total Paid Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo count($paid_loans); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Loans with progress = paid</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Principal Amount Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-primary loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary mb-1 fw-bold text-xs">
                                                <span>Total Principal</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><span class="currency-symbol">K</span><?php echo number_format($total_paid_amount, 2); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Total loan amount paid</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Interest & Penalties Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-warning loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                                <span>Interest & Penalties</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><span class="currency-symbol">K</span><?php echo number_format($total_paid_interest, 2); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Interest: <span class="currency-symbol">K</span><?php echo number_format($total_original_interest, 2); ?></span><br>
                                                <span>Penalties: <span class="currency-symbol">K</span><?php echo number_format($total_penalty_fees, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Amount Paid Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-info loan-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-info mb-1 fw-bold text-xs">
                                                <span>Total Collected</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><span class="currency-symbol">K</span><?php echo number_format($total_paid_total, 2); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Principal + Interest + Penalties</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-usd fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Loans Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 fw-bold">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Paid Loans (<?php echo count($paid_loans); ?>)
                                    </h6>
                                    <span class="badge bg-light text-success fs-6">
                                        Total Collected: <span class="currency-symbol">K</span><?php echo number_format($total_paid_total, 2); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($paid_loans)): ?>
                                        <div class="alert alert-success mb-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Payment Information:</strong> 
                                            Showing all loans with progress status = <strong>PAID</strong>. 
                                            Loans paid after due date incur a penalty of <strong>K15 per day</strong> added to the original interest amount.
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover" id="paidTable">
                                                <thead>
                                                    <tr>
                                                        <th>Progress</th>
                                                        <th>Status</th>
                                                        <th>Loan Number</th>
                                                        <th>Client Name</th>
                                                        <th>Loan Amount</th>
                                                        <th>Original Interest</th>
                                                        <th>Penalty Amount</th>
                                                        <th>Total Interest</th>
                                                        <th>Total Paid</th>
                                                        <th>Start Date</th>
                                                        <th>Due Date</th>
                                                        <th>Payment Date</th>
                                                        <th>Days Overdue</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($paid_loans as $loan): ?>
                                                        <tr class="<?php echo $loan['payment_status'] === 'overdue' ? 'table-warning' : 'table-success'; ?>">
                                                            <td>
                                                                <span class="badge bg-success"><?php echo strtoupper($loan['progress']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="status-indicator <?php echo $loan['payment_status'] === 'overdue' ? 'status-overdue-paid' : 'status-paid'; ?>"></span>
                                                                <?php if ($loan['payment_status'] === 'overdue'): ?>
                                                                    <span class="overdue-paid-badge" title="Paid after due date">PAID (OVERDUE)</span>
                                                                <?php else: ?>
                                                                    <span class="paid-badge" title="Paid on time">PAID</span>
                                                                <?php endif; ?>
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
                                                                <span class="currency-symbol">K</span><?php echo number_format($loan['original_interest'], 2); ?>
                                                            </td>
                                                            <td class="penalty-amount">
                                                                <?php if ($loan['penalty_amount'] > 0): ?>
                                                                    +<span class="currency-symbol">K</span><?php echo number_format($loan['penalty_amount'], 2); ?>
                                                                    <br>
                                                                    <small class="text-muted">(K15 × <?php echo $loan['days_overdue']; ?> days)</small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><span class="currency-symbol">K</span><?php echo number_format($loan['total_interest_with_penalty'], 2); ?></strong>
                                                            </td>
                                                            <td class="success-amount">
                                                                <strong><span class="currency-symbol">K</span><?php echo number_format($loan['total_paid_with_penalty'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_start_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y', strtotime($loan['loan_end_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $loan['payment_date'] ? date('M j, Y g:i A', strtotime($loan['payment_date'])) : 'N/A'; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($loan['payment_status'] === 'overdue'): ?>
                                                                    <span class="badge bg-danger"><?php echo $loan['days_overdue']; ?> days</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">On Time</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="loan_review.php?loan_id=<?php echo $loan['loan_id']; ?>" 
                                                                       class="btn btn-primary" 
                                                                       title="View Loan Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="receipt.php?loan_id=<?php echo $loan['loan_id']; ?>" 
                                                                       class="btn btn-success" 
                                                                       title="Generate Receipt">
                                                                        <i class="fas fa-receipt"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-secondary">
                                                        <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_paid_amount, 2); ?></strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_original_interest, 2); ?></strong></td>
                                                        <td class="penalty-amount"><strong>+<span class="currency-symbol">K</span><?php echo number_format($total_penalty_fees, 2); ?></strong></td>
                                                        <td><strong><span class="currency-symbol">K</span><?php echo number_format($total_paid_interest, 2); ?></strong></td>
                                                        <td class="success-amount"><strong><span class="currency-symbol">K</span><?php echo number_format($total_paid_total, 2); ?></strong></td>
                                                        <td colspan="5"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-money-bill-wave fa-4x text-secondary mb-3"></i>
                                            <h4 class="text-secondary">No Paid Loans Found!</h4>
                                            <p class="text-muted">There are no loans with progress status as 'paid' in the system.</p>
                                            <a href="loan.php" class="btn btn-primary mt-2">
                                                <i class="fas fa-eye me-2"></i>View All Loans
                                            </a>
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
            console.log('Paid Loans page loaded');
            
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

            // Search functionality for paid loans table
            const searchInput = document.createElement('input');
            searchInput.type = 'search';
            searchInput.className = 'form-control form-control-sm';
            searchInput.placeholder = 'Search paid loans...';
            searchInput.style.textAlign = 'center';
            searchInput.style.marginBottom = '15px';
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#paidTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Add search input to the card header
            const cardHeader = document.querySelector('.card-header.py-3');
            if (cardHeader) {
                const searchContainer = document.createElement('div');
                searchContainer.className = 'mt-2';
                searchContainer.appendChild(searchInput);
                cardHeader.parentNode.insertBefore(searchContainer, cardHeader.nextSibling);
            }
        });
    </script>
</body>
</html>