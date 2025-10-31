<?php
// Fetch user's loans from database using the status column
$loan_sql = "SELECT 
                l.*, 
                l.loan_number,  -- Added loan_number from loan table
                u.first_name, 
                u.last_name, 
                u.phone, 
                u.NRC, 
                u.email, 
                u.occupation, 
                u.address,
                k.first_name as kin_first_name, 
                k.last_name as kin_last_name, 
                k.nrc_number as kin_nrc, 
                k.phone as kin_phone
             FROM loan l 
             LEFT JOIN user_table u ON l.user_id = u.user_id 
             LEFT JOIN kin k ON l.loan_id = k.loan_id 
             WHERE l.user_id = ? 
             ORDER BY l.loan_start_date DESC";
$loan_stmt = $pdo->prepare($loan_sql);
$loan_stmt->execute([$user_id]);
$loans = $loan_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate loans by status using the status column
$approved_loans = [];
$pending_loans = [];
$rejected_loans = [];

foreach ($loans as $loan) {
    if ($loan['status'] === 'approved') {
        $approved_loans[] = $loan;
    } elseif ($loan['status'] === 'rejected') {
        $rejected_loans[] = $loan;
    } else {
        $pending_loans[] = $loan;
    }
}

// Fetch all user data for display
$user_sql = "SELECT * FROM user_table WHERE user_id = ?";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);



