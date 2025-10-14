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
$loan_sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.NRC, k.first_name as kin_first_name, 
                    k.last_name as kin_last_name, k.nrc_number as kin_nrc, k.phone as kin_phone
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

foreach ($loans as $loan) {
    if ($loan['status'] === 'approved') {
        $approved_loans[] = $loan;
    } else {
        $pending_loans[] = $loan;
    }
}

// Function to display loan details
function displayLoanDetails($loan, $status) {
    $bg_color = $status === 'approved' ? 'rgba(28,200,138,0.14)' : 'rgba(246,194,62,0.13)';
    $border_color = $status === 'approved' ? 'border-left-success' : 'border-left-warning';
    
    // Convert BLOB images to base64 for display
    $image1_src = $loan['image1'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image1']) : 'assets/img/dogs/image3.jpeg';
    $image2_src = $loan['image2'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image2']) : 'assets/img/dogs/image3.jpeg';
    
    // Calculate interest percentage
    $interest_percentage = $loan['amount'] > 0 ? ($loan['interest'] / $loan['amount']) * 100 : 0;
    
    $message_button = '';
    if ($status === 'pending') {
        $message_button = '
        <div class="text-end mb-3">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#messageModal" 
                    onclick="setLoanId('.$loan['loan_id'].')">
                <i class="fas fa-envelope me-2"></i>Send Message About This Loan
            </button>
        </div>';
    }
    
    return $message_button . '
    <section class="ps-2 pe-2 pt-3 mb-4" style="background: '.$bg_color.';">
        <p class="mt-0 pt-0">Date of Application: '.($loan['loan_start_date'] ?? 'N/A').'</p>
        <div class="row me-0">
            <div class="col-md-6 col-lg-6 col-xl-6 mb-4">
                <div class="card shadow py-2 '.$border_color.'">
                    <div class="card-body text-start">
                        <h5>Client Details</h5>
                        <p><strong>Name:</strong> '.($loan['first_name'] ?? 'N/A').' '.($loan['last_name'] ?? 'N/A').'<br>
                        <strong>NRC:</strong> '.($loan['NRC'] ?? 'N/A').'<br>
                        <strong>Phone:</strong> '.($loan['phone'] ?? 'N/A').'</p>
                        
                        <h5>Next of Kin</h5>
                        <p><strong>Name:</strong> '.($loan['kin_first_name'] ?? 'N/A').' '.($loan['kin_last_name'] ?? 'N/A').'<br>
                        <strong>NRC:</strong> '.($loan['kin_nrc'] ?? 'N/A').'<br>
                        <strong>Phone:</strong> '.($loan['kin_phone'] ?? 'N/A').'</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 text-start mb-4">
                <div class="card shadow py-2 '.$border_color.'">
                    <div class="card-body">
                        <h5>Loan Details</h5>
                        <p><strong>Amount:</strong> K'.number_format($loan['amount'] ?? 0, 2).'<br>
                        <strong>Duration:</strong> '.($loan['duration'] ?? 0).' week(s)<br>
                        <strong>Interest:</strong> K'.number_format($loan['interest'] ?? 0, 2).' ('.number_format($interest_percentage, 1).'%)<br>
                        <strong>Total Repayment:</strong> K'.number_format(($loan['amount'] + $loan['interest']) ?? 0, 2).'<br>
                        <strong>Status:</strong> <span class="badge bg-'.($status === 'approved' ? 'success' : 'warning').'">'.ucfirst($status).'</span><br>
                        <strong>End Date:</strong> '.($loan['loan_end_date'] ?? 'N/A').'</p>
                        
                        <h5>Collateral</h5>
                        <p><strong>Item:</strong> '.($loan['collateral_name'] ?? 'N/A').'</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 text-start mb-4">
                <div class="card shadow py-2 '.$border_color.'">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Collateral Image 1</h6>
                                <img src="'.$image1_src.'" class="img-fluid clickable-image" style="max-height: 200px; max-width: 100%; cursor: pointer;" 
                                     alt="Collateral Image 1" 
                                     onclick="openImageModal(\''.$image1_src.'\', \'Collateral Image 1\')">
                            </div>
                            <div class="col-md-6">
                                <h6>Collateral Image 2</h6>
                                <img src="'.$image2_src.'" class="img-fluid clickable-image" style="max-height: 200px; max-width: 100%; cursor: pointer;" 
                                     alt="Collateral Image 2" 
                                     onclick="openImageModal(\''.$image2_src.'\', \'Collateral Image 2\')">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>';
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan</title>
    <meta name="description" content="Loan Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .clickable-image {
            transition: transform 0.2s ease-in-out;
        }
        .clickable-image:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
         <?php require 'navbar.php' ?>
                <div class="container-fluid" style="margin-top: 100PX;">
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
                        <h3 class="text-dark mb-0"><strong>MY LOANS</strong></h3>
                    </div>
                    <div class="row" style="display: flex;text-align: center;">
                        <div class="col-12">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link focus-ring focus-ring-primary" role="tab" data-bs-toggle="tab" href="#tab-1">
                                        APPROVED (<?php echo count($approved_loans); ?>)
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active bg-gradient focus-ring" role="tab" data-bs-toggle="tab" href="#tab-2">
                                        PENDING (<?php echo count($pending_loans); ?>)
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <!-- Approved Loans Tab -->
                                <div class="tab-pane" role="tabpanel" id="tab-1">
                                    <?php if (empty($approved_loans)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h4>No Approved Loans</h4>
                                            <p class="text-muted">You don't have any approved loans at the moment.</p>
                                            <a class="btn btn-primary" role="button" href="apply_loan.php">Apply for a Loan</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($approved_loans as $loan): ?>
                                            <?php echo displayLoanDetails($loan, 'approved'); ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Pending Loans Tab -->
                                <div class="tab-pane active" role="tabpanel" id="tab-2">
                                    <?php if (empty($pending_loans)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                            <h4>No Pending Loans</h4>
                                            <p class="text-muted">You don't have any pending loan applications.</p>
                                            <a class="btn btn-primary" role="button" href="apply_loan.php">Apply for a Loan</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($pending_loans as $loan): ?>
                                            <?php echo displayLoanDetails($loan, 'pending'); ?>
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
                        <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="login.php">Yes</a>
                    </p>
                    <div class="text-center" style="display: inline-block;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script src="assets/js/zoom_image.js"></script>
</body>
</html>