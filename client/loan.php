<?php 
require 'auth_client.php';
require '../db_connect.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch loans with user information for this specific user
try {
    // Pending loans
    $pending_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.user_id = ? AND (l.status = 'pending' OR l.status IS NULL)
    ");
    $pending_stmt->execute([$user_id]);
    $pending_loans = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Approved loans
    $approved_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.user_id = ? AND l.status = 'approved'
    ");
    $approved_stmt->execute([$user_id]);
    $approved_loans = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rejected loans
    $rejected_stmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.occupation 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.user_id = ? AND l.status = 'rejected'
    ");
    $rejected_stmt->execute([$user_id]);
    $rejected_loans = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error fetching loans: " . $e->getMessage());
}

// Function to display loan data in table rows
function displayLoans($loans) {
    if (empty($loans)) {
        echo '<tr><td colspan="7" class="text-center py-4">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i><br>
                <span class="text-muted">No loans found</span>
              </td></tr>';
        return;
    }
    
    foreach ($loans as $loan) {
        $loan_number = htmlspecialchars($loan['loan_number'] ?? 'N/A');
        $full_name = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
        $occupation = htmlspecialchars($loan['occupation'] ?? 'Not specified');
        $collateral = htmlspecialchars($loan['collateral_name'] ?? 'No collateral');
        $duration = htmlspecialchars($loan['duration'] . ' week(s)');
        $amount = 'K' . number_format($loan['amount'], 2);
        $interest = 'K' . number_format($loan['interest'] ?? 0, 2);
        $total_amount = 'K' . number_format(($loan['amount'] + $loan['interest']), 2);
        $loan_id = $loan['loan_id'];
        
        echo "
        <tr style='cursor: pointer;' onclick='viewLoanDetails($loan_id)' class='clickable-row'>
            <td><strong class='loan-number'>$loan_number</strong></td>
            <td>$collateral</td>
            <td>$duration</td>
            <td>$amount</td>
            <td>$interest</td>
            <td>$total_amount</td>
            <td>
                <span class='badge bg-" . getStatusColor($loan['status']) . "'>" . 
                ucfirst($loan['status'] ?? 'pending') . "</span>
            </td>
        </tr>";
    }
}

function getStatusColor($status) {
    return match($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning',
        default => 'secondary'
    };
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
    <title>My Loans</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
  <style>
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
    .stats-card {
        border-left: 4px solid #007bff;
    }
    .stats-card.pending {
        border-left-color: #ffc107;
    }
    .stats-card.approved {
        border-left-color: #198754;
    }
    .stats-card.rejected {
        border-left-color: #dc3545;
    }
    
    /* Centered Tab Styling */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        display: flex;
        justify-content: center;
        flex-wrap: nowrap;
    }
    
    .nav-tabs .nav-item {
        flex: 1;
        text-align: center;
        max-width: 300px;
    }
    
    .nav-tabs .nav-link {
        color: #ffffff !important;
        font-weight: 600;
        border: 2px solid transparent;
        border-radius: 8px 8px 0 0;
        margin: 0 2px;
        transition: all 0.3s ease;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 20px;
    }
    
    /* Pending Tab - Yellow/Orange */
    .nav-tabs .nav-link[href="#tab-1"] {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
    }
    
    .nav-tabs .nav-link[href="#tab-1"]:hover,
    .nav-tabs .nav-link[href="#tab-1"].active {
        background-color: #e0a800 !important;
        border-color: #e0a800 !important;
        color: #ffffff !important;
    }
    
    /* Approved Tab - Green */
    .nav-tabs .nav-link[href="#tab-2"] {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    
    .nav-tabs .nav-link[href="#tab-2"]:hover,
    .nav-tabs .nav-link[href="#tab-2"].active {
        background-color: #157347 !important;
        border-color: #157347 !important;
        color: #ffffff !important;
    }
    
    /* Rejected Tab - Red */
    .nav-tabs .nav-link[href="#tab-3"] {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    
    .nav-tabs .nav-link[href="#tab-3"]:hover,
    .nav-tabs .nav-link[href="#tab-3"].active {
        background-color: #bb2d3b !important;
        border-color: #bb2d3b !important;
        color: #ffffff !important;
    }
    
    /* Active tab indicator */
    .nav-tabs .nav-link.active {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* Tab content area */
    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 8px 8px;
        padding: 20px;
        background: #ffffff;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .nav-tabs {
            flex-direction: column;
            align-items: center;
        }
        
        .nav-tabs .nav-item {
            width: 100%;
            max-width: 100%;
            margin-bottom: 5px;
        }
        
        .nav-tabs .nav-link {
            border-radius: 8px;
            margin: 2px 0;
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
                    
                    <div style="color:black;">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">
                                    <i class="fas fa-clock me-2"></i>Pending (<?php echo getLoanCount($pending_loans); ?>)
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">
                                    <i class="fas fa-check-circle me-2"></i>Approved (<?php echo getLoanCount($approved_loans); ?>)
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">
                                    <i class="fas fa-times-circle me-2"></i>Rejected (<?php echo getLoanCount($rejected_loans); ?>)
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
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                            <th>Interest</th>
                                                                            <th>Total Amount</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($pending_loans); ?>
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
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                            <th>Interest</th>
                                                                            <th>Total Amount</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($approved_loans); ?>
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
                                                        <div class="card-body">
                                                            <div class="table-responsive mt-2">
                                                                <table class="table my-0 table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Loan Number</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan Amount</th>
                                                                            <th>Interest</th>
                                                                            <th>Total Amount</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php displayLoans($rejected_loans); ?>
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
            window.location.href = 'loan_details.php?loan_id=' + loanId;
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
        });
    </script>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>
</html>