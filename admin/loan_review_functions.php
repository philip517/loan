<?php

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

// Handle loan request creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    try {
        $request_message = $_POST['request_message'];
        $admin_id = $_SESSION['user_id'] ?? null;
        
        if (!$admin_id) {
            throw new Exception("Admin ID not found in session. Please log in again.");
        }

        // Insert into loan_requests table
        $request_sql = "INSERT INTO loan_requests (loan_id, admin_id, request_message, status) VALUES (?, ?, ?, 'pending')";
        $request_stmt = $pdo->prepare($request_sql);
        $request_stmt->execute([$loan_id, $admin_id, $request_message]);
        
        $_SESSION['success_message'] = "Loan request created successfully! The client will be notified.";
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to create loan request. Please try again. Error: " . $e->getMessage();
        error_log("Error in loan request creation: " . $e->getMessage());
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

// Handle loan status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        $admin_id = $_SESSION['user_id'] ?? null;
        
        if (!$admin_id) {
            throw new Exception("Admin ID not found in session. Please log in again.");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. First, fetch the current loan data to get duration and other details
        $current_loan_sql = "SELECT duration, loan_number FROM loan WHERE loan_id = ?";
        $current_loan_stmt = $pdo->prepare($current_loan_sql);
        $current_loan_stmt->execute([$loan_id]);
        $current_loan = $current_loan_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_loan) {
            throw new Exception("Loan not found.");
        }
        
        // 2. Update the loan status in the loans table
        $update_sql = "UPDATE loan SET status = ?";
        $update_params = [$new_status];
        
        // 3. If approved, set the loan start and end dates AND update progress to 'current'
        if ($new_status === 'approved') {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+{$current_loan['duration']} weeks"));
            
            $update_sql .= ", loan_start_date = ?, loan_end_date = ?, progress = 'current'";
            array_push($update_params, $start_date, $end_date);
            
            error_log("Setting loan dates - Start: $start_date, End: $end_date, Progress: current, Duration: {$current_loan['duration']} weeks");
        } else if ($new_status === 'rejected') {
            // If rejected, you might want to set progress to something else or leave it as is
            $update_sql .= ", progress = 'rejected'";
        }
        
        $update_sql .= " WHERE loan_id = ?";
        array_push($update_params, $loan_id);
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_result = $update_stmt->execute($update_params);
        
        if (!$update_result) {
            throw new Exception("Failed to update loan status in loan table.");
        }
        
        // 4. Record the review decision
        $loan_number = $current_loan['loan_number'] ?? 'N/A';
        $review_id = recordLoanReview($pdo, $loan_id, $loan_number, $admin_id, $new_status, $admin_notes);
        
        if (!$review_id) {
            throw new Exception("Failed to record loan review decision.");
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Loan status updated successfully to: " . strtoupper($new_status);
        if ($new_status === 'approved') {
            $_SESSION['success_message'] .= ". Loan has been activated and marked as CURRENT.";
        }
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update loan status. Please try again. Error: " . $e->getMessage();
        error_log("Error updating loan status: " . $e->getMessage());
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
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

// Function to get loan requests for this loan
function getLoanRequests($pdo, $loan_id) {
    try {
        $sql = "SELECT lr.*, a.username as admin_name 
                FROM loan_requests lr 
                LEFT JOIN admin_users a ON lr.admin_id = a.admin_id 
                WHERE lr.loan_id = ? 
                ORDER BY lr.date_of_request DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching loan requests: " . $e->getMessage());
        return [];
    }
}

// Function to check if loan is overdue and calculate overdue days
function checkLoanOverdue($loan_end_date) {
    if (empty($loan_end_date)) {
        return ['is_overdue' => false, 'overdue_days' => 0];
    }
    
    $current_date = new DateTime();
    $end_date = new DateTime($loan_end_date);
    
    if ($current_date > $end_date) {
        $interval = $current_date->diff($end_date);
        $overdue_days = $interval->days;
        return ['is_overdue' => true, 'overdue_days' => $overdue_days];
    }
    
    return ['is_overdue' => false, 'overdue_days' => 0];
}

// Function to display loan details
function displayLoanDetails($loan, $pdo) {
    // Check if loan is overdue
    $overdue_info = checkLoanOverdue($loan['loan_end_date'] ?? '');
    $is_overdue = $overdue_info['is_overdue'];
    $overdue_days = $overdue_info['overdue_days'];
    
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
        'overdue' => 'rgba(231,76,60,0.14)',
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
    
    // Get loan requests for this loan
    $loan_requests = getLoanRequests($pdo, $loan['loan_id']);
    $loan_requests_section = '';
    
    if (!empty($loan_requests)) {
        $loan_requests_section = '
        <div class="row">
            <div class="col-12">
                <div class="card border-primary shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Loan Requests</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date Requested</th>
                                        <th>Request Message</th>
                                        <th>Status</th>
                                        <th>Requested By</th>
                                        <th>Admin Notes</th>
                                    </tr>
                                </thead>
                                <tbody>';
        
        foreach ($loan_requests as $request) {
            $status_badge = match($request['status']) {
                'pending' => '<span class="badge bg-warning">Pending</span>',
                'approved' => '<span class="badge bg-success">Approved</span>',
                'rejected' => '<span class="badge bg-danger">Rejected</span>',
                'completed' => '<span class="badge bg-info">Completed</span>',
                default => '<span class="badge bg-secondary">' . $request['status'] . '</span>'
            };
            
            $admin_name_display = $request['admin_name'] ?? 'Admin #' . $request['admin_id'];
            $notes_preview = !empty($request['admin_notes']) 
                ? '<span class="text-muted" title="' . htmlspecialchars($request['admin_notes']) . '">' 
                  . (strlen($request['admin_notes']) > 50 ? substr($request['admin_notes'], 0, 50) . '...' : $request['admin_notes']) 
                  . '</span>'
                : '<span class="text-muted">No notes</span>';
            
            $loan_requests_section .= '
                                    <tr>
                                        <td>' . date('M j, Y g:i A', strtotime($request['date_of_request'])) . '</td>
                                        <td>' . nl2br(htmlspecialchars($request['request_message'])) . '</td>
                                        <td>' . $status_badge . '</td>
                                        <td>' . $admin_name_display . '</td>
                                        <td>' . $notes_preview . '</td>
                                    </tr>';
        }
        
        $loan_requests_section .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // Overdue alert section
    $overdue_alert = '';
    if ($is_overdue && $loan['status'] === 'overdue') {
        $overdue_alert = '
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h4 class="alert-heading mb-1">LOAN OVERDUE!</h4>
                            <p class="mb-0">
                                This loan is <strong>' . $overdue_days . ' day(s)</strong> overdue. 
                                The repayment deadline was on <strong>' . date('F j, Y', strtotime($loan['loan_end_date'])) . '</strong>.
                            </p>
                            <p class="mb-0 mt-1">
                                <small>Penalty: K15 per day (Total penalty: K' . number_format($overdue_days * 15, 2) . ')</small>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>';
    }
    
    // Show date calculation info for pending loans
    $date_calculation_info = '';
    if ($loan['status'] === 'pending') {
        $calculated_end_date = date('Y-m-d', strtotime("+{$loan['duration']} weeks"));
        $date_calculation_info = '
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Date Calculation Preview:</strong> If approved, the loan will start today and end on ' . date('F j, Y', strtotime($calculated_end_date)) . ' 
            (based on ' . $loan['duration'] . ' week(s) duration).
        </div>';
    }
    
    // Admin Actions Section - Only show Send Loan Request button for non-pending loans
    $admin_actions_section = '';
    
    if ($loan['status'] !== 'pending') {
        // For non-pending loans, only show the Send Loan Request button
        $admin_actions_section = '
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <button class="btn btn-warning btn-lg me-3" type="button" data-bs-toggle="modal" data-bs-target="#requestModal">
                                <i class="fas fa-hand-holding-usd me-2"></i>Send Loan Request
                            </button>
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
        </div>';
    } else {
        // For pending loans, show the full admin actions (status update + buttons)
        $admin_actions_section = '
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
                            <button class="btn btn-warning me-2" type="button" data-bs-toggle="modal" data-bs-target="#requestModal">
                                <i class="fas fa-hand-holding-usd me-2"></i>Send Loan Request
                            </button>
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
        </div>';
    }
    
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
                    ' . ($is_overdue && $loan['status'] === 'approved' ? '<span class="badge bg-danger fs-6 me-2">OVERDUE</span>' : '') . '
                    <span class="badge bg-' . $status_color . ' fs-6">' . strtoupper($loan['status'] ?? 'PENDING') . '</span>
                </div>
            </div>
        </div>
        
        <div class="card-body" style="background: ' . $status_bg . ';">
            ' . $date_calculation_info . '
            ' . $overdue_alert . '
            ' . $review_section . '
            ' . $past_reviews_section . '
            ' . $loan_requests_section . '
            
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
                            <p><strong>Start Date:</strong> ' . ($loan['loan_start_date'] ?? 'Not set') . '</p>
                            <p><strong>End Date:</strong> ' . ($loan['loan_end_date'] ?? 'Not set') . ' 
                                ' . ($is_overdue && $loan['status'] === 'approved' ? '<span class="badge bg-danger ms-2">OVERDUE</span>' : '') . '
                            </p>
                            ' . ($is_overdue && $loan['status'] === 'approved' ? '
                            <div class="alert alert-warning mt-2 p-2">
                                <small>
                                    <i class="fas fa-clock me-1"></i><strong>Overdue by:</strong> ' . $overdue_days . ' day(s)
                                    <br><i class="fas fa-exclamation-triangle me-1"></i><strong>Daily Penalty:</strong> K15 per day
                                    <br><i class="fas fa-calculator me-1"></i><strong>Total Penalty:</strong> K' . number_format($overdue_days * 15, 2) . '
                                </small>
                            </div>' : '') . '
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
            
            ' . $admin_actions_section . '
        </div>
    </div>';
}

?>