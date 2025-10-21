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
    <title>Dashboard</title>
    <meta name="description" content="User Dashboard">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .card-header {
            font-weight: 600;
        }
        .stat-card {
            transition: transform 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }
        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }
        .progress {
            height: 8px;
        }
        .dashboard-section {
            margin-bottom: 2rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);">
                <div class="container-fluid" style="margin-top: 80px;">
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>DASHBOARD</strong></h3>
                        
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row dashboard-section">
                        <!-- Amount Loaned Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow-lg border-0 border-left-primary stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary mb-1 fw-bold">
                                                <i class="fas fa-hand-holding-usd me-2"></i>Amount Loaned
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h4"><?php echo $display_loaned; ?></div>
                                            <?php if (empty($approved_loans)): ?>
                                                <small class="text-muted">No approved loans</small>
                                            <?php else: ?>
                                                <small class="text-muted">Total from <?php echo count($approved_loans); ?> loan(s)</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-primary opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amount Due Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow-lg border-0 border-left-success stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success mb-1 fw-bold">
                                                <i class="fas fa-file-invoice-dollar me-2"></i>Amount Due
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h4"><?php echo $display_due; ?></div>
                                            <?php if (empty($approved_loans)): ?>
                                                <small class="text-muted">No payments due</small>
                                            <?php else: ?>
                                                <small class="text-muted">Principal + Interest</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-credit-card fa-2x text-success opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loan Progress Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow-lg border-0 border-left-info stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-info mb-1 fw-bold">
                                                <i class="fas fa-chart-line me-2"></i>Loan Progress
                                            </div>
                                            <div class="text-dark mb-2 fw-bold h4"><?php echo $display_progress; ?></div>
                                            <div class="progress mb-2">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     style="width: <?php echo $progress_width; ?>%" 
                                                     aria-valuenow="<?php echo $progress_width; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <?php if (!empty($approved_loans) && $nearest_due_date): ?>
                                                <small class="text-muted">Due: <?php echo date('M j, Y', strtotime($nearest_due_date)); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">No active loans</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tasks fa-2x text-info opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Days Remaining Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow-lg border-0 border-left-warning stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold">
                                                <i class="fas fa-clock me-2"></i>Days Remaining
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h4">
                                                <?php 
                                                if ($display_days === '-') {
                                                    echo '-';
                                                } else if ($days_remaining < 0) {
                                                    echo '<span class="text-danger">' . abs($days_remaining) . '</span>';
                                                } else {
                                                    echo $display_days;
                                                }
                                                ?>
                                            </div>
                                            <?php if (empty($approved_loans)): ?>
                                                <small class="text-muted">No due dates</small>
                                            <?php elseif ($days_remaining < 0): ?>
                                                <small class="text-danger fw-bold">Days Overdue!</small>
                                            <?php else: ?>
                                                <small class="text-muted">Until next payment</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-warning opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Notifications -->
                    <div class="row dashboard-section">
                        <!-- Quick Actions -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-lg border-0">
                                <div class="card-header bg-primary text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-6 mb-3">
                                            <a href="apply_loan.php" class="btn btn-outline-primary w-100 py-3">
                                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                                <h6>Apply for Loan</h6>
                                                <small class="text-muted">Start new application</small>
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="loan.php" class="btn btn-outline-success w-100 py-3">
                                                <i class="fas fa-list fa-2x mb-2"></i>
                                                <h6>My Loans</h6>
                                                <small class="text-muted">View loan status</small>
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="profile.php" class="btn btn-outline-info w-100 py-3">
                                                <i class="fas fa-user fa-2x mb-2"></i>
                                                <h6>My Profile</h6>
                                                <small class="text-muted">Update information</small>
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="messages.php" class="btn btn-outline-warning w-100 py-3">
                                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                                <h6>Messages</h6>
                                                <small class="text-muted">Contact support</small>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications & Contact -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-lg border-0">
                                <div class="card-header bg-success text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications & Support</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($approved_loans)): ?>
                                        <div class="alert alert-info">
                                            <div class="d-flex">
                                                <i class="fas fa-info-circle fa-2x me-3"></i>
                                                <div>
                                                    <h6 class="alert-heading">Welcome!</h6>
                                                    <p class="mb-0">You don't have any approved loans yet. <a href="apply_loan.php" class="alert-link">Apply for a loan</a> to get started.</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success">
                                            <div class="d-flex">
                                                <i class="fas fa-check-circle fa-2x me-3"></i>
                                                <div>
                                                    <h6 class="alert-heading">Active Loans</h6>
                                                    <p class="mb-0">You have <strong><?php echo count($approved_loans); ?> approved loan(s)</strong>. Keep track of your payments and due dates.</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($approved_loans) && $days_remaining < 0): ?>
                                        <div class="alert alert-danger">
                                            <div class="d-flex">
                                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                                <div>
                                                    <h6 class="alert-heading">Payment Overdue!</h6>
                                                    <p class="mb-0">Your payment is <strong><?php echo abs($days_remaining); ?> days overdue</strong>. Please contact support immediately.</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card bg-light mt-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fas fa-headset me-2"></i>Customer Support</h6>
                                            <p class="card-text">For any queries and information concerning your loans, please contact us:</p>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-phone me-2 text-primary"></i> <strong>Phone:</strong> +2601234567890</li>
                                                <li><i class="fas fa-envelope me-2 text-primary"></i> <strong>Email:</strong> support@sefasatty.com</li>
                                                <li><i class="fas fa-clock me-2 text-primary"></i> <strong>Hours:</strong> Mon-Fri, 8AM-5PM</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity (if any loans exist) -->
                    <?php if (!empty($approved_loans)): ?>
                    <div class="row dashboard-section">
                        <div class="col-12">
                            <div class="card shadow-lg border-0">
                                <div class="card-header bg-info text-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Loan Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Loan Amount</th>
                                                    <th>Duration</th>
                                                    <th>Interest</th>
                                                    <th>Total Due</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($approved_loans, 0, 5) as $loan): ?>
                                                <tr>
                                                    <td>K<?php echo number_format($loan['amount'], 2); ?></td>
                                                    <td><?php echo $loan['duration']; ?> weeks</td>
                                                    <td>K<?php echo number_format($loan['interest'], 2); ?></td>
                                                    <td><strong>K<?php echo number_format($loan['amount'] + $loan['interest'], 2); ?></strong></td>
                                                    <td><?php echo date('M j, Y', strtotime($loan['loan_start_date'])); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($loan['loan_end_date'])); ?></td>
                                                    <td><span class="badge bg-success">Approved</span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (count($approved_loans) > 5): ?>
                                        <div class="text-center mt-3">
                                            <a href="loan.php" class="btn btn-outline-primary">View All Loans</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
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
                        <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="../index.php">Yes</a>
                    </p>
                    <div class="text-center" style="display: inline-block;"></div>
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
</body>
</html>