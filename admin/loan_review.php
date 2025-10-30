<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

// Get loan ID from URL parameter
$loan_id = $_GET['loan_id'] ?? null;

if (!$loan_id) {
    $_SESSION['error_message'] = "No loan ID provided.";
    header("Location: loan.php");
    exit;
}

// Handle loan status update (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $update_sql = "UPDATE loan SET status = ?, admin_notes = ? WHERE loan_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$new_status, $admin_notes, $loan_id]);
        
        $_SESSION['success_message'] = "Loan status updated to " . ucfirst($new_status) . " successfully!";
        header("Location: loan_review.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to update loan status. Please try again.";
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

// Function to display loan details
function displayLoanDetails($loan) {
    $status_color = match($loan['status']) {
        'approved' => 'success',
        'rejected' => 'danger',
        default => 'warning'
    };
    
    $status_bg = match($loan['status']) {
        'approved' => 'rgba(28,200,138,0.14)',
        'rejected' => 'rgba(231,76,60,0.14)',
        default => 'rgba(246,194,62,0.13)'
    };
    
    // Convert BLOB images to base64 for display
    $user_id_image_src = $loan['user_id_image'] ? 'data:image/jpeg;base64,' . base64_encode($loan['user_id_image']) : 'assets/img/dogs/image3.jpeg';
    $image1_src = $loan['image1'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image1']) : 'assets/img/dogs/image3.jpeg';
    $image2_src = $loan['image2'] ? 'data:image/jpeg;base64,' . base64_encode($loan['image2']) : 'assets/img/dogs/image3.jpeg';
    
    // Calculate interest percentage and total repayment
    $interest_percentage = $loan['amount'] > 0 ? ($loan['interest'] / $loan['amount']) * 100 : 0;
    $total_repayment = $loan['amount'] + $loan['interest'];
    
    return '
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">Loan Application Review</h4>
                    <p class="text-muted mb-0">Application Date: ' . ($loan['loan_start_date'] ?? 'N/A') . '</p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-' . $status_color . ' fs-6">' . strtoupper($loan['status'] ?? 'PENDING') . '</span>
                </div>
            </div>
        </div>
        
        <div class="card-body" style="background: ' . $status_bg . ';">
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
                                              placeholder="Add notes about this loan decision...">' . ($loan['admin_notes'] ?? '') . '</textarea>
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
                    <?php echo displayLoanDetails($loan); ?>
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