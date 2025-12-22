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
        COALESCE(SUM(CASE WHEN decision = 'approved' THEN 1 ELSE 0 END), 0) as approved_loans,
        COALESCE(SUM(CASE WHEN decision = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_loans
    FROM loan_reviews 
    WHERE admin_id = ? AND decision IN ('approved', 'rejected')";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$admin_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Ensure all stats are set to 0 if null
$total_reviews = $stats['total_reviews'] ?? 0;
$approved_loans = $stats['approved_loans'] ?? 0;
$rejected_loans = $stats['rejected_loans'] ?? 0;

$approval_rate = $total_reviews > 0 ? 
    round(($approved_loans / $total_reviews) * 100, 1) : 0;
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
    /* Fix the sidebar position */
    #wrapper {
        display: flex;
        position: relative;
        min-height: 100vh;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        z-index: 1000;
        overflow-y: auto;
        transition: all 0.3s;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Fix the content wrapper position */
    #content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
        position: relative;
    }

    /* Fix the top navbar position */
    .topbar {
        position: fixed !important;
        top: 0;
        left: 250px;
        right: 0;
        z-index: 999;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: 70px;
    }

    /* Main content area with proper spacing */
    #content {
        margin-top: 70px; /* Height of top navbar */
        padding: 20px;
        min-height: calc(100vh - 70px);
        overflow-y: auto;
        background: rgba(255,255,255,0.09);
    }

    /* Admin Header Styling */
    .admin-header {
        background: #1A2980;
        background: -webkit-linear-gradient(to right, #26D0CE, #1A2980);
        background: linear-gradient(to left, #175e5cff, #1A2980);
        color: white;
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 2rem;
        animation: fadeIn 0.8s ease-in-out;
    }

    /* Statistics Cards */
    .stats-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border-radius: 10px;
        overflow: hidden;
        border: none;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }

    /* Card border accents */
    .border-left-primary { border-left: 4px solid #007bff !important; }
    .border-left-success { border-left: 4px solid #28a745 !important; }
    .border-left-danger { border-left: 4px solid #dc3545 !important; }
    .border-left-warning { border-left: 4px solid #ffc107 !important; }

    /* Table Styling */
    .card.shadow {
        border-radius: 10px;
        border: none;
        transition: transform 0.3s;
    }

    .card.shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
    }

    /* Clickable rows */
    .clickable-row {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .clickable-row:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
        transform: translateX(3px);
    }

    /* Badge Styling */
    .decision-badge, .status-badge, .amount-badge {
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .amount-badge {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        color: white !important;
        border: none;
    }

    /* Search Input */
    .dataTables_filter input {
        border-radius: 25px;
        padding: 8px 20px;
        border: 1px solid #dee2e6;
        transition: all 0.3s;
    }

    .dataTables_filter input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        transform: scale(1.02);
    }

    /* Empty State */
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #6c757d;
        animation: fadeIn 0.5s ease-in-out;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        animation: pulse 2s infinite;
    }

    /* Back Button */
    .back-button {
        color: white;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .back-button:hover {
        color: #e0e0e0;
        transform: translateX(-3px);
    }

    /* Footer */
    .sticky-footer {
        border-top: 1px solid #dee2e6;
        padding: 1rem 0;
        margin-top: auto;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            margin-left: -250px;
            z-index: 1050;
        }
        
        .sidebar.show {
            margin-left: 0;
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar {
            left: 0;
        }
        
        .admin-header {
            padding: 1.5rem;
            margin-top: 20px;
        }
        
        #content {
            padding: 15px;
        }
    }

    @media (max-width: 576px) {
        .admin-header {
            padding: 1rem;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .table-responsive {
            font-size: 0.9rem;
        }
    }

    /* Scrollbar styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    #content::-webkit-scrollbar {
        width: 8px;
    }

    #content::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #content::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 4px;
    }

    #content::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 0.5;
        }
        50% {
            opacity: 0.8;
        }
    }

    /* Scroll to top button */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        display: none;
        z-index: 1000;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scroll-to-top:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .scroll-to-top.show {
        display: flex;
        animation: fadeIn 0.3s ease-in-out;
    }

    /* Loading overlay for table */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        border-radius: 10px;
        display: none;
    }

    .loading-overlay.active {
        display: flex;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Table improvements */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 12px 15px;
        background-color: #f8f9fa;
    }

    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }

    .table tbody tr:first-child td {
        border-top: none;
    }

    /* Card header improvements */
    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        background-color: #f8f9fa;
        border-radius: 10px 10px 0 0 !important;
        padding: 1rem 1.25rem;
    }

    /* Form control styling */
    .form-control, .form-select {
        border-radius: 0.375rem;
        border: 1px solid #ced4da;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        transform: translateY(-1px);
    }

    /* Smooth scrolling for the entire page */
    html {
        scroll-behavior: smooth;
    }

    /* Ensure content doesn't get hidden behind fixed elements */
    @media (min-width: 768px) {
        #content {
            padding-top: 30px;
        }
    }

    /* Mobile menu backdrop */
    .sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        display: none;
    }

    .sidebar-backdrop.show {
        display: block;
    }
