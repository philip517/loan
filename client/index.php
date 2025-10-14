<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch user's approved loans data
$loan_sql = "SELECT l.*, u.first_name, u.last_name 
             FROM loan l 
             LEFT JOIN user_table u ON l.user_id = u.user_id 
             WHERE l.user_id = ? AND l.status = 'approved' 
             ORDER BY l.loan_start_date DESC";
$loan_stmt = $pdo->prepare($loan_sql);
$loan_stmt->execute([$user_id]);
$approved_loans = $loan_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_loaned = 0;
$total_due = 0;
$nearest_due_date = null;
$days_remaining = null;
$progress_percentage = 0;

if (!empty($approved_loans)) {
    foreach ($approved_loans as $loan) {
        $total_loaned += $loan['amount'];
        
        // Calculate total due (principal + interest)
        $loan_total = $loan['amount'] + $loan['interest'];
        $total_due += $loan_total;
        
        // Find the nearest due date
        if ($loan['loan_end_date']) {
            if ($nearest_due_date === null || $loan['loan_end_date'] < $nearest_due_date) {
                $nearest_due_date = $loan['loan_end_date'];
            }
        }
    }
    
    // Calculate days remaining for the nearest due date
    if ($nearest_due_date) {
        $today = new DateTime();
        $due_date = new DateTime($nearest_due_date);
        $interval = $today->diff($due_date);
        $days_remaining = $interval->days;
        
        // If due date is in the past, show negative days
        if ($today > $due_date) {
            $days_remaining = -$days_remaining;
        }
        
        // Calculate progress percentage (example: based on time passed)
        $start_date = new DateTime($approved_loans[0]['loan_start_date'] ?? date('Y-m-d'));
        $total_duration = $start_date->diff($due_date)->days;
        $days_passed = $start_date->diff($today)->days;
        
        if ($total_duration > 0) {
            $progress_percentage = min(100, max(0, ($days_passed / $total_duration) * 100));
        }
    }
}

// Format data for display or use dashes if no approved loans
$display_loaned = empty($approved_loans) ? '-' : 'K' . number_format($total_loaned, 2);
$display_due = empty($approved_loans) ? '-' : 'K' . number_format($total_due, 2);
$display_progress = empty($approved_loans) ? '-' : number_format($progress_percentage, 0) . '%';
$display_days = empty($approved_loans) ? '-' : $days_remaining;

// For progress bar width
$progress_width = empty($approved_loans) ? 0 : $progress_percentage;
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Home</title>
    <meta name="description" content="User Home Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="container-fluid">
            <div class="row" style="margin-top: 100PX;">
                <!-- Amount Loaned Card -->
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card shadow py-2 border-left-primary">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-uppercase text-primary mb-1 fw-bold text-xs"><span>AMOUNT LOANED</span></div>
                                    <div class="text-dark mb-0 fw-bold h5"><span><?php echo $display_loaned; ?></span></div>
                                    <?php if (empty($approved_loans)): ?>
                                        <small class="text-muted">No approved loans</small>
                                    <?php else: ?>
                                        <small class="text-muted">Total from <?php echo count($approved_loans); ?> loan(s)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto"><i class="fas fa-calendar fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Amount Due Card -->
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card shadow py-2 border-left-success">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-uppercase text-success mb-1 fw-bold text-xs"><span>AMOUNT DUE</span></div>
                                    <div class="text-dark mb-0 fw-bold h5"><span><?php echo $display_due; ?></span></div>
                                    <?php if (empty($approved_loans)): ?>
                                        <small class="text-muted">No payments due</small>
                                    <?php else: ?>
                                        <small class="text-muted">Principal + Interest</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Due Date Progress Card -->
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card shadow py-2 border-left-info">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-uppercase text-info mb-1 fw-bold text-xs"><span>LOAN PROGRESS</span></div>
                                    <div class="row g-0 align-items-center">
                                        <div class="col-auto">
                                            <div class="text-dark me-3 mb-0 fw-bold h5"><span><?php echo $display_progress; ?></span></div>
                                        </div>
                                        <div class="col">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-info" aria-valuenow="<?php echo $progress_width; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $progress_width; ?>%;">
                                                    <span class="visually-hidden"><?php echo $display_progress; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($approved_loans) && $nearest_due_date): ?>
                                        <small class="text-muted">Due: <?php echo date('M j, Y', strtotime($nearest_due_date)); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">No active loans</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Days Remaining Card -->
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card shadow py-2 border-left-warning">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-uppercase text-warning mb-1 fw-bold text-xs"><span>DAYS REMAINING</span></div>
                                    <div class="text-dark mb-0 fw-bold h5">
                                        <span>
                                            <?php 
                                            if ($display_days === '-') {
                                                echo '-';
                                            } else if ($days_remaining < 0) {
                                                echo '<span class="text-danger">' . abs($days_remaining) . ' days overdue</span>';
                                            } else {
                                                echo $display_days . ' days';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <?php if (empty($approved_loans)): ?>
                                        <small class="text-muted">No due dates</small>
                                    <?php elseif ($days_remaining < 0): ?>
                                        <small class="text-danger">Payment overdue!</small>
                                    <?php else: ?>
                                        <small class="text-muted">Until next payment</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="card shadow py-2 border-left-info">
                        <div class="card-body" style="width: 100%;">
                            <h4 class="ps-2 pt-2 pb-2 card-title" style="background: var(--bs-success-bg-subtle);width: 100%;">NOTIFICATION</h4>
                            <h6 class="text-muted mt-4 card-subtitle mb-2">Administrator</h6>
                            <p class="card-text">For any queries and information on any areas concerning the loans please feel free to contact us on<br><br>Cell: +2601234567890<br>Email: email@example.com</p>
                            
                            <?php if (empty($approved_loans)): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You don't have any approved loans. <a href="apply_loan.php" class="alert-link">Apply for a loan</a> to see your statistics here.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    You have <strong><?php echo count($approved_loans); ?> approved loan(s)</strong>. Keep track of your payments and due dates.
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
            <div class="text-center my-auto copyright"><span>Copyright © SEFA SATTY 2025</span></div>
        </div>
    </footer>
    
    <a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    
    <div class="modal fade text-center" role="dialog" tabindex="-1" id="modal-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"></div>
                <div class="modal-body">
                    <p>Leaving Already ?</p>
                </div>
                <div class="modal-footer text-end" style="text-align: justify;">
                    <p style="text-align: left;">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal" style="text-align: center;">No</button>
                        &nbsp;&nbsp;
                        <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="login.php">Yes</a>
                    </p>
                    <div class="text-center" style="display: inline-block;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>
</html>