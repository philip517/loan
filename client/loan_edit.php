<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Get loan ID from URL parameter
$loan_id = $_GET['edit'] ?? null;

if (!$loan_id) {
    $_SESSION['error_message'] = "No loan specified for editing.";
    header("Location: loan.php");
    exit;
}

// Fetch loan data from database
$loan_sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.NRC, u.email, u.occupation, u.address, u.date_of_birth, u.nationality,
                    k.first_name as kin_first_name, k.last_name as kin_last_name, 
                    k.nrc_number as kin_nrc, k.phone as kin_phone
             FROM loan l 
             LEFT JOIN user_table u ON l.user_id = u.user_id 
             LEFT JOIN kin k ON l.loan_id = k.loan_id 
             WHERE l.loan_id = ? AND l.user_id = ?";
$loan_stmt = $pdo->prepare($loan_sql);
$loan_stmt->execute([$loan_id, $user_id]);
$loan_data = $loan_stmt->fetch(PDO::FETCH_ASSOC);

// Check if loan exists and belongs to the user
if (!$loan_data) {
    $_SESSION['error_message'] = "Loan not found or you don't have permission to edit it.";
    header("Location: loan.php");
    exit;
}

// Set values from loan data
$first_name = $loan_data['first_name'] ?? '';
$last_name = $loan_data['last_name'] ?? '';
$phone = $loan_data['phone'] ?? '';
$nrc = $loan_data['NRC'] ?? '';
$occupation = $loan_data['occupation'] ?? '';
$address = $loan_data['address'] ?? '';
$date_of_birth = $loan_data['date_of_birth'] ?? '';
$nationality = $loan_data['nationality'] ?? '';
$collateral_name = $loan_data['collateral_name'] ?? '';
$kin_first_name = $loan_data['kin_first_name'] ?? '';
$kin_last_name = $loan_data['kin_last_name'] ?? '';
$kin_nrc = $loan_data['kin_nrc'] ?? '';
$kin_phone = $loan_data['kin_phone'] ?? '';
$loan_amount = $loan_data['amount'] ?? '';
$loan_duration = $loan_data['duration'] ?? '';
$status = $loan_data['status'] ?? '';
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Edit Loan Application</title>
    <meta name="description" content="Edit Loan Application page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .clickable-image {
            transition: transform 0.2s ease-in-out;
        }
        .clickable-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border-color: #007bff !important;
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
        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }
        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
            color: #007bff;
            border-bottom: 3px solid #007bff;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            border: none;
        }
        .nav-tabs .nav-link:hover {
            border: none;
            color: #007bff;
        }
        .tab-content {
            padding: 20px 0;
        }
        .preview-image {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 5px;
            max-width: 200px;
            max-height: 200px;
        }
        .form-control-user {
            border-radius: 0.375rem;
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            background: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
        .auto-fill-badge {
            font-size: 0.65em;
            margin-left: 5px;
        }
        .auto-fill-info {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 6px 12px;
        }
        .edit-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .current-file {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 10px;
            margin-bottom: 10px;
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

                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>EDIT LOAN APPLICATION</strong></h3>
                        <div>
                            <span class="badge 
                                <?php 
                                if ($status == 'pending') echo 'bg-warning';
                                elseif ($status == 'approved') echo 'bg-success';
                                elseif ($status == 'rejected') echo 'bg-danger';
                                elseif ($status == 'under_review') echo 'bg-info';
                                else echo 'bg-secondary';
                                ?> 
                                status-badge">
                                Status: <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Edit Notice -->
                    <div class="alert alert-warning edit-notice mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> You are editing loan application #<?php echo $loan_id; ?>. 
                        Some changes may require re-approval of your loan application.
                    </div>

                    <!-- Main form that collects all data -->
                    <form method="POST" action="update_loan.php" class="user" id="loanEditForm" enctype="multipart/form-data">
                        <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                        
                        <div class="row">
                            <div class="col-12">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">
                                            <i class="fas fa-user me-2"></i>Personal Details
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">
                                            <i class="fas fa-shield-alt me-2"></i>Collateral Details
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">
                                            <i class="fas fa-users me-2"></i>Next Of Kin
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-4">
                                            <i class="fas fa-money-bill-wave me-2"></i>Loan Details
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content mt-4">
                                    <!-- Personal Details Tab -->
                                    <div class="tab-pane active" role="tabpanel" id="tab-1">
                                        <div class="card shadow-lg border-0 border-left-primary">
                                            <div class="card-header bg-primary text-white py-3">
                                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info auto-fill-info mb-4">
                                                    <i class="fas fa-lightbulb me-2"></i>
                                                    <strong>Tip:</strong> Your information has been pre-filled from your loan application.
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">First Name</label>
                                                            <input class="form-control form-control-user" type="text" id="first_name" placeholder="Enter First Name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input class="form-control form-control-user" type="text" id="last_name" placeholder="Enter Last Name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input class="form-control form-control-user" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Occupation</label>
                                                            <select class="form-select form-control-user" name="occupation" required>
                                                                <option value="" disabled>Select Occupation</option>
                                                                <option value="student" <?php echo ($occupation == 'student') ? 'selected' : ''; ?>>Student</option>
                                                                <option value="worker" <?php echo ($occupation == 'worker') ? 'selected' : ''; ?>>Worker</option>
                                                                <option value="unemployed" <?php echo ($occupation == 'unemployed') ? 'selected' : ''; ?>>Unemployed</option>
                                                                <option value="entreprenuer" <?php echo ($occupation == 'entreprenuer') ? 'selected' : ''; ?>>Entrepreneur</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">NRC Number</label>
                                                            <input class="form-control form-control-user" type="text" name="nrc" placeholder="Enter NRC Number" value="<?php echo htmlspecialchars($nrc); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nationality</label>
                                                            <select class="form-select form-control-user" name="nationality" required>
                                                                <option value="" disabled>Select Nationality</option>
                                                                <option value="Zambian" <?php echo ($nationality == 'Zambian') ? 'selected' : ''; ?>>Zambian</option>
                                                                <option value="Other" <?php echo ($nationality == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control form-control-user" type="text" placeholder="Enter Phone Number" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Address</label>
                                                            <input class="form-control form-control-user" type="text" placeholder="Enter Address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label class="form-label">Upload ID/NRC Document</label>
                                                            <input class="form-control" type="file" name="id_image" accept="image/*" onchange="previewImage(this, 'id_preview')">
                                                            <small class="form-text text-muted">Upload a clear image of your ID or NRC document (leave empty to keep current)</small>
                                                            <div class="mt-2">
                                                                <?php if (!empty($loan_data['user_id_image'])): ?>
                                                                    <div class="current-file">
                                                                        <strong>Current ID Document:</strong> 
                                                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="viewCurrentImage('id_image')">
                                                                            <i class="fas fa-eye me-1"></i>View Current
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <img id="id_preview" class="preview-image" src="" style="display: none;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-primary px-4" onclick="switchToTab(2)">
                                                        <i class="fas fa-arrow-right me-2"></i>Next: Collateral Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Collateral Details Tab -->
                                    <div class="tab-pane" role="tabpanel" id="tab-2">
                                        <div class="card shadow-lg border-0 border-left-warning">
                                            <div class="card-header bg-warning text-dark py-3">
                                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Collateral Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Item Name</label>
                                                            <input class="form-control form-control-user" type="text" id="collateral_name" placeholder="Enter collateral item name" name="collateral_name" value="<?php echo htmlspecialchars($collateral_name); ?>" required>
                                                            <small class="form-text text-muted">Describe the item you're using as collateral</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 1</label>
                                                            <input class="form-control" type="file" name="collateral_image1" accept="image/*" onchange="previewImage(this, 'image1_preview')">
                                                            <small class="form-text text-muted">Leave empty to keep current image</small>
                                                            <div class="mt-2">
                                                                <?php if (!empty($loan_data['image1'])): ?>
                                                                    <div class="current-file">
                                                                        <strong>Current Image 1:</strong> 
                                                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="viewCurrentImage('collateral1')">
                                                                            <i class="fas fa-eye me-1"></i>View Current
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <img id="image1_preview" class="preview-image" src="" style="display: none;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 2</label>
                                                            <input class="form-control" type="file" name="collateral_image2" accept="image/*" onchange="previewImage(this, 'image2_preview')">
                                                            <small class="form-text text-muted">Leave empty to keep current image</small>
                                                            <div class="mt-2">
                                                                <?php if (!empty($loan_data['image2'])): ?>
                                                                    <div class="current-file">
                                                                        <strong>Current Image 2:</strong> 
                                                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="viewCurrentImage('collateral2')">
                                                                            <i class="fas fa-eye me-1"></i>View Current
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <img id="image2_preview" class="preview-image" src="" style="display: none;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-secondary me-3" onclick="switchToTab(1)">
                                                        <i class="fas fa-arrow-left me-2"></i>Back
                                                    </button>
                                                    <button type="button" class="btn btn-primary px-4" onclick="switchToTab(3)">
                                                        <i class="fas fa-arrow-right me-2"></i>Next: Next of Kin
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Next Of Kin Details Tab -->
                                    <div class="tab-pane" role="tabpanel" id="tab-3">
                                        <div class="card shadow-lg border-0 border-left-info">
                                            <div class="card-header bg-info text-white py-3">
                                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Next of Kin Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">First Name</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_first_name" placeholder="Enter First Name" name="kin_first_name" value="<?php echo htmlspecialchars($kin_first_name); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_last_name" placeholder="Enter Last Name" name="kin_last_name" value="<?php echo htmlspecialchars($kin_last_name); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">NRC Number</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_nrc" placeholder="Enter NRC Number" name="kin_nrc" value="<?php echo htmlspecialchars($kin_nrc); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control form-control-user" type="text" name="kin_phone" placeholder="Enter Phone Number" value="<?php echo htmlspecialchars($kin_phone); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-secondary me-3" onclick="switchToTab(2)">
                                                        <i class="fas fa-arrow-left me-2"></i>Back
                                                    </button>
                                                    <button type="button" class="btn btn-primary px-4" onclick="switchToTab(4)">
                                                        <i class="fas fa-arrow-right me-2"></i>Next: Loan Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Loan Details Tab -->
                                    <div class="tab-pane" role="tabpanel" id="tab-4">
                                        <div class="card shadow-lg border-0 border-left-success">
                                            <div class="card-header bg-success text-white py-3">
                                                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Loan Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Loan Amount</label>
                                                            <input class="form-control form-control-user" type="number" max="5000" min="500" name="loan_amount" placeholder="Enter amount (K500 - K5000)" value="<?php echo htmlspecialchars($loan_amount); ?>" required id="loan_amount" oninput="calculateInterest()">
                                                            <small class="form-text text-muted">Amount must be between K500 and K5000</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Loan Duration</label>
                                                            <select class="form-select form-control-user" name="loan_duration" required id="loan_duration" onchange="calculateInterest()">
                                                                <option value="" disabled>Select Duration</option>
                                                                <option value="1" <?php echo ($loan_duration == '1') ? 'selected' : ''; ?>>1 Week</option>
                                                                <option value="2" <?php echo ($loan_duration == '2') ? 'selected' : ''; ?>>2 Weeks</option>
                                                                <option value="3" <?php echo ($loan_duration == '3') ? 'selected' : ''; ?>>3 Weeks</option>
                                                                <option value="4" <?php echo ($loan_duration == '4') ? 'selected' : ''; ?>>4 Weeks</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Loan Amount</h6>
                                                                <h4 class="text-primary">K<span id="display_loan_amount"><?php echo number_format($loan_amount, 2); ?></span></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Interest</h6>
                                                                <h4 class="text-warning">K<span id="interest_amount">0.00</span></h4>
                                                                <small id="interest_percentage">0%</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Total Repayment</h6>
                                                                <h4 class="text-success">K<span id="total_repayment">0.00</span></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="alert alert-warning mt-4">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Important:</strong> Changing loan amount or duration may affect your interest rate and require re-approval.
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-secondary me-3" onclick="switchToTab(3)">
                                                        <i class="fas fa-arrow-left me-2"></i>Back
                                                    </button>
                                                    <button type="button" class="btn btn-success px-4" id="reviewButton">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden submit button for the form -->
                        <input type="submit" id="realUpdateButton" style="display: none;">
                    </form>
                </div>
            </div>

            <!-- Review Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="reviewModal">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Confirm Loan Changes</h4>
                            <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please review your changes before submitting. Your loan status may change to "Under Review" after these updates.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Personal Information</h6>
                                    <p><strong>Name:</strong> <span id="review_name"></span></p>
                                    <p><strong>Phone:</strong> <span id="review_phone"></span></p>
                                    <p><strong>Occupation:</strong> <span id="review_occupation"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Loan Details</h6>
                                    <p><strong>Amount:</strong> K<span id="review_amount"></span></p>
                                    <p><strong>Duration:</strong> <span id="review_duration"></span> weeks</p>
                                    <p><strong>Total Repayment:</strong> K<span id="review_total"></span></p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Collateral</h6>
                                    <p><strong>Item:</strong> <span id="review_collateral"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Next of Kin</h6>
                                    <p><strong>Name:</strong> <span id="review_kin_name"></span></p>
                                    <p><strong>Phone:</strong> <span id="review_kin_phone"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-success" type="button" id="confirmUpdateButton">
                                <i class="fas fa-check me-2"></i>Confirm Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Image Modal -->
            <div class="modal fade" role="dialog" tabindex="-1" id="currentImageModal">
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content image-modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-white" id="currentImageModalTitle">Current Image</h5>
                            <button class="btn-close btn-close-white" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img id="currentModalImage" src="" class="modal-image" alt="Current Image">
                        </div>
                        <div class="modal-footer border-0 justify-content-center">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        </div>
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
        // Wait for the document to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize interest calculation
            calculateInterest();
            
            // Tab navigation function
            window.switchToTab = function(tabNumber) {
                try {
                    const tabTrigger = document.querySelector(`a[href="#tab-${tabNumber}"]`);
                    if (tabTrigger) {
                        // Use Bootstrap's Tab API
                        const tab = new bootstrap.Tab(tabTrigger);
                        tab.show();
                    } else {
                        console.error(`Tab ${tabNumber} not found`);
                    }
                } catch (error) {
                    console.error('Error switching tabs:', error);
                }
            };

            // Image preview function
            window.previewImage = function(input, previewId) {
                const preview = document.getElementById(previewId);
                const file = input.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            };

            // View current image function
            window.viewCurrentImage = function(imageType) {
                let imageData = '';
                let title = '';
                
                <?php if (!empty($loan_data['user_id_image'])): ?>
                    if (imageType === 'id_image') {
                        imageData = 'data:image/jpeg;base64,<?php echo base64_encode($loan_data['user_id_image']); ?>';
                        title = 'Current ID Document';
                    }
                <?php endif; ?>
                
                <?php if (!empty($loan_data['image1'])): ?>
                    if (imageType === 'collateral1') {
                        imageData = 'data:image/jpeg;base64,<?php echo base64_encode($loan_data['image1']); ?>';
                        title = 'Current Collateral Image 1';
                    }
                <?php endif; ?>
                
                <?php if (!empty($loan_data['image2'])): ?>
                    if (imageType === 'collateral2') {
                        imageData = 'data:image/jpeg;base64,<?php echo base64_encode($loan_data['image2']); ?>';
                        title = 'Current Collateral Image 2';
                    }
                <?php endif; ?>
                
                if (imageData) {
                    document.getElementById('currentModalImage').src = imageData;
                    document.getElementById('currentImageModalTitle').textContent = title;
                    new bootstrap.Modal(document.getElementById('currentImageModal')).show();
                }
            };

            // Interest calculation function
            window.calculateInterest = function() {
                const loanAmount = parseFloat(document.getElementById('loan_amount').value) ;
                const duration = parseInt(document.getElementById('loan_duration').value) ;
                
                // Interest rate: 10% per week
                const interestRate = 0.1 * duration;
                const interestAmount = loanAmount * interestRate;
                const totalRepayment = loanAmount + interestAmount;
                
                document.getElementById('display_loan_amount').textContent = loanAmount.toFixed(2);
                document.getElementById('interest_amount').textContent = interestAmount.toFixed(2);
                document.getElementById('interest_percentage').textContent = (interestRate * 100).toFixed(0) + '%';
                document.getElementById('total_repayment').textContent = totalRepayment.toFixed(2);
            };

            // Review button click handler
            const reviewButton = document.getElementById('reviewButton');
            if (reviewButton) {
                reviewButton.addEventListener('click', function() {
                    // Collect form data for review
                    const firstName = document.getElementById('first_name').value;
                    const lastName = document.getElementById('last_name').value;
                    const phone = document.querySelector('input[name="phone"]').value;
                    const occupation = document.querySelector('select[name="occupation"]').value;
                    const collateralName = document.getElementById('collateral_name').value;
                    const kinFirstName = document.getElementById('kin_first_name').value;
                    const kinLastName = document.getElementById('kin_last_name').value;
                    const kinPhone = document.querySelector('input[name="kin_phone"]').value;
                    const loanAmount = document.getElementById('loan_amount').value;
                    const loanDuration = document.getElementById('loan_duration').value;
                    const totalRepayment = document.getElementById('total_repayment').textContent;
                    
                    // Populate review modal
                    document.getElementById('review_name').textContent = firstName + ' ' + lastName;
                    document.getElementById('review_phone').textContent = phone;
                    document.getElementById('review_occupation').textContent = occupation;
                    document.getElementById('review_collateral').textContent = collateralName;
                    document.getElementById('review_kin_name').textContent = kinFirstName + ' ' + kinLastName;
                    document.getElementById('review_kin_phone').textContent = kinPhone;
                    document.getElementById('review_amount').textContent = parseFloat(loanAmount).toFixed(2);
                    document.getElementById('review_duration').textContent = loanDuration;
                    document.getElementById('review_total').textContent = totalRepayment;
                    
                    // Show modal
                    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
                    reviewModal.show();
                });
            }
            
            // Confirm update button handler
            const confirmUpdateButton = document.getElementById('confirmUpdateButton');
            if (confirmUpdateButton) {
                confirmUpdateButton.addEventListener('click', function() {
                    // Submit the form
                    document.getElementById('realUpdateButton').click();
                });
            }

            console.log('Loan edit page JavaScript initialized successfully');
        });
    </script>
</body>
</html>