</style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
       <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
                    <!-- Admin Header -->
                    <div class="admin-header">
                        <a href="loan_activity.php" class="back-button mb-3 d-inline-block">
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
                                                <span><?php echo $total_reviews; ?></span>
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
                                                <span><?php echo $approved_loans; ?></span>
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
                                                <span><?php echo $rejected_loans; ?></span>
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
                                            <?php foreach ($loans as $loan): 
                                                $loan_number = $loan['actual_loan_number'] ?? $loan['loan_number'];
                                            ?>
                                                <tr class="clickable-row" data-loan-number="<?php echo htmlspecialchars($loan_number); ?>">
                                                    <td>
                                                        <strong>Loan #<?php echo htmlspecialchars($loan_number); ?></strong><br>
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
                                                        <span class="">K<?php echo number_format($loan['amount'], 2); ?></span>
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
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
    // Handle sidebar toggle button
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggleTop-1');
        const sidebar = document.querySelector('.sidebar');
        const contentWrapper = document.getElementById('content-wrapper');
        const topbar = document.querySelector('.topbar');
        const content = document.getElementById('content');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
                
                // Create or remove backdrop
                let backdrop = document.querySelector('.sidebar-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'sidebar-backdrop';
                    document.body.appendChild(backdrop);
                }
                
                if (sidebar.classList.contains('show')) {
                    backdrop.classList.add('show');
                    if (window.innerWidth <= 768) {
                        contentWrapper.style.marginLeft = '250px';
                        topbar.style.left = '250px';
                    }
                } else {
                    backdrop.classList.remove('show');
                    if (window.innerWidth <= 768) {
                        contentWrapper.style.marginLeft = '0';
                        topbar.style.left = '0';
                    }
                }
                
                // Close sidebar when clicking backdrop
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                    if (window.innerWidth <= 768) {
                        contentWrapper.style.marginLeft = '0';
                        topbar.style.left = '0';
                    }
                });
            });
        }

        // Adjust layout on window resize
        function adjustLayout() {
            if (window.innerWidth > 768) {
                contentWrapper.style.marginLeft = '250px';
                topbar.style.left = '250px';
                sidebar.classList.remove('show');
                const backdrop = document.querySelector('.sidebar-backdrop');
                if (backdrop) backdrop.classList.remove('show');
            } else {
                if (!sidebar.classList.contains('show')) {
                    contentWrapper.style.marginLeft = '0';
                    topbar.style.left = '0';
                }
            }
        }

        window.addEventListener('resize', adjustLayout);
        adjustLayout(); // Initial adjustment

        // Scroll to top functionality
        const scrollToTopBtn = document.querySelector('.scroll-to-top');
        if (scrollToTopBtn) {
            // Update button position based on scroll
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollToTopBtn.classList.add('show');
                } else {
                    scrollToTopBtn.classList.remove('show');
                }
            });

            // Scroll to top when clicked
            scrollToTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Enhanced search functionality with loading indicator
        const loanSearch = document.getElementById('loanSearch');
        if (loanSearch) {
            let searchTimeout;
            loanSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                
                // Show loading
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay active';
                loadingOverlay.innerHTML = '<div class="spinner"></div>';
                document.querySelector('.card-body').appendChild(loadingOverlay);
                
                searchTimeout = setTimeout(() => {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#loansTable .clickable-row');
                    let visibleCount = 0;
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (searchTerm === '' || text.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Update table info
                    const infoElement = document.querySelector('.dataTables_info');
                    if (infoElement) {
                        infoElement.textContent = `Showing ${visibleCount} completed loan review(s)`;
                    }
                    
                    // Remove loading
                    loadingOverlay.remove();
                    
                    // Scroll to top of table if not many results
                    if (visibleCount < 3) {
                        document.querySelector('.table-responsive').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 300);
            });
        }

        // Enhanced clickable rows with visual feedback
        const clickableRows = document.querySelectorAll('.clickable-row');
        clickableRows.forEach(row => {
            row.addEventListener('click', function() {
                const loanNumber = this.getAttribute('data-loan-number');
                if (loanNumber) {
                    // Add click animation
                    this.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 300);
                    
                    // Redirect after brief delay
                    setTimeout(() => {
                        const encodedLoanNumber = encodeURIComponent(loanNumber);
                        window.location.href = `loan_review.php?loan_number=${encodedLoanNumber}`;
                    }, 200);
                }
            });
            
            // Add hover effect with delay
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s ease';
            });
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-hide sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const backdrop = document.querySelector('.sidebar-backdrop');
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle?.contains(event.target) &&
                backdrop?.classList.contains('show')) {
                
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                contentWrapper.style.marginLeft = '0';
                topbar.style.left = '0';
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f' && loanSearch) {
                e.preventDefault();
                loanSearch.focus();
            }
            
            // Escape to clear search
            if (e.key === 'Escape' && loanSearch && document.activeElement === loanSearch) {
                loanSearch.value = '';
                loanSearch.dispatchEvent(new Event('input'));
            }
            
            // Alt + S to toggle sidebar (on mobile)
            if (e.altKey && e.key === 's' && window.innerWidth <= 768) {
                e.preventDefault();
                sidebarToggle?.click();
            }
        });

        // Add tooltips to collateral text
        document.querySelectorAll('.collateral-text').forEach(el => {
            const fullText = el.getAttribute('title');
            if (fullText && fullText.length > 20) {
                el.setAttribute('data-bs-toggle', 'tooltip');
                el.setAttribute('data-bs-placement', 'top');
                el.setAttribute('title', fullText);
                new bootstrap.Tooltip(el);
            }
        });

        // Smooth loading of content
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    });
</script>
    <script>
        // Make loan rows clickable and redirect to loan review page
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const loanNumber = this.getAttribute('data-loan-number');
                    if (loanNumber) {
                        const encodedLoanNumber = encodeURIComponent(loanNumber);
                        window.location.href = `loan_review.php?loan_number=${encodedLoanNumber}`;
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