// Function to display loan details in card format
function displayLoanCard($loan, $status) {
    $status_color = match($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        default => 'warning'
    };
    
    $status_bg = match($status) {
        'approved' => 'rgba(28,200,138,0.14)',
        'rejected' => 'rgba(231,76,60,0.14)',
        default => 'rgba(246,194,62,0.13)'
    };
    
    $border_color = match($status) {
        'approved' => 'border-left-success',
        'rejected' => 'border-left-danger',
        default => 'border-left-warning'
    };
    
    // Get loan number or display placeholder
    $loan_number = $loan['loan_number'] ?? 'N/A';
    
    // Convert BLOB images to base64 for display
    $image1_src = $loan['image1'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image1']) : 'assets/img/dogs/image3.jpeg';
    $image2_src = $loan['image2'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image2']) : 'assets/img/dogs/image3.jpeg';
    $user_id_image_src = $loan['user_id_image'] ? 'data:image/jpeg;base64,' . base64_encode($loan['user_id_image']) : 'assets/img/dogs/image3.jpeg';
    
    // Calculate interest percentage and total repayment (similar to review.js)
    $interest_rate = 0.10; // 10% per week
    $interest = $loan['amount'] * $interest_rate * $loan['duration'];
    $interest_percentage = $loan['amount'] > 0 ? ($interest / $loan['amount']) * 100 : 0;
    $total_repayment = $loan['amount'] + $interest;
    
    // Display admin notes if available and loan is rejected
    $admin_notes_section = '';
    if ($status === 'rejected' && !empty($loan['admin_notes'])) {
        $admin_notes_section = '
        <div class="row mt-3">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white py-2">
                        <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Admin Feedback</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-dark">'.htmlspecialchars($loan['admin_notes']).'</p>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    $action_buttons = '';
    if ($status === 'pending') {
        $action_buttons = '
        <div class="text-end mb-3">
            <a href="apply_loan.php?edit='.$loan['loan_id'].'" class="btn btn-warning btn-sm me-2">
                <i class="fas fa-edit me-2"></i>Edit Loan
            </a>
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#messageModal" 
                    onclick="setLoanId('.$loan['loan_id'].')">
                <i class="fas fa-envelope me-2"></i>Send Message
            </button>
        </div>';
    } else {
        // For approved/rejected loans, show only message button
        $action_buttons = '
        <div class="text-end mb-3">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#messageModal" 
                    onclick="setLoanId('.$loan['loan_id'].')">
                <i class="fas fa-envelope me-2"></i>Send Message
            </button>
        </div>';
    }
    
    return '
    <div class="card shadow-lg border-0 mb-4 '.$border_color.'" style="background: '.$status_bg.';">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Loan Application #'.$loan_number.'</h5>
                    <p class="text-muted mb-0">Applied on: '.($loan['loan_start_date'] ?? 'N/A').'</p>
                </div>
            
            </div>
        </div>
        
        <div class="card-body">
            '.$action_buttons.'
            <div class="row">
                <!-- Client & Kin Information -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client & Kin Details</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Client Name:</strong> '.($loan['first_name'] ?? 'N/A').' '.($loan['last_name'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>NRC:</strong> '.($loan['NRC'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>Phone:</strong> '.($loan['phone'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>Email:</strong> '.($loan['email'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>Occupation:</strong> '.($loan['occupation'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>Address:</strong> '.($loan['address'] ?? 'N/A').'</p>
                            <hr>
                            <p class="mb-2"><strong>Next of Kin:</strong> '.($loan['kin_first_name'] ?? 'N/A').' '.($loan['kin_last_name'] ?? 'N/A').'</p>
                            <p class="mb-2"><strong>Kin NRC:</strong> '.($loan['kin_nrc'] ?? 'N/A').'</p>
                            <p class="mb-0"><strong>Kin Phone:</strong> '.($loan['kin_phone'] ?? 'N/A').'</p>
                        </div>
                    </div>
                </div>
                
                <!-- Loan Details -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Loan Details</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Loan Amount:</strong> K'.number_format($loan['amount'] ?? 0, 2).'</p>
                            <p class="mb-2"><strong>Duration:</strong> '.($loan['duration'] ?? 0).' Weeks</p>
                            <p class="mb-2"><strong>Interest Rate:</strong> 10% per week</p>
                            <p class="mb-2"><strong>Interest Amount:</strong> K'.number_format($interest, 2).' ('.number_format($interest_percentage, 1).'%)</p>
                            <p class="mb-2"><strong>Total Repayment:</strong> K'.number_format($total_repayment, 2).'</p>
                            <p class="mb-2"><strong>Start Date:</strong> '.($loan['loan_start_date'] ?? 'N/A').'</p>
                            <p class="mb-0"><strong>End Date:</strong> '.($loan['loan_end_date'] ?? 'N/A').'</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Collateral Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark py-2">
                            <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Collateral & ID Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><strong>Collateral Item:</strong> '.($loan['collateral_name'] ?? 'N/A').'</p>
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <h6>ID Image</h6>
                                    <img src="'.$user_id_image_src.'" class="img-fluid clickable-image" 
                                         style="max-height: 200px; max-width: 100%; cursor: pointer; border: 2px solid #dee2e6; border-radius: 8px;" 
                                         alt="User ID Image" 
                                         onclick="openImageModal(\''.$user_id_image_src.'\', \'User ID Image\')">
                                    <p class="small text-muted mt-2">User Identification</p>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <h6>Collateral Image 1</h6>
                                    <img src="'.$image1_src.'" class="img-fluid clickable-image" 
                                         style="max-height: 200px; max-width: 100%; cursor: pointer; border: 2px solid #dee2e6; border-radius: 8px;" 
                                         alt="Collateral Image 1" 
                                         onclick="openImageModal(\''.$image1_src.'\', \'Collateral Image 1\')">
                                    <p class="small text-muted mt-2">Collateral Front View</p>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <h6>Collateral Image 2</h6>
                                    <img src="'.$image2_src.'" class="img-fluid clickable-image" 
                                         style="max-height: 200px; max-width: 100%; cursor: pointer; border: 2px solid #dee2e6; border-radius: 8px;" 
                                         alt="Collateral Image 2" 
                                         onclick="openImageModal(\''.$image2_src.'\', \'Collateral Image 2\')">
                                    <p class="small text-muted mt-2">Collateral Alternate View</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            '.$admin_notes_section.'
        </div>
    </div>';
}
?>