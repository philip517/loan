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
        echo '<tr><td colspan="7" class="text-center">No loans found</td></tr>';
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
        <tr style='cursor: pointer;' onclick='viewLoanDetails($loan_id)'>
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
    <link rel="stylesheet" href="assets/css/loan.css">


  
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
                                                                            <th>Loan Date</th>
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
                                                                            <th>Loan Date</th>
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
                                                                            <th>Loan Date</th>
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
    
  
    <script src="assets/js/loan.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>
</html>