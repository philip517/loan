<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get loan ID from URL parameter
$loan_id = $_GET['loan_id'] ?? null;

if (!$loan_id) {
    $_SESSION['error_message'] = "No loan ID provided.";
    header("Location: loan.php");
    exit;
}

// Debug: Check if loan_reviews table exists
try {
    $check_table = $pdo->query("SELECT 1 FROM loan_reviews LIMIT 1");
    error_log("loan_reviews table exists");
} catch (PDOException $e) {
    // Create the table if it doesn't exist
    try {
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `loan_reviews` (
            `review_id` int(11) NOT NULL AUTO_INCREMENT,
            `loan_id` int(11) NOT NULL,
            `loan_number` varchar(20) NOT NULL,
            `admin_id` int(11) NOT NULL,
            `decision` enum('pending','approved','rejected') NOT NULL,
            `admin_notes` text DEFAULT NULL,
            `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`review_id`),
            UNIQUE KEY `unique_loan_review` (`loan_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $pdo->exec($create_table_sql);
        error_log("loan_reviews table created successfully");
    } catch (PDOException $create_error) {
        error_log("Failed to create loan_reviews table: " . $create_error->getMessage());
    }
}

// Function to record loan review decision - ALWAYS CREATES NEW RECORDS
function recordLoanReview($pdo, $loan_id, $loan_number, $admin_id, $decision, $admin_notes = '') {
    try {
        // Check if all required parameters are provided
        if (empty($loan_id) || empty($loan_number) || empty($admin_id) || empty($decision)) {
            error_log("Missing parameters: loan_id=$loan_id, loan_number=$loan_number, admin_id=$admin_id, decision=$decision");
            return false;
        }
        
        // ALWAYS INSERT NEW REVIEW - don't check for existing ones
        $sql = "INSERT INTO loan_reviews (loan_id, loan_number, admin_id, decision, admin_notes) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$loan_id, $loan_number, $admin_id, $decision, $admin_notes]);
        
        if ($result) {
            $last_id = $pdo->lastInsertId();
            error_log("Successfully inserted NEW loan review with ID: " . $last_id . " for loan_id: " . $loan_id . " with decision: " . $decision);
            return $last_id;
        } else {
            error_log("Failed to execute INSERT statement for new review");
            return false;
        }
        
    } catch (PDOException $e) {
        // Check if it's a duplicate entry error (remove this after removing UNIQUE constraint)
        if ($e->getCode() == 23000) { // MySQL duplicate entry error code
            error_log("Duplicate review detected - removing UNIQUE constraint from loan_reviews table");
            // You might want to automatically alter the table here
        }
        error_log("Error recording loan review: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        return false;
    }
}

// Handle loan status update (approve/reject/pending)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Debug: Check session data
        $admin_id = $_SESSION['user_id'] ?? null;
        error_log("Admin ID from session: " . ($admin_id ?? 'NOT SET'));
        
        if (!$admin_id) {
            throw new Exception("Admin ID not found in session. Please log in again.");
        }

        // Fetch loan details including loan number and current status
        $loan_sql = "SELECT loan_number, status FROM loan WHERE loan_id = ?";
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([$loan_id]);
        $loan_data = $loan_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$loan_data) {
            throw new Exception("Loan not found");
        }
        
        $loan_number = $loan_data['loan_number'] ?? null;
        $current_status = $loan_data['status'] ?? 'pending';

        // Debug: Check current values
        error_log("Loan Number: " . ($loan_number ?? 'NULL') . ", Current Status: $current_status, New Status: $new_status");

        if (!$loan_number) {
            throw new Exception("Loan number not found for this loan");
        }

        // Update loan status only (without admin_notes)
        $update_sql = "UPDATE loan SET status = ? WHERE loan_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$new_status, $loan_id]);
        
        // Record in loan_reviews table if status changed (including pending)
        if (($new_status === 'approved' || $new_status === 'rejected' || $new_status === 'pending') && $current_status !== $new_status) {
            error_log("Attempting to record loan review for status: " . $new_status);
            
            $review_id = recordLoanReview($pdo, $loan_id, $loan_number, $admin_id, $new_status, $admin_notes);
            
            if ($review_id) {
                $_SESSION['success_message'] = "Loan status updated to " . $new_status . " successfully! Review recorded in audit trail.";
                error_log("Loan review recorded successfully with ID: " . $review_id);
            } else {
                $_SESSION['warning_message'] = "Loan status updated to " . $new_status . " but review recording failed. Please check error logs.";
                error_log("Loan review recording failed");
            }
        } else {
            $_SESSION['success_message'] = "Loan status updated to " . ucfirst($new_status) . " successfully!";
            error_log("No review recorded - status didn't change or was already set");
        }
        
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to update loan status. Please try again. Error: " . $e->getMessage();
        error_log("Error in status update: " . $e->getMessage());
    }
}

// Handle message submission to user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    try {
        $topic = $_POST['topic'];
        $message_text = $_POST['message_text'];
        
        $message_sql = "INSERT INTO message (status, topic, message_text, type, loan_id) 
                       VALUES ('sent', ?, ?, 'admin_to_user', ?)";
        $message_stmt = $pdo->prepare($message_sql);
        $message_stmt->execute([$topic, $message_text, $loan_id]);
        
        $_SESSION['success_message'] = "Message sent successfully!";
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to send message. Please try again.";
    }
}

// Fetch specific loan details with user and kin information
try {
    $loan_sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.NRC, u.email, u.occupation, u.address,
                        k.first_name as kin_first_name, k.last_name as kin_last_name, 
                        k.nrc_number as kin_nrc, k.phone as kin_phone
                 FROM loan l 
                 JOIN user_table u ON l.user_id = u.user_id 
                 LEFT JOIN kin k ON l.loan_id = k.loan_id 
                 WHERE l.loan_id = ?";
    $loan_stmt = $pdo->prepare($loan_sql);
    $loan_stmt->execute([$loan_id]);
    $loan = $loan_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        $_SESSION['error_message'] = "Loan not found.";
        header("Location: loan.php");
        exit;
    }
    
} catch (PDOException $e) {
    die("Error fetching loan details: " . $e->getMessage());
}

// Function to get review history for display
function getLoanReviewInfo($pdo, $loan_id) {
    try {
        $sql = "SELECT lr.*, a.username as admin_name 
                FROM loan_reviews lr 
                LEFT JOIN admin_users a ON lr.admin_id = a.admin_id 
                WHERE lr.loan_id = ? 
                ORDER BY lr.review_date DESC 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching review info: " . $e->getMessage());
        return false;
    }
}

// Function to get ALL past loan reviews for this loan
function getAllLoanReviews($pdo, $loan_id) {
    try {
        $sql = "SELECT lr.*, a.username as admin_name 
                FROM loan_reviews lr 
                LEFT JOIN user_table a ON lr.admin_id = a.user_id 
                WHERE lr.loan_id = ? 
                ORDER BY lr.review_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($reviews) . " reviews for loan_id: " . $loan_id);
        return $reviews;
        
    } catch (PDOException $e) {
        error_log("Error fetching all loan reviews: " . $e->getMessage());
        return [];
    }
}

// Function to display loan details
function displayLoanDetails($loan, $pdo) {
    $status_color = match($loan['status']) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending' => 'warning',
        default => 'secondary'
    };
    
    $status_bg = match($loan['status']) {
        'approved' => 'rgba(28,200,138,0.14)',
        'rejected' => 'rgba(231,76,60,0.14)',
        'pending' => 'rgba(246,194,62,0.13)',
        default => 'rgba(108,117,125,0.13)'
    };
    
    // Convert BLOB images to base64 for display
    $user_id_image_src = $loan['user_id_image'] ? 'data:image/jpeg;base64,' . base64_encode($loan['user_id_image']) : 'assets/img/dogs/image3.jpeg';
    $image1_src = $loan['image1'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image1']) : 'assets/img/dogs/image3.jpeg';
    $image2_src = $loan['image2'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image2']) : 'assets/img/dogs/image3.jpeg';
    
    // Calculate interest percentage and total repayment
    $interest_percentage = $loan['amount'] > 0 ? ($loan['interest'] / $loan['amount']) * 100 : 0;
    $total_repayment = $loan['amount'] + $loan['interest'];
    
    // Get loan number or display placeholder
    $loan_number = $loan['loan_number'] ?? 'N/A';
    
    // Get review information if available
    $review_info = getLoanReviewInfo($pdo, $loan['loan_id']);
    $review_section = '';
    
    if ($review_info) {
        $decision_badge = match($review_info['decision']) {
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
            default => '<span class="badge bg-secondary">' . $review_info['decision'] . '</span>'
        };
        
        $admin_name_display = $review_info['admin_name'] ?? 'Admin #' . $review_info['admin_id'];
        
        $review_section = '
        <div class="row">
            <div class="col-12">
                <div class="card border-info shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Latest Review Decision</h6>
                    </div>
                    <div class="card-body">';
        
        // Display admin notes if available
        if (!empty($review_info['admin_notes'])) {
            $review_section .= '
                        <div class="mb-3">
                            <strong>Admin Notes:</strong>
                            <div class="alert alert-light mt-2">
                                ' . nl2br(htmlspecialchars($review_info['admin_notes'])) . '
                            </div>
                        </div>';
        }
        
        $review_section .= '
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Decision:</strong><br>' . $decision_badge . '</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Reviewed By:</strong><br>' . $admin_name_display . '</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Review Date:</strong><br>' . date('F j, Y g:i A', strtotime($review_info['review_date'])) . '</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Loan Number:</strong><br>' . $review_info['loan_number'] . '</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // Get ALL past loan reviews for this loan
    $all_reviews = getAllLoanReviews($pdo, $loan['loan_id']);
    $past_reviews_section = '';
    
    // ALWAYS show the past reviews card, even if empty
    $past_reviews_section = '
    <div class="row">
        <div class="col-12">
            <div class="card border-warning shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Past Loan Reviews</h6>
                </div>
                <div class="card-body">';
    
    if (!empty($all_reviews)) {
        $past_reviews_section .= '
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Review Date</th>
                                    <th>Decision</th>
                                    <th>Reviewed By</th>
                                    <th>Admin Notes</th>
                                </tr>
                            </thead>
                            <tbody>';
        
        foreach ($all_reviews as $review) {
            $decision_badge = match($review['decision']) {
                'approved' => '<span class="badge bg-success">Approved</span>',
                'rejected' => '<span class="badge bg-danger">Rejected</span>',
                'pending' => '<span class="badge bg-warning">Pending</span>',
                default => '<span class="badge bg-secondary">' . $review['decision'] . '</span>'
            };
            
            $admin_name_display = $review['admin_name'] ?? 'Admin #' . $review['admin_id'];
            $notes_preview = !empty($review['admin_notes']) 
                ? '<span class="text-muted" title="' . htmlspecialchars($review['admin_notes']) . '">' 
                  . (strlen($review['admin_notes']) > 50 ? substr($review['admin_notes'], 0, 50) . '...' : $review['admin_notes']) 
                  . '</span>'
                : '<span class="text-muted">No notes</span>';
            
            $past_reviews_section .= '
                                <tr>
                                    <td>' . date('M j, Y g:i A', strtotime($review['review_date'])) . '</td>
                                    <td>' . $decision_badge . '</td>
                                    <td>' . $admin_name_display . '</td>
                                    <td>' . $notes_preview . '</td>
                                </tr>';
        }
        
        $past_reviews_section .= '
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing ' . count($all_reviews) . ' review(s) for this loan
                    </div>';
    } else {
        $past_reviews_section .= '
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No past reviews found for this loan.</p>
                        <small class="text-muted">Reviews will appear here once decisions are made.</small>
                    </div>';
    }
    
    $past_reviews_section .= '
                </div>
            </div>
        </div>
    </div>';
    
    return '
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-1">Loan Application Review</h4>
                    <div class="d-flex align-items-center">
                        <p class="text-muted mb-0 me-3">Application Date: ' . ($loan['loan_start_date'] ?? 'N/A') . '</p>
                        <span class="text-primary fw-bold fs-5">
                            <i class="fas fa-hashtag me-1"></i>Loan Number: ' . $loan_number . '
                        </span>
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-' . $status_color . ' fs-6">' . strtoupper($loan['status'] ?? 'PENDING') . '</span>
                </div>
            </div>
        </div>
        
        <div class="card-body" style="background: ' . $status_bg . ';">
            ' . $review_section . '
            ' . $past_reviews_section . '
            
            <div class="row">
                <!-- Client Information -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Client Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> ' . ($loan['first_name'] ?? 'N/A') . ' ' . ($loan['last_name'] ?? 'N/A') . '</p>
                            <p><strong>NRC:</strong> ' . ($loan['NRC'] ?? 'N/A') . '</p>
                            <p><strong>Phone:</strong> ' . ($loan['phone'] ?? 'N/A') . '</p>
                            <p><strong>Email:</strong> ' . ($loan['email'] ?? 'N/A') . '</p>
                            <p><strong>Occupation:</strong> ' . ($loan['occupation'] ?? 'N/A') . '</p>
                            <p><strong>Address:</strong> ' . ($loan['address'] ?? 'N/A') . '</p>
                        </div>
                    </div>
                </div>
                
                <!-- Next of Kin -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Next of Kin</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> ' . ($loan['kin_first_name'] ?? 'N/A') . ' ' . ($loan['kin_last_name'] ?? 'N/A') . '</p>
                            <p><strong>NRC:</strong> ' . ($loan['kin_nrc'] ?? 'N/A') . '</p>
                            <p><strong>Phone:</strong> ' . ($loan['kin_phone'] ?? 'N/A') . '</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loan Details -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Loan Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Loan Amount:</strong> K' . number_format($loan['amount'] ?? 0, 2) . '</p>
                            <p><strong>Duration:</strong> ' . ($loan['duration'] ?? 0) . ' week(s)</p>
                            <p><strong>Interest:</strong> K' . number_format($loan['interest'] ?? 0, 2) . ' (' . number_format($interest_percentage, 1) . '%)</p>
                            <p><strong>Total Repayment:</strong> K' . number_format($total_repayment, 2) . '</p>
                            <p><strong>Start Date:</strong> ' . ($loan['loan_start_date'] ?? 'N/A') . '</p>
                            <p><strong>End Date:</strong> ' . ($loan['loan_end_date'] ?? 'N/A') . '</p>
                        </div>
                    </div>
                </div>
                
                <!-- Collateral Information -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Collateral Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Collateral Item:</strong> ' . ($loan['collateral_name'] ?? 'N/A') . '</p>
                            <div class="row mt-3">
                                <div class="col-md-4 text-center">
                                    <h6>Collateral Image 1</h6>
                                    <img src="' . $image1_src . '" class="img-fluid clickable-image" 
                                         style="max-height: 150px; max-width: 100%; cursor: pointer;" 
                                         alt="Collateral Image 1" 
                                         onclick="openImageModal(\'' . $image1_src . '\', \'Collateral Image 1\')">
                                    <p class="small text-muted mt-2">Front View</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h6>Collateral Image 2</h6>
                                    <img src="' . $image2_src . '" class="img-fluid clickable-image" 
                                         style="max-height: 150px; max-width: 100%; cursor: pointer;" 
                                         alt="Collateral Image 2" 
                                         onclick="openImageModal(\'' . $image2_src . '\', \'Collateral Image 2\')">
                                    <p class="small text-muted mt-2">Alternate View</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h6>User ID Image</h6>
                                    <img src="' . $user_id_image_src . '" class="img-fluid clickable-image" 
                                         style="max-height: 150px; max-width: 100%; cursor: pointer;" 
                                         alt="User ID Image" 
                                         onclick="openImageModal(\'' . $user_id_image_src . '\', \'User ID Document\')">
                                    <p class="small text-muted mt-2">Identification</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Admin Actions</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Update Loan Status</strong></label>
                                    <select class="form-select" name="status" required>
                                        <option value="pending" ' . ($loan['status'] == 'pending' ? 'selected' : '') . '>Pending</option>
                                        <option value="approved" ' . ($loan['status'] == 'approved' ? 'selected' : '') . '>Approve</option>
                                        <option value="rejected" ' . ($loan['status'] == 'rejected' ? 'selected' : '') . '>Reject</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Admin Notes</strong></label>
                                    <textarea class="form-control" name="admin_notes" rows="2" 
                                              placeholder="Add notes about this loan decision...">' . ($review_info['admin_notes'] ?? '') . '</textarea>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Update
                                    </button>
                                </div>
                            </form>
                            
                            <div class="mt-3 text-end">
                                <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="modal" data-bs-target="#messageModal">
                                    <i class="fas fa-envelope me-2"></i>Send Message to Client
                                </button>
                                <a href="loan.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Loans
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Review - <?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?></title>
    <meta name="description" content="Loan Review Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .clickable-image {
            transition: transform 0.2s ease-in-out;
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }
        .clickable-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border-color: #007bff;
        }
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            width: auto;
            height: auto;
        }
        .image-modal-content {
            background: transparent;
            border: none;
        }
        .card-header {
            font-weight: 600;
        }
        .image-section {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .image-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .loan-number-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #2c3e50;
            background: rgba(52, 152, 219, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }
        .table-sm th,
        .table-sm td {
            padding: 0.5rem;
            font-size: 0.875rem;
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

                    <!-- Loan Details -->
                    <?php echo displayLoanDetails($loan, $pdo); ?>
                </div>
            </div>

            <!-- Message Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="messageModal">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <h4 class="modal-title">Send Message to Client</h4>
                                <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="send_message" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Topic/Subject</label>
                                    <select class="form-select" name="topic" required>
                                        <option value="" selected disabled>Select Topic</option>
                                        <option value="Loan Application Approved">Loan Application Approved</option>
                                        <option value="Loan Application Rejected">Loan Application Rejected</option>
                                        <option value="Additional Information Needed">Additional Information Needed</option>
                                        <option value="Collateral Verification">Collateral Verification</option>
                                        <option value="ID Verification Required">ID Verification Required</option>
                                        <option value="Payment Schedule">Payment Schedule</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message_text" rows="6" placeholder="Type your message to the client..." required></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        This message will be sent to the client: <strong><?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?></strong>
                                        <br>Loan Number: <strong><?php echo $loan['loan_number'] ?? 'N/A'; ?></strong>
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Image Preview Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="imageModal">
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content image-modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-white" id="imageModalTitle">Image Preview</h5>
                            <button class="btn-close btn-close-white" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img id="modalImage" src="" class="modal-image" alt="Enlarged Image">
                        </div>
                        <div class="modal-footer border-0 justify-content-center">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
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
        function openImageModal(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
    </script>
</body>
</html>