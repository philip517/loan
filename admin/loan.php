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
        echo '<tr><td colspan="6" class="text-center">No loans found</td></tr>';
        return;
    }
    
    foreach ($loans as $loan) {
        $loan_number = htmlspecialchars($loan['loan_number'] ?? 'N/A');
        $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
        $occupation = htmlspecialchars($loan['occupation'] ?? 'Not specified');
        $collateral = htmlspecialchars($loan['collateral_name'] ?? 'No collateral');
        $duration = htmlspecialchars($loan['duration'] . ' week(s)');
        $amount = 'K' . number_format($loan['amount'], 2);
        $loan_id = $loan['loan_id'];
        
        echo "
        <tr style='cursor: pointer;' onclick='viewLoanDetails($loan_id)'>
            <td><strong>$loan_number</strong></td>
            <td>$full_name</td>
            <td>$occupation</td>
            <td>$collateral</td>
            <td>$duration</td>
            <td>$amount</td>
        </tr>";
    }
}

// Count functions for pagination info
function getLoanCount($loans) {
    return count($loans);
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
 <style>
    body {
        position: relative;
        min-height: 100vh;
        padding-bottom: 60px; /* Height of footer */
    }

    .sticky-footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 60px;
        z-index: 100;
    }
    
    .clickable-row:hover {
        background-color: #f8f9fa !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .loan-number {
        font-weight: 600;
        color: #2c3e50;
        font-family: 'Courier New', monospace;
    }
    
    /* Centered Tab Styling */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-item {
        margin: 0 5px;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 600;
        border: 1px solid transparent;
        border-radius: 8px 8px 0 0;
        padding: 12px 20px;
        transition: all 0.3s ease;
        text-align: center;
        min-width: 150px;
    }
    
    .nav-tabs .nav-link.active {
        color: #ffffff;
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .nav-tabs .nav-link:not(.active) {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .nav-tabs .nav-link:not(.active):hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
    }
    
    /* Centered Search box styling */
    .search-container {
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        width: 100%;
    }
    
    .search-box {
        max-width: 400px;
        width: 100%;
        text-align: center;
    }
    
    .card-header .row {
        align-items: center;
    }
    
    /* For the pending tab where you removed the title */
    .card-header .row .col-md-6:only-child {
        width: 100%;
        text-align: center;
    }
    
    /* Responsive design for smaller screens */
    @media (max-width: 768px) {
        .nav-tabs {
            flex-direction: column;
            align-items: center;
        }
        
        .nav-tabs .nav-item {
            width: 100%;
            margin: 2px 0;
        }
        
        .nav-tabs .nav-link {
            border-radius: 8px;
            min-width: 200px;
        }
        
        .search-box {
            max-width: 100%;
        }
        
        .card-header .row {
            flex-direction: column;
            gap: 15px;
        }
        
        .card-header .row .col-md-6 {
            width: 100%;
            text-align: center !important;
        }
    }
</style>
  
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;filter: blur(0px);"> 
                <div class="container-fluid" style="margin-top: 100px;">
                    <div>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">
                                    Pending (<?php echo getLoanCount($pending_loans); ?>)
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">
                                    Approved (<?php echo getLoanCount($approved_loans); ?>)
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">
                                    Rejected (<?php echo getLoanCount($rejected_loans); ?>)
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Pending Loans Tab -->
                            <div class="tab-pane active" role="tabpanel" id="tab-1">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-header py-3">
                                                            <div class="row">
                                                                
                                                                <div class="col-md-6">
                                                                    <div class="text-md-end search-container">
                                                                        <input type="search" class="form-control form-control-sm search-box" 
                                                                               placeholder="Search pending loans..." 
                                                                               id="searchPending">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover" id="pendingTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Applicant Name</th>
                                                                            <th>Occupation</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($pending_loans, 'pendingTable'); ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Approved Loans Tab -->
                            <div class="tab-pane" role="tabpanel" id="tab-2">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-header py-3">
                                                            <div class="row">
                                                              
                                                                <div class="col-md-6">
                                                                    <div class="text-md-end search-container">
                                                                        <input type="search" class="form-control form-control-sm search-box" 
                                                                               placeholder="Search approved loans..." 
                                                                               id="searchApproved">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover" id="approvedTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Applicant Name</th>
                                                                            <th>Occupation</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($approved_loans, 'approvedTable'); ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rejected Loans Tab -->
                            <div class="tab-pane" role="tabpanel" id="tab-3">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-header py-3">
                                                            <div class="row">
                                                                
                                                                <div class="col-md-6">
                                                                    <div class="text-md-end search-container">
                                                                        <input type="search" class="form-control form-control-sm search-box" 
                                                                               placeholder="Search rejected loans..." 
                                                                               id="searchRejected">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover" id="rejectedTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Applicant Name</th>
                                                                            <th>Occupation</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($rejected_loans, 'rejectedTable'); ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
    
    <script>
        function viewLoanDetails(loanId) {
            // Redirect to loan details page with the loan ID
            window.location.href = 'loan_review.php?loan_id=' + loanId;
        }
        
        // Search functionality for all tables
        function initializeSearch(searchInputId, tableId) {
            const searchInput = document.getElementById(searchInputId);
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = document.getElementById(tableId);
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }
        }
        
        // Optional: Add keyboard navigation support
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr[onclick]');
            rows.forEach(row => {
                row.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const onclickAttr = this.getAttribute('onclick');
                        const match = onclickAttr.match(/viewLoanDetails\((\d+)\)/);
                        if (match) {
                            viewLoanDetails(match[1]);
                        }
                    }
                });
                
                // Make rows focusable for accessibility
                row.setAttribute('tabindex', '0');
                row.classList.add('clickable-row');
            });
            
            // Initialize search functionality for all tabs
            initializeSearch('searchPending', 'pendingTable');
            initializeSearch('searchApproved', 'approvedTable');
            initializeSearch('searchRejected', 'rejectedTable');
            
            // Clear search when switching tabs
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Clear all search inputs
                    document.getElementById('searchPending').value = '';
                    document.getElementById('searchApproved').value = '';
                    document.getElementById('searchRejected').value = '';
                    
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
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>
</html>