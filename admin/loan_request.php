<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get admin details
$admin_id = $_SESSION['user_id'] ?? null;
$admin_name = $_SESSION['first_name'] ?? 'Admin';

if (!$admin_id) {
    $_SESSION['error_message'] = "Admin not authenticated. Please log in again.";
    header("Location: login.php");
    exit;
}

// Check if loan_requests table exists, create if not
try {
    $check_table = $pdo->query("SELECT 1 FROM loan_requests LIMIT 1");
} catch (PDOException $e) {
    // Create the table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `loan_requests` (
        `request_id` INT(11) NOT NULL AUTO_INCREMENT,
        `loan_id` INT(11) NOT NULL,
        `admin_id` INT(11) NOT NULL,
        `request_message` TEXT NOT NULL,
        `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        `admin_notes` TEXT NULL,
        `date_of_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `date_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`request_id`),
        FOREIGN KEY (`loan_id`) REFERENCES `loan`(`loan_id`) ON DELETE CASCADE,
        FOREIGN KEY (`admin_id`) REFERENCES `user_table`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_loan_id` (`loan_id`),
        INDEX `idx_admin_id` (`admin_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_date_request` (`date_of_request`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($create_table_sql);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new request
    if (isset($_POST['create_request'])) {
        $loan_id = $_POST['loan_id'] ?? null;
        $request_message = $_POST['request_message'] ?? '';
        
        if ($loan_id && $request_message && $admin_id) {
            try {
                $sql = "INSERT INTO loan_requests (loan_id, admin_id, request_message, status) 
                        VALUES (?, ?, ?, 'pending')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$loan_id, $admin_id, $request_message]);
                
                $_SESSION['success_message'] = "Request created successfully!";
                header("Location: loan_requests.php?loan_id=" . $loan_id);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error creating request: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields";
        }
    }
    
    // Update request status - ONLY ALLOW IF CURRENT ADMIN OWNS THE REQUEST
    if (isset($_POST['update_request_status'])) {
        $request_id = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if ($request_id && $status) {
            try {
                // First verify that the current admin owns this request
                $verify_sql = "SELECT request_id FROM loan_requests WHERE request_id = ? AND admin_id = ?";
                $verify_stmt = $pdo->prepare($verify_sql);
                $verify_stmt->execute([$request_id, $admin_id]);
                $owned_request = $verify_stmt->fetch();
                
                if ($owned_request) {
                    // Current admin owns this request, allow update
                    $sql = "UPDATE loan_requests SET status = ?, admin_notes = ? WHERE request_id = ? AND admin_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$status, $admin_notes, $request_id, $admin_id]);
                    
                    $_SESSION['success_message'] = "Request status updated successfully!";
                } else {
                    $_SESSION['error_message'] = "You can only update requests that you created.";
                }
                
                header("Location: loan_requests.php" . ($_GET['loan_id'] ? '?loan_id=' . $_GET['loan_id'] : ''));
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating request: " . $e->getMessage();
            }
        }
    }
}

// Get loan_id from URL if provided
$loan_id = $_GET['loan_id'] ?? null;

// Fetch requests - ONLY FOR CURRENT ADMIN
try {
    if ($loan_id) {
        // Fetch requests for specific loan created by current admin
        $sql = "SELECT lr.*, l.loan_number, u.first_name, u.last_name, 
                       a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM loan_requests lr
                JOIN loan l ON lr.loan_id = l.loan_id
                JOIN user_table u ON l.user_id = u.user_id
                JOIN user_table a ON lr.admin_id = a.user_id
                WHERE lr.loan_id = ? AND lr.admin_id = ?
                ORDER BY lr.date_of_request DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_id, $admin_id]);
    } else {
        // Fetch all requests created by current admin
        $sql = "SELECT lr.*, l.loan_number, u.first_name, u.last_name, 
                       a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM loan_requests lr
                JOIN loan l ON lr.loan_id = l.loan_id
                JOIN user_table u ON l.user_id = u.user_id
                JOIN user_table a ON lr.admin_id = a.user_id
                WHERE lr.admin_id = ?
                ORDER BY lr.date_of_request DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id]);
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    $error = "Error fetching requests: " . $e->getMessage();
}

// Fetch loan details if loan_id is provided
$loan_details = null;
if ($loan_id) {
    try {
        $loan_sql = "SELECT l.*, u.first_name, u.last_name 
                     FROM loan l 
                     JOIN user_table u ON l.user_id = u.user_id 
                     WHERE l.loan_id = ?";
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([$loan_id]);
        $loan_details = $loan_stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>My Loan Requests</title>
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
        .admin-badge {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);">
                <div class="container-fluid" style="margin-top: 80px;">
                    
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
                            <?php echo $loan_id ? 'My Requests - Loan #' . $loan_details['loan_number'] : 'My Loan Requests'; ?>
                            <span class="admin-badge"><?php echo $admin_name; ?></span>
                        </h3>
                        <div>
                            <?php if ($loan_id): ?>
                                <a href="loan_requests.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-list me-1"></i>View All My Requests
                                </a>
                                <a href="loan_review.php?loan_id=<?php echo $loan_id; ?>" class="btn btn-secondary">
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
                        <?php if ($loan_id && $loan_details): ?>
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-primary text-white py-3">
                                    <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Request</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Request Message</label>
                                            <textarea class="form-control" name="request_message" rows="6" 
                                                      placeholder="Describe your request in detail..." required></textarea>
                                            <div class="form-text">Clearly describe what information or action is needed.</div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i> 
                                                This request will be associated with Loan #<?php echo $loan_details['loan_number']; ?>
                                                for client <?php echo $loan_details['first_name'] . ' ' . $loan_details['last_name']; ?>
                                                <br><strong>Created by: <?php echo $admin_name; ?></strong>
                                            </small>
                                        </div>
                                        
                                        <button type="submit" name="create_request" class="btn btn-success w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Create Request
                                        </button>
                                    </form>
                                </div>
                            </div>

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
                        <div class="<?php echo ($loan_id && $loan_details) ? 'col-lg-8' : 'col-12'; ?>">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">
                                        <i class="fas fa-list me-2"></i>
                                        <?php echo $loan_id ? 'My Requests for This Loan' : 'All My Loan Requests'; ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($requests); ?></span>
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?<?php echo $loan_id ? 'loan_id=' . $loan_id : ''; ?>">All</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_id ? 'loan_id=' . $loan_id . '&' : ''; ?>status=pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_id ? 'loan_id=' . $loan_id . '&' : ''; ?>status=approved">Approved</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_id ? 'loan_id=' . $loan_id . '&' : ''; ?>status=rejected">Rejected</a></li>
                                            <li><a class="dropdown-item" href="?<?php echo $loan_id ? 'loan_id=' . $loan_id . '&' : ''; ?>status=completed">Completed</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($requests)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">No requests found</p>
                                            <small class="text-muted">
                                                <?php echo $loan_id ? 'Create your first request for this loan' : 'You haven\'t created any loan requests yet'; ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Showing only requests created by <strong>you (<?php echo $admin_name; ?>)</strong>
                                        </div>
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
                                                                    <span class="badge <?php echo $badge_class; ?> me-2">
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
                                                                
                                                                
                                                                <div class="text-end">
                                                                    <a href="loan_review.php?loan_id=<?php echo $request['loan_id']; ?>" class="btn btn-outline-secondary btn-sm">
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