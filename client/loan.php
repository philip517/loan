<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    try {
        $loan_id = $_POST['loan_id'];
        $topic = $_POST['topic'];
        $message_text = $_POST['message_text'];
        
        $message_sql = "INSERT INTO message (status, topic, message_text, type, loan_id) 
                       VALUES ('sent', ?, ?, 'user_to_admin', ?)";
        $message_stmt = $pdo->prepare($message_sql);
        $message_stmt->execute([$topic, $message_text, $loan_id]);
        
        $_SESSION['success_message'] = "Message sent successfully!";
        header("Location: loan.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to send message. Please try again.";
    }
}

// Fetch user's loans from database using the status column
$loan_sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.NRC, u.email, u.occupation, u.address,
                    k.first_name as kin_first_name, k.last_name as kin_last_name, 
                    k.nrc_number as kin_nrc, k.phone as kin_phone
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
                    <h5 class="mb-0">Loan Application #'.$loan['loan_id'].'</h5>
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
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>My Loans</title>
    <meta name="description" content="My Loans Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <link rel="stylesheet" href="assets/css/loan.css">

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
                    
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <a class="btn btn-primary" href="apply_loan.php">
                            <i class="fas fa-plus me-2"></i>Apply for New Loan
                        </a>
                    </div>

                    <!-- User Profile Section -->

                    <div class="row mt-4">
                        <div class="col-12">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1" style="color: #495057; font-weight: 500;">
                                        <i class="fas fa-clock me-2"></i>PENDING (<?php echo count($pending_loans); ?>)
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2" style="color: #495057; font-weight: 500;">
                                        <i class="fas fa-check-circle me-2"></i>APPROVED (<?php echo count($approved_loans); ?>)
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3" style="color: #495057; font-weight: 500;">
                                        <i class="fas fa-times-circle me-2"></i>REJECTED (<?php echo count($rejected_loans); ?>)
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Pending Loans Tab -->
                                <div class="tab-pane active" role="tabpanel" id="tab-1">
                                    <?php if (empty($pending_loans)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                            <h4>No Pending Loans</h4>
                                            <p class="text-muted">You don't have any pending loan applications.</p>
                                            <a class="btn btn-primary" href="apply_loan.php">Apply for a Loan</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($pending_loans as $loan): ?>
                                            <?php echo displayLoanCard($loan, 'pending'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Approved Loans Tab -->
                                <div class="tab-pane" role="tabpanel" id="tab-2">
                                    <?php if (empty($approved_loans)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h4>No Approved Loans</h4>
                                            <p class="text-muted">You don't have any approved loans at the moment.</p>
                                            <a class="btn btn-primary" href="apply_loan.php">Apply for a Loan</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($approved_loans as $loan): ?>
                                            <?php echo displayLoanCard($loan, 'approved'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Rejected Loans Tab -->
                                <div class="tab-pane" role="tabpanel" id="tab-3">
                                    <?php if (empty($rejected_loans)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                            <h4>No Rejected Loans</h4>
                                            <p class="text-muted">You don't have any rejected loan applications.</p>
                                            <a class="btn btn-primary" href="apply_loan.php">Apply for a Loan</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($rejected_loans as $loan): ?>
                                            <?php echo displayLoanCard($loan, 'rejected'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="messageModal">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <h4 class="modal-title">Send Message About Loan</h4>
                                <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="loan_id" id="modal_loan_id">
                                <input type="hidden" name="send_message" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Topic/Subject</label>
                                    <select class="form-select" name="topic" required>
                                        <option value="" selected disabled>Select Topic</option>
                                        <option value="Loan Application Status">Loan Application Status</option>
                                        <option value="Additional Information Needed">Additional Information Needed</option>
                                        <option value="Collateral Questions">Collateral Questions</option>
                                        <option value="Payment Issues">Payment Issues</option>
                                        <option value="Extension Request">Extension Request</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message_text" rows="6" placeholder="Type your message here..." required></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        Your message will be sent to the administrators. They will respond to you as soon as possible.
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
      <div class="modal fade text-center" role="dialog" tabindex="-1" id="modal-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"></div>
                <div class="modal-body">
                    <p>Leaving Already ?</p>
                </div>
                <div class="modal-footer text-end" style="text-align: justify;">
                    <p style="text-align: left;">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal" style="text-align: center;">No</button>
                        &nbsp;&nbsp;
                        <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="../index.php">Yes</a>
                    </p>
                    <div class="text-center" style="display: inline-block;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        function setLoanId(loanId) {
            document.getElementById('modal_loan_id').value = loanId;
        }
        
        function openImageModal(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Initialize tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Activate the first tab by default
            const firstTab = document.querySelector('.nav-tabs .nav-link');
            if (firstTab) {
                firstTab.click();
            }
        });
    </script>
</body>
</html>