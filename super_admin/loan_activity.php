<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Fetch active admin and super_admin users with loan statistics
$admin_stats_sql = "
    SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.username, 
        u.email, 
        u.phone, 
        u.role, 
        u.status,
        COUNT(lr.review_id) as total_reviews,
        SUM(CASE WHEN lr.decision = 'approved' THEN 1 ELSE 0 END) as approved_loans,
        SUM(CASE WHEN lr.decision = 'rejected' THEN 1 ELSE 0 END) as rejected_loans
    FROM user_table u
    LEFT JOIN loan_reviews lr ON u.user_id = lr.admin_id
    WHERE u.role IN ('admin', 'super_admin') 
    GROUP BY u.user_id, u.first_name, u.last_name, u.username, u.email, u.phone, u.role, u.status
    ORDER BY u.first_name, u.last_name";

$admin_stats_stmt = $pdo->prepare($admin_stats_sql);
$admin_stats_stmt->execute();
$admin_stats = $admin_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate active and deactivated admins
$active_admins = array_filter($admin_stats, function($admin) {
    return $admin['status'] === 'active';
});

$deactivated_admins = array_filter($admin_stats, function($admin) {
    return $admin['status'] !== 'active';
});

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Admin Loan Statistics</title>
    <meta name="description" content="Admin loan approval statistics">
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
    .role-badge {
        font-size: 0.75em;
        padding: 0.25em 0.6em;
    }
    .status-badge {
        font-size: 0.7em;
        padding: 0.3em 0.6em;
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
    .stats-card {
        transition: transform 0.2s ease-in-out;
    }
    .stats-card:hover {
        transform: translateY(-2px);
    }
    .progress {
        height: 8px;
        margin-top: 5px;
    }
    .approval-rate {
        font-size: 0.85rem;
        font-weight: 600;
    }
    .currency-symbol {
        font-weight: bold;
        color: #2c3e50;
    }
    .number-text {
        font-weight: 600;
        color: #2c3e50;
    }
    
    /* ========== ADDED: FIXED NAVBAR & SIDEBAR CSS ========== */
    /* Fixed layout styles */
    body {
        overflow-x: hidden;
    }
    
    #wrapper {
        display: flex;
        min-height: 100vh;
    }
    
    /* Sidebar styles - FIXED */
    .sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px !important;
        overflow-y: auto;
        z-index: 1030;
    }
    
    /* Content wrapper - this wraps both topbar and main content */
    #content-wrapper {
        flex: 1;
        margin-left: 250px !important;
        width: calc(100% - 250px) !important;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Top navbar - FIXED */
    .topbar {
        position: fixed !important;
        top: 0;
        left: 250px !important;
        right: 0;
        z-index: 1020;
        height: 70px;
        width: calc(100% - 250px) !important;
    }
    
    /* Main content area */
    #content {
        margin-top: 70px; /* Space for fixed topbar */
        padding: 20px;
        flex: 1;
        overflow-y: auto;
        background: rgba(255,255,255,0.09);
    }
    
    /* Remove any inline margin-top from container-fluid */
    .container-fluid {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    
    /* Footer adjustment */
    footer.bg-white.sticky-footer {
        margin-left: 250px;
        width: calc(100% - 250px);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            position: relative !important;
            width: 100% !important;
            height: auto;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .topbar {
            position: relative !important;
            left: 0 !important;
            width: 100% !important;
        }
        
        #content {
            margin-top: 0;
            padding: 15px;
        }
        
        footer.bg-white.sticky-footer {
            margin-left: 0;
            width: 100%;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
        
        .table-responsive {
            font-size: 0.9rem;
        }
    }
</style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
                <div class="container-fluid" style="margin-top: 100px;">
                    <h3 class="text-dark mb-4">Admin Loan Statistics</h3>
                    
                    <!-- Overall Statistics Cards - Updated Styling -->
                    <div class="row mb-4">
                        <!-- Active Administrators Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-primary stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-primary mb-1 fw-bold text-xs">
                                                <span>Active Administrators</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span><?php echo count($active_admins); ?></span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>Currently active admins</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Loans Approved Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-success stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-success mb-1 fw-bold text-xs">
                                                <span>Total Approved</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span>
                                                    <?php 
                                                    $totalApproved = array_sum(array_column($admin_stats, 'approved_loans'));
                                                    echo $totalApproved;
                                                    ?>
                                                </span>
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

                        <!-- Total Loans Rejected Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-danger stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-danger mb-1 fw-bold text-xs">
                                                <span>Total Rejected</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span>
                                                    <?php 
                                                    $totalRejected = array_sum(array_column($admin_stats, 'rejected_loans'));
                                                    echo $totalRejected;
                                                    ?>
                                                </span>
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

                        <!-- Total Reviews Card -->
                        <div class="col-md-6 col-xl-3 mb-4">
                            <div class="card shadow border-left-warning stats-card">
                                <div class="card-body">
                                    <div class="row g-0 align-items-center">
                                        <div class="col me-2">
                                            <div class="text-uppercase text-warning mb-1 fw-bold text-xs">
                                                <span>Total Reviews</span>
                                            </div>
                                            <div class="text-dark mb-0 fw-bold h5">
                                                <span>
                                                    <?php 
                                                    $totalReviews = array_sum(array_column($admin_stats, 'total_reviews'));
                                                    echo $totalReviews;
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="text-xs text-muted">
                                                <span>All loan reviews</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                                Active Administrators 
                                <span class="badge bg-success ms-1"><?php echo count($active_admins); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="deactivated-tab" data-bs-toggle="tab" data-bs-target="#deactivated" type="button" role="tab" aria-controls="deactivated" aria-selected="false">
                                Deactivated Administrators 
                                <span class="badge bg-danger ms-1"><?php echo count($deactivated_admins); ?></span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="adminTabsContent">
                        
                        <!-- Active Administrators Tab -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Active Administrators - Loan Statistics</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm active-search" aria-controls="activeTable" placeholder="Search Active Administrators..." style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="activeTable">
                                            <thead>
                                                <tr>
                                                    <th>Administrator</th>
                                                    <th>Role</th>
                                                    <th>Total Reviews</th>
                                                    <th>Approved</th>
                                                    <th>Rejected</th>
                                                    <th>Approval Rate</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($active_admins)): ?>
                                                    <tr>
                                                        <td colspan="7" class="empty-state">
                                                            <i class="fas fa-users"></i>
                                                            <p class="text-muted mb-0">No active administrators found</p>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($active_admins as $admin): ?>
                                                        <?php
                                                        $total = $admin['total_reviews'];
                                                        $approved = $admin['approved_loans'];
                                                        $rejected = $admin['rejected_loans'];
                                                        
                                                        $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
                                                        ?>
                                                        <tr class="clickable-row" data-user-id="<?php echo $admin['user_id']; ?>">
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($admin['role'] === 'super_admin'): ?>
                                                                    <span class="badge bg-danger role-badge">Super Admin</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-primary role-badge">Admin</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="number-text"><?php echo $total; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="number-text text-success"><?php echo $approved; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="number-text text-danger"><?php echo $rejected; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($total > 0): ?>
                                                                    <div class="approval-rate text-success"><?php echo $approvalRate; ?>%</div>
                                                                    <div class="progress">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approvalRate; ?>%" aria-valuenow="<?php echo $approvalRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success status-badge">Active</span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deactivated Administrators Tab -->
                        <div class="tab-pane fade" id="deactivated" role="tabpanel" aria-labelledby="deactivated-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Deactivated Administrators - Loan Statistics</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm deactivated-search" aria-controls="deactivatedTable" placeholder="Search Deactivated Administrators..." style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="deactivatedTable">
                                            <thead>
                                                <tr>
                                                    <th>Administrator</th>
                                                    <th>Role</th>
                                                    <th>Total Reviews</th>
                                                    <th>Approved</th>
                                                    <th>Rejected</th>
                                                    <th>Approval Rate</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($deactivated_admins)): ?>
                                                    <tr>
                                                        <td colspan="7" class="empty-state">
                                                            <i class="fas fa-user-slash"></i>
                                                            <p class="text-muted mb-0">No deactivated administrators found</p>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($deactivated_admins as $admin): ?>
                                                        <?php
                                                        $total = $admin['total_reviews'];
                                                        $approved = $admin['approved_loans'];
                                                        $rejected = $admin['rejected_loans'];
                                                        
                                                        $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
                                                        ?>
                                                        <tr class="clickable-row" data-user-id="<?php echo $admin['user_id']; ?>">
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($admin['role'] === 'super_admin'): ?>
                                                                    <span class="badge bg-danger role-badge">Super Admin</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-primary role-badge">Admin</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="number-text"><?php echo $total; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="number-text text-success"><?php echo $approved; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="number-text text-danger"><?php echo $rejected; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($total > 0): ?>
                                                                    <div class="approval-rate text-success"><?php echo $approvalRate; ?>%</div>
                                                                    <div class="progress">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approvalRate; ?>%" aria-valuenow="<?php echo $approvalRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger status-badge">Deactivated</span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
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
    // Make rows clickable and redirect to user profile
    document.addEventListener('DOMContentLoaded', function() {
        const clickableRows = document.querySelectorAll('.clickable-row');
        
        clickableRows.forEach(row => {
            row.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (userId) {
                    window.location.href = `admin_loan_activity.php?admin_id=${userId}`;
                }
            });
        });

        // Search functionality for active administrators
        const activeSearch = document.querySelector('.active-search');
        if (activeSearch) {
            activeSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#activeTable .clickable-row');
                
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

        // Search functionality for deactivated administrators
        const deactivatedSearch = document.querySelector('.deactivated-search');
        if (deactivatedSearch) {
            deactivatedSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#deactivatedTable .clickable-row');
                
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
    <script>
        // Make rows clickable and redirect to user profile
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    if (userId) {
                        window.location.href = `admin_loan_activity.php?admin_id=${userId}`;
                    }
                });
            });

            // Search functionality for active administrators
            const activeSearch = document.querySelector('.active-search');
            if (activeSearch) {
                activeSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#activeTable .clickable-row');
                    
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

            // Search functionality for deactivated administrators
            const deactivatedSearch = document.querySelector('.deactivated-search');
            if (deactivatedSearch) {
                deactivatedSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#deactivatedTable .clickable-row');
                    
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