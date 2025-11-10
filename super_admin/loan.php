<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

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
    
    // Rejected loans
    $rejected_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'rejected'
    ");
    $rejected_stmt->execute();
    $rejected_loans = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        <tr class='clickable-row' data-loan-id='$loan_id'>
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
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <div class="container-fluid" style="margin-top: 100px;">
                    <h3 class="text-dark mb-4">Loans Management</h3>
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="loanTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                                Pending Loans 
                                <span class="badge bg-warning ms-1"><?php echo count($pending_loans); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                                Current Loans 
                                <span class="badge bg-success ms-1"><?php echo count($approved_loans); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab" aria-controls="overdue" aria-selected="false">
                                Overdue Loans 
                                <span class="badge bg-danger ms-1"><?php echo count($overdue_loans); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid" type="button" role="tab" aria-controls="paid" aria-selected="false">
                                Paid Loans 
                                <span class="badge bg-info ms-1"><?php echo count($paid_loans); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">
                                Rejected Loans 
                                <span class="badge bg-secondary ms-1"><?php echo count($rejected_loans); ?></span>
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
                                                Showing <?php echo count($pending_loans); ?> pending loan(s)
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
                                    <div class="alert alert-success mb-4">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Active Loans:</strong> These approved loans are currently active and up-to-date with payments.
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
                                                Showing <?php echo count($approved_loans); ?> current loan(s)
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
                                                        <tr class='clickable-row overdue-row' data-loan-id='$loan_id'>
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
                                                Showing <?php echo count($overdue_loans); ?> overdue loan(s)
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
                                                        <tr class='clickable-row paid-row' data-loan-id='$loan_id'>
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
                                                Showing <?php echo count($paid_loans); ?> paid loan(s)
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
                                                Showing <?php echo count($rejected_loans); ?> rejected loan(s)
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
                    const loanId = this.getAttribute('data-loan-id');
                    if (loanId) {
                        window.location.href = 'loan_review.php?loan_id=' + loanId;
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