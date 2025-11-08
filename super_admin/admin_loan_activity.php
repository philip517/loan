<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Get admin_id from URL
$admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;

if ($admin_id === 0) {
    header('Location: admins.php');
    exit();
}

// Fetch admin details
$admin_sql = "SELECT user_id, first_name, last_name, username, email, role 
              FROM user_table 
              WHERE user_id = ? AND role IN ('admin', 'super_admin')";
$admin_stmt = $pdo->prepare($admin_sql);
$admin_stmt->execute([$admin_id]);
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: admins.php');
    exit();
}

// Fetch all loans reviewed by this admin from loan_reviews table (excluding pending status)
$loans_sql = "
    SELECT 
        lr.review_id,
        lr.loan_id,
        lr.loan_number,
        lr.decision,
        lr.admin_notes as review_notes,
        lr.review_date,
        l.loan_id,
        l.loan_number as actual_loan_number,
        l.amount,
        l.duration,
        l.interest,
        l.loan_start_date,
        l.loan_end_date,
        l.collateral_name,
        l.status as loan_status,
        l.admin_notes as loan_notes,
        u.first_name,
        u.last_name,
        u.email as customer_email,
        u.phone as customer_phone
    FROM loan_reviews lr
    INNER JOIN loan l ON lr.loan_id = l.loan_id
    INNER JOIN user_table u ON l.user_id = u.user_id
    WHERE lr.admin_id = ? AND l.status IN ('approved', 'rejected')
    ORDER BY lr.review_date DESC";

$loans_stmt = $pdo->prepare($loans_sql);
$loans_stmt->execute([$admin_id]);
$loans = $loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics for this admin (only approved and rejected loans)
$stats_sql = "
    SELECT 
        COUNT(*) as total_reviews,
        SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END) as approved_loans,
        SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END) as rejected_loans
    FROM loan_reviews 
    WHERE admin_id = ? AND decision IN ('approved', 'rejected')";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$admin_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$approval_rate = $stats['total_reviews'] > 0 ? 
    round(($stats['approved_loans'] / $stats['total_reviews']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Admin Loan Activity</title>
    <meta name="description" content="Loan activity for administrator">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
        .decision-badge {
            font-size: 0.75em;
            padding: 0.4em 0.8em;
        }
        .status-badge {
            font-size: 0.7em;
            padding: 0.3em 0.6em;
        }
        .stats-card {
            transition: transform 0.2s ease-in-out;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .admin-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .back-button {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-button:hover {
            color: #e0e0e0;
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
        .amount-badge {
            font-size: 0.9em;
            padding: 0.4em 0.8em;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .collateral-text {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .currency-symbol {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <div class="container-fluid" style="margin-top: 100px;">
                    
                    <!-- Admin Header -->
                    <div class="admin-header">
                        <a href="admins.php" class="back-button mb-3 d-inline-block">
                            <i class="fas fa-arrow-left me-2"></i>Back to Administrators
                        </a>
                        <h3 class="text-white mb-2">Loan Review Activity</h3>
                        <h4 class="text-white mb-0">
                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                            <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?> ms-2">
                                <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                            </span>
                        </h4>
                        <p class="text-white mb-0 opacity-75"><?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>

                    <!-- Statistics Cards - Updated Styling -->
                    <div class="row mb-4">
                        <!-- Total Reviews Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-primary stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary mb-1 fw-bold text-xs">
                                                <span>Completed Reviews</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo $stats['total_reviews']; ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Total reviews processed</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Loans Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-success stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                                                <span>Approved Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo $stats['approved_loans']; ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Loans approved</span>
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
                            <div class="card shadow border-left-danger stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-danger mb-1 fw-bold text-xs">
                                                <span>Rejected Loans</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo $stats['rejected_loans']; ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Loans rejected</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Rate Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-warning stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                                <span>Approval Rate</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo $approval_rate; ?>%</span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Success rate</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loans Table -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <p class="text-primary m-0 fw-bold">Completed Loan Reviews by <?php echo htmlspecialchars($admin['first_name']); ?></p>
                            <small class="text-muted">Showing only approved and rejected loans</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-lg-12">
                                    <div class="text-md-end dataTables_filter">
                                        <input type="search" class="form-control form-control-sm" id="loanSearch" placeholder="Search loans..." style="text-align: center;">
                                        <label class="form-label"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table my-0" id="loansTable">
                                    <thead>
                                        <tr>
                                            <th>Loan Details</th>
                                            <th>Customer</th>
                                            <th>Loan Amount</th>
                                            <th>Duration</th>
                                            <th>Interest</th>
                                            <th>Collateral</th>
                                            <th>Review Decision</th>
                                            <th>Loan Status</th>
                                            <th>Review Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($loans)): ?>
                                            <tr>
                                                <td colspan="9" class="empty-state">
                                                    <i class="fas fa-file-alt"></i>
                                                    <p class="text-muted mb-0">No completed loan reviews by this administrator</p>
                                                    <small class="text-muted">Only approved and rejected loans are shown</small>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($loans as $loan): ?>
                                                <tr class="clickable-row" data-loan-id="<?php echo $loan['loan_id']; ?>">
                                                    <td>
                                                        <strong>Loan #<?php echo htmlspecialchars($loan['actual_loan_number'] ?? $loan['loan_number']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php 
                                                            if ($loan['loan_start_date']) {
                                                                echo 'Start: ' . date('M j, Y', strtotime($loan['loan_start_date']));
                                                            } else {
                                                                echo 'Not started';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($loan['customer_email']); ?></small><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($loan['customer_phone'] ?? 'N/A'); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge amount-badge">$<?php echo number_format($loan['amount'], 2); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($loan['duration']); ?> weeks
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($loan['interest']); ?>%</strong>
                                                    </td>
                                                    <td>
                                                        <span class="collateral-text" title="<?php echo htmlspecialchars($loan['collateral_name'] ?? 'No collateral'); ?>">
                                                            <?php echo htmlspecialchars($loan['collateral_name'] ?? 'No collateral'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $decision_badge_class = '';
                                                        switch($loan['decision']) {
                                                            case 'approved':
                                                                $decision_badge_class = 'bg-success';
                                                                break;
                                                            case 'rejected':
                                                                $decision_badge_class = 'bg-danger';
                                                                break;
                                                            default:
                                                                $decision_badge_class = 'bg-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $decision_badge_class; ?> decision-badge">
                                                            <?php echo ucfirst($loan['decision']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $status_badge_class = '';
                                                        switch($loan['loan_status']) {
                                                            case 'approved':
                                                                $status_badge_class = 'bg-success';
                                                                break;
                                                            case 'rejected':
                                                                $status_badge_class = 'bg-danger';
                                                                break;
                                                            default:
                                                                $status_badge_class = 'bg-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_badge_class; ?> status-badge">
                                                            <?php echo ucfirst($loan['loan_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y g:i A', strtotime($loan['review_date'])); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6 align-self-center">
                                    <p class="dataTables_info" role="status" aria-live="polite">
                                        Showing <?php echo count($loans); ?> completed loan review(s)
                                    </p>
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
    <script src="assets/bootstrap/css/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Make loan rows clickable and redirect to loan review page
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const loanId = this.getAttribute('data-loan-id');
                    if (loanId) {
                        // Redirect to loan review page - adjust the URL as needed
                        window.location.href = `loan_review.php?loan_id=${loanId}`;
                    }
                });
            });

            // Search functionality
            const loanSearch = document.getElementById('loanSearch');
            if (loanSearch) {
                loanSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#loansTable .clickable-row');
                    
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
        });
    </script>
</body>
</html>