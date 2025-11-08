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

require 'loan_review_functions.php';
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