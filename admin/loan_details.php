<?php
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
    
    // Check if loan is overdue
    $overdue_warning = '';
    if (!empty($loan['loan_end_date']) && $loan['status'] === 'approved') {
        $end_date = new DateTime($loan['loan_end_date']);
        $today = new DateTime();
        
        if ($today > $end_date) {
            $interval = $today->diff($end_date);
            $days_overdue = $interval->days;
            $overdue_warning = '
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-danger d-flex align-items-center shadow-sm">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">LOAN OVERDUE!</h5>
                            <p class="mb-0">This loan is overdue by <strong>' . $days_overdue . ' day(s)</strong>. The repayment was due on ' . $loan['loan_end_date'] . '.</p>
                        </div>
                        <span class="badge bg-danger fs-6">
                            <i class="fas fa-clock me-1"></i>' . $days_overdue . ' Day(s) Late
                        </span>
                    </div>
                </div>
            </div>';
        }
    }
    
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
    
    // Check if loan is approved or rejected to hide admin actions
    $is_final_status = ($loan['status'] === 'approved' || $loan['status'] === 'rejected');
    
    // Admin Actions section - only show if loan is pending
    $admin_actions_section = '';
    if (!$is_final_status) {
     // In the Admin Actions section, add this button:
$admin_actions_section = '
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
                        </div><br>
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
    </div>';
    } else {
        // Show final status message instead of admin actions
        $admin_actions_section = '
            <!-- Final Status Message -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-' . $status_color . ' text-white">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Loan Status Finalized</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-' . $status_color . ' mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="alert-heading">This loan has been ' . strtoupper($loan['status']) . '</h5>
                                        <p class="mb-0">No further actions can be taken on this loan application. Kindly send a request for status change.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                             <button class="btn btn-warning me-2" type="button" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="fas fa-hand-holding-usd me-2"></i>Create Request
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
                    <span class="badge bg-' . $status_color . ' fs-6">' . strtoupper($loan['status'] ?? 'PENDING') . '</span>
                </div>
            </div>
        </div>
        
        <div class="card-body" style="background: ' . $status_bg . ';">
            ' . $overdue_warning . '
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
            
            ' . $admin_actions_section . '
        </div>
    </div>';
}
?>