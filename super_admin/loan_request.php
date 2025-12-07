<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get admin details
$admin_id = $_SESSION['user_id'] ?? null;
$admin_name = $_SESSION['first_name'] ?? 'Admin';

// Get loan_number from URL if provided
$loan_number = $_GET['loan_number'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new request
    if (isset($_POST['create_request'])) {
        $loan_number = $_POST['loan_number'] ?? null;
        $request_message = $_POST['request_message'] ?? '';
        
        if ($loan_number && $request_message && $admin_id) {
            try {
                // Get loan_id from loan_number first
                $loan_sql = "SELECT loan_id FROM loan WHERE loan_number = ?";
                $loan_stmt = $pdo->prepare($loan_sql);
                $loan_stmt->execute([$loan_number]);
                $loan = $loan_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loan) {
                    $loan_id = $loan['loan_id'];
                    
                    $sql = "INSERT INTO loan_requests (loan_id, loan_number, admin_id, request_message, status) 
                            VALUES (?, ?, ?, ?, 'pending')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$loan_id, $loan_number, $admin_id, $request_message]);
                    
                    $_SESSION['success_message'] = "Request created successfully!";
                    header("Location: loan_request.php?loan_number=" . urlencode($loan_number));
                    exit;
                } else {
                    $_SESSION['error_message'] = "Loan not found with number: " . $loan_number;
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error creating request: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields";
        }
    }
    
    // Update request status
    if (isset($_POST['update_request_status'])) {
        $request_id = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if ($request_id && $status) {
            try {
                $sql = "UPDATE loan_requests SET status = ?, admin_notes = ? WHERE request_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$status, $admin_notes, $request_id]);
                
                $_SESSION['success_message'] = "Request status updated successfully!";
                header("Location: loan_request.php" . ($loan_number ? "?loan_number=" . urlencode($loan_number) : ""));
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating request: " . $e->getMessage();
            }
        }
    }
}

// Fetch requests
try {
    if ($loan_number) {
        // Fetch requests for specific loan using loan_number
        $sql = "SELECT lr.*, l.loan_number, u.first_name, u.last_name, 
                       a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM loan_requests lr
                JOIN loan l ON lr.loan_id = l.loan_id
                JOIN user_table u ON l.user_id = u.user_id
                JOIN user_table a ON lr.admin_id = a.user_id
                WHERE lr.loan_number = ?
                ORDER BY lr.date_of_request DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_number]);
    } else {
        // Fetch all requests
        $sql = "SELECT lr.*, l.loan_number, u.first_name, u.last_name, 
                       a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM loan_requests lr
                JOIN loan l ON lr.loan_id = l.loan_id
                JOIN user_table u ON l.user_id = u.user_id
                JOIN user_table a ON lr.admin_id = a.user_id
                ORDER BY lr.date_of_request DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    $error = "Error fetching requests: " . $e->getMessage();
}

// Fetch loan details if loan_number is provided
$loan_details = null;
if ($loan_number) {
    try {
        $loan_sql = "SELECT l.*, u.first_name, u.last_name 
                     FROM loan l 
                     JOIN user_table u ON l.user_id = u.user_id 
                     WHERE l.loan_number = ?";
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([$loan_number]);
        $loan_details = $loan_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store loan_id from fetched data for navigation
        $loan_id = $loan_details['loan_id'] ?? null;
    } catch (PDOException $e) {
        $loan_details = null;
    }
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Requests Management</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
   <style>
    .request-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .status-pending { border-left-color: #ffc107; }
    .status-approved { border-left-color: #28a745; }
    .status-rejected { border-left-color: #dc3545; }
    .status-completed { border-left-color: #17a2b8; }
    .badge-pending { background-color: #ffc107; color: #000; }
    .badge-approved { background-color: #28a745; }
    .badge-rejected { background-color: #dc3545; }
    .badge-completed { background-color: #17a2b8; }
    
    /* ========== ADDED/CHANGED CSS FOR FIXED LAYOUT ========== */
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
    
    /* Remove the inline margin-top from container-fluid */
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
    }
</style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
          <div id="content">
            <div class="container-fluid">
                  
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0">
                            <i class="fas fa-hand-holding-usd me-2"></i>
                            <?php echo $loan_number ? 'Loan Requests - #' . $loan_number : 'All Loan Requests'; ?>
                        </h3>
                        <div>
                            <?php if ($loan_number): ?>
                                <a href="loan_request.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-list me-1"></i>View All Requests
                                </a>
                                <a href="loan_review.php?loan_number=<?php echo urlencode($loan_number); ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Loan
                                </a>
                            <?php else: ?>
                                <a href="loan.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Loans
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Create New Request Form -->
                        <?php if ($loan_number && $loan_details): ?>
                       

                            <!-- Loan Information -->
                            <div class="card shadow mt-4">
                                <div class="card-header bg-info text-white py-3">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Loan Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Loan Number:</strong><br><?php echo $loan_details['loan_number']; ?></p>
                                    <p><strong>Client:</strong><br><?php echo $loan_details['first_name'] . ' ' . $loan_details['last_name']; ?></p>
                                    <p><strong>Amount:</strong><br>K<?php echo number_format($loan_details['amount'], 2); ?></p>
                                    <p><strong>Status:</strong><br>
                                        <span class="badge bg-<?php 
                                            echo $loan_details['status'] == 'approved' ? 'success' : 
                                                 ($loan_details['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($loan_details['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Requests List -->
                        <div class="<?php echo ($loan_number && $loan_details) ? 'col-lg-8' : 'col-12'; ?>">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">
                                        <i class="fas fa-list me-2"></i>
                                        <?php echo $loan_number ? 'Requests for This Loan' : 'All Loan Requests'; ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($requests); ?></span>
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?<?php echo $loan_number ? 'loan_number=' . urlencode($loan_number) : ''; ?>">All</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_number ? 'loan_number=' . urlencode($loan_number) . '&' : ''; ?>status=pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_number ? 'loan_number=' . urlencode($loan_number) . '&' : ''; ?>status=approved">Approved</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_number ? 'loan_number=' . urlencode($loan_number) . '&' : ''; ?>status=rejected">Rejected</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_number ? 'loan_number=' . urlencode($loan_number) . '&' : ''; ?>status=completed">Completed</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($requests)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No requests found</p>
                                            <small class="text-muted">
                                                <?php echo $loan_number ? 'Create the first request for this loan' : 'No loan requests have been created yet'; ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($requests as $request): 
                                                $status_class = 'status-' . $request['status'];
                                                $badge_class = 'badge-' . $request['status'];
                                            ?>
                                            <div class="col-12 mb-3">
                                                <div class="card request-card <?php echo $status_class; ?>">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <h6 class="mb-0 me-3">Request #<?php echo $request['request_id']; ?></h6>
                                                                    <span class="badge <?php echo $badge_class; ?>">
                                                                        <?php echo ucfirst($request['status']); ?>
                                                                    </span>
                                                                </div>
                                                                
                                                                <p class="mb-2"><strong>Loan:</strong> #<?php echo $request['loan_number']; ?> - <?php echo $request['first_name'] . ' ' . $request['last_name']; ?></p>
                                                                
                                                                <div class="mb-3">
                                                                    <strong>Request Message:</strong>
                                                                    <div class="alert alert-light mt-1">
                                                                        <?php echo nl2br(htmlspecialchars($request['request_message'])); ?>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if (!empty($request['admin_notes'])): ?>
                                                                    <div class="alert alert-info mt-2">
                                                                        <strong>Admin Notes:</strong><br>
                                                                        <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                
                                                                <small class="text-muted">
                                                                    <i class="fas fa-user me-1"></i>Created by: <?php echo $request['admin_first_name'] . ' ' . $request['admin_last_name']; ?>
                                                                    | <i class="fas fa-clock me-1"></i><?php echo date('M j, Y g:i A', strtotime($request['date_of_request'])); ?>
                                                                    <?php if ($request['date_updated'] != $request['date_of_request']): ?>
                                                                        | <i class="fas fa-sync me-1"></i>Updated: <?php echo date('M j, Y g:i A', strtotime($request['date_updated'])); ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="col-md-4">
                                                                <!-- Status Update Form -->
                                                                <form method="POST" class="mb-3">
                                                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                                    
                                                                    <div class="mb-2">
                                                                        <label class="form-label small"><strong>Update Status</strong></label>
                                                                        <select class="form-select form-select-sm" name="status" required>
                                                                            <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                            <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                            <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-2">
                                                                        <label class="form-label small"><strong>Admin Notes</strong></label>
                                                                        <textarea class="form-control form-control-sm" name="admin_notes" rows="3" 
                                                                                  placeholder="Add notes or comments..."><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                                                                    </div>
                                                                    
                                                                    <button type="submit" name="update_request_status" class="btn btn-primary btn-sm w-100">
                                                                        <i class="fas fa-save me-1"></i>Update Status
                                                                    </button>
                                                                </form>
                                                                
                                                                <div class="text-end">
                                                                    <a href="loan_review.php?loan_number=<?php echo urlencode($request['loan_number']); ?>" class="btn btn-outline-secondary btn-sm">
                                                                        <i class="fas fa-eye me-1"></i>View Loan
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
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
        </div>
        <a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>
</html>