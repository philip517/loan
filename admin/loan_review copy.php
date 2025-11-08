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

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    try {
        $request_message = $_POST['request_message'] ?? '';
        $admin_id = $_SESSION['user_id'] ?? null;
        
        if (!$admin_id) {
            throw new Exception("Admin ID not found in session. Please log in again.");
        }
        
        if (empty($request_message)) {
            throw new Exception("Request message cannot be empty.");
        }

        $sql = "INSERT INTO loan_requests (loan_id, admin_id, request_message, status) 
                VALUES (?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_id, $admin_id, $request_message]);
        
        $_SESSION['success_message'] = "Request created successfully!";
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to create request: " . $e->getMessage();
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
require 'loan_details.php';
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
    <link rel="stylesheet" href="assets/css/loan_review.css">

    
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

            <!-- Request Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="requestModal">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header bg-warning text-dark">
                                <h4 class="modal-title"><i class="fas fa-hand-holding-usd me-2"></i>Create Loan Request</h4>
                                <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="create_request" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Request Message</label>
                                    <textarea class="form-control" name="request_message" rows="6" 
                                              placeholder="Describe your request in detail. Be specific about what information or action is needed..." required></textarea>
                                    <div class="form-text">
                                        Examples: "Need additional collateral documentation", "Require clarification on employment details", 
                                        "Request updated bank statements", etc.
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        This request will be associated with Loan #<strong><?php echo $loan['loan_number'] ?? 'N/A'; ?></strong>
                                        for client <strong><?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?></strong>
                                        <br>Request will be recorded in the system for tracking and follow-up.
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-warning" type="submit">
                                    <i class="fas fa-paper-plane me-2"></i>Create Request
                                </button>
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