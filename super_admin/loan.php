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
    
    // Approved loans
    $approved_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.status = 'approved'
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
                                Approved Loans 
                                <span class="badge bg-success ms-1"><?php echo count($approved_loans); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" aria-controls="rejected" aria-selected="false">
                                Rejected Loans 
                                <span class="badge bg-danger ms-1"><?php echo count($rejected_loans); ?></span>
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

                        <!-- Approved Loans Tab -->
                        <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Approved Loans</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm approved-search" 
                                                       aria-controls="approvedTable" 
                                                       placeholder="Search approved loans..." 
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
                                                    <th>Approval Date</th>
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
                                                Showing <?php echo count($approved_loans); ?> approved loan(s)
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

            // Search functionality for pending loans
            const pendingSearch = document.querySelector('.pending-search');
            if (pendingSearch) {
                pendingSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#pendingTable .clickable-row');
                    
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

            // Search functionality for approved loans
            const approvedSearch = document.querySelector('.approved-search');
            if (approvedSearch) {
                approvedSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#approvedTable .clickable-row');
                    
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

            // Search functionality for rejected loans
            const rejectedSearch = document.querySelector('.rejected-search');
            if (rejectedSearch) {
                rejectedSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#rejectedTable .clickable-row');
                    
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

            // Clear search when switching tabs
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Clear all search inputs
                    document.querySelector('.pending-search').value = '';
                    document.querySelector('.approved-search').value = '';
                    document.querySelector('.rejected-search').value = '';
                    
                    // Show all rows again
                    const tables = ['pendingTable', 'approvedTable', 'rejectedTable'];
                    tables.forEach(tableId => {
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