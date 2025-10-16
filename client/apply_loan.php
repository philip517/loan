<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Check if we're editing an existing loan
$edit_loan_id = $_GET['edit'] ?? null;
$existing_loan_data = null;
$existing_kin_data = null;
$is_editing = false;

if ($edit_loan_id) {
    $is_editing = true;
    
    // Fetch existing loan data
    $loan_sql = "SELECT l.*, k.first_name as kin_first_name, k.last_name as kin_last_name, 
                        k.nrc_number as kin_nrc, k.phone as kin_phone
                 FROM loan l 
                 LEFT JOIN kin k ON l.loan_id = k.loan_id 
                 WHERE l.loan_id = ? AND l.user_id = ?";
    $loan_stmt = $pdo->prepare($loan_sql);
    $loan_stmt->execute([$edit_loan_id, $user_id]);
    $existing_loan_data = $loan_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_loan_data) {
        $_SESSION['error_message'] = "Loan not found or you don't have permission to edit it.";
        header("Location: loan.php");
        exit;
    }
    
    // Check if loan status allows editing (only pending loans can be edited)
    if ($existing_loan_data['status'] !== 'pending') {
        $_SESSION['error_message'] = "Only pending loans can be edited.";
        header("Location: loan.php");
        exit;
    }
}

// Fetch user data from database
$user_sql = "SELECT first_name, last_name, phone, NRC, email, occupation, address, date_of_birth, nationality 
             FROM user_table 
             WHERE user_id = ?";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Set default values - use existing loan data if editing, otherwise use user profile data
if ($is_editing && $existing_loan_data) {
    $first_name = $existing_loan_data['first_name'] ?? $user_data['first_name'] ?? '';
    $last_name = $existing_loan_data['last_name'] ?? $user_data['last_name'] ?? '';
    $phone = $existing_loan_data['phone'] ?? $user_data['phone'] ?? '';
    $nrc = $existing_loan_data['NRC'] ?? $user_data['NRC'] ?? '';
    $occupation = $existing_loan_data['occupation'] ?? $user_data['occupation'] ?? '';
    $address = $existing_loan_data['address'] ?? $user_data['address'] ?? '';
    $date_of_birth = $existing_loan_data['date_of_birth'] ?? $user_data['date_of_birth'] ?? '';
    $nationality = $existing_loan_data['nationality'] ?? $user_data['nationality'] ?? '';
    
    // Loan specific data
    $collateral_name = $existing_loan_data['collateral_name'] ?? '';
    $loan_amount = $existing_loan_data['amount'] ?? '';
    $loan_duration = $existing_loan_data['duration'] ?? '';
    
    // Kin data
    $kin_first_name = $existing_loan_data['kin_first_name'] ?? '';
    $kin_last_name = $existing_loan_data['kin_last_name'] ?? '';
    $kin_nrc = $existing_loan_data['kin_nrc'] ?? '';
    $kin_phone = $existing_loan_data['kin_phone'] ?? '';
} else {
    // New loan application - use user profile data
    $first_name = $user_data['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? '';
    $phone = $user_data['phone'] ?? '';
    $nrc = $user_data['NRC'] ?? '';
    $occupation = $user_data['occupation'] ?? '';
    $address = $user_data['address'] ?? '';
    $date_of_birth = $user_data['date_of_birth'] ?? '';
    $nationality = $user_data['nationality'] ?? '';
    
    // Loan specific data - empty for new applications
    $collateral_name = '';
    $loan_amount = '';
    $loan_duration = '';
    
    // Kin data - empty for new applications
    $kin_first_name = '';
    $kin_last_name = '';
    $kin_nrc = '';
    $kin_phone = '';
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo $is_editing ? 'Edit Loan Application' : 'Loan Application'; ?></title>
    <meta name="description" content="Loan Application page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .card-header {
            font-weight: 600;
        }
        .form-control-user {
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .preview-image {
            max-height: 150px;
            max-width: 100%;
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }
        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }
        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }
        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }
        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }
        .auto-fill-badge {
            font-size: 0.7rem;
            margin-left: 5px;
        }
        .edit-badge {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        
       <?php require 'navbar.php' ?>

        <div class="container-fluid" style="margin-top: 100px;">
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

            <!-- Main form that collects all data -->
            <form method="POST" action="confirm_loan.php" class="user" id="loanApplicationForm" enctype="multipart/form-data">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="edit_loan_id" value="<?php echo $edit_loan_id; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="d-sm-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-dark mb-0">
                                <strong>
                                    <?php echo $is_editing ? 'EDIT LOAN APPLICATION' : 'LOAN APPLICATION'; ?>
                                    <?php if ($is_editing): ?>
                                        <span class="badge edit-badge ms-2">EDITING</span>
                                    <?php endif; ?>
                                </strong>
                            </h3>
                            <div class="alert alert-info d-flex align-items-center py-2" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>
                                    <?php if ($is_editing): ?>
                                        You are editing your pending loan application. All changes will be reviewed again.
                                    <?php else: ?>
                                        Personal information has been auto-filled from your profile
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        
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
                                        <div class="alert alert-info mb-4">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            <strong>Tip:</strong> 
                                            <?php if ($is_editing): ?>
                                                You are editing your loan application. Update any information that has changed.
                                            <?php else: ?>
                                                Your personal information has been pre-filled from your profile. You can update it if needed.
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        First Name 
                                                        <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                    </label>
                                                    <input class="form-control form-control-user" type="text" id="first_name" placeholder="Enter First Name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Last Name 
                                                        <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                    </label>
                                                    <input class="form-control form-control-user" type="text" id="last_name" placeholder="Enter Last Name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Date of Birth
                                                        <?php if (!empty($date_of_birth)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <input class="form-control form-control-user" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Occupation
                                                        <?php if (!empty($occupation)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
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
                                                    <label class="form-label">
                                                        NRC Number
                                                        <?php if (!empty($nrc)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <input class="form-control form-control-user" type="text" name="nrc" placeholder="Enter NRC Number" value="<?php echo htmlspecialchars($nrc); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Nationality
                                                        <?php if (!empty($nationality)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
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
                                                    <label class="form-label">
                                                        Phone Number
                                                        <?php if (!empty($phone)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <input class="form-control form-control-user" type="text" placeholder="Enter Phone Number" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Address
                                                        <?php if (!empty($address)): ?>
                                                            <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <input class="form-control form-control-user" type="text" placeholder="Enter Address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label">Upload ID/NRC Document</label>
                                                    <input class="form-control" type="file" name="id_image" accept="image/*" <?php echo $is_editing ? '' : 'required'; ?>>
                                                    <small class="form-text text-muted">
                                                        <?php if ($is_editing): ?>
                                                            Upload a new ID image if you want to change it. Leave empty to keep the current one.
                                                        <?php else: ?>
                                                            Upload a clear image of your ID or NRC document
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-4">
                                            <button type="button" class="btn btn-primary px-4" onclick="nextTab(2)">
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
                                                    <input class="form-control" type="file" name="collateral_image1" accept="image/*" <?php echo $is_editing ? '' : 'required'; ?> onchange="previewImage(this, 'image1_preview')">
                                                    <small class="form-text text-muted">
                                                        <?php if ($is_editing): ?>
                                                            Upload a new image if you want to change it. Leave empty to keep the current one.
                                                        <?php endif; ?>
                                                    </small>
                                                    <div class="mt-2 text-center">
                                                        <img id="image1_preview" class="preview-image" src="" style="display: none;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Collateral Image 2</label>
                                                    <input class="form-control" type="file" name="collateral_image2" accept="image/*" <?php echo $is_editing ? '' : 'required'; ?> onchange="previewImage(this, 'image2_preview')">
                                                    <small class="form-text text-muted">
                                                        <?php if ($is_editing): ?>
                                                            Upload a new image if you want to change it. Leave empty to keep the current one.
                                                        <?php endif; ?>
                                                    </small>
                                                    <div class="mt-2 text-center">
                                                        <img id="image2_preview" class="preview-image" src="" style="display: none;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-4">
                                            <button type="button" class="btn btn-secondary me-3" onclick="prevTab(1)">
                                                <i class="fas fa-arrow-left me-2"></i>Back
                                            </button>
                                            <button type="button" class="btn btn-primary px-4" onclick="nextTab(3)">
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
                                            <button type="button" class="btn btn-secondary me-3" onclick="prevTab(2)">
                                                <i class="fas fa-arrow-left me-2"></i>Back
                                            </button>
                                            <button type="button" class="btn btn-primary px-4" onclick="nextTab(4)">
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
                                                        <h4 class="text-success">K<span id="total_repayment"><?php echo number_format($loan_amount, 2); ?></span></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning mt-4">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Important:</strong> 
                                            <?php if ($is_editing): ?>
                                                Editing your loan application will reset its status to "pending" and require re-approval.
                                            <?php else: ?>
                                                By submitting this application, you agree to the terms and conditions of the loan.
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-center mt-4">
                                            <button type="button" class="btn btn-secondary me-3" onclick="prevTab(3)">
                                                <i class="fas fa-arrow-left me-2"></i>Back
                                            </button>
                                            <button type="submit" class="btn btn-success px-4" name="<?php echo $is_editing ? 'update_loan' : 'submit_loan'; ?>">
                                                <i class="fas fa-check me-2"></i>
                                                <?php echo $is_editing ? 'Update Loan Application' : 'Submit Loan Application'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Tab navigation functions
        function nextTab(tabNumber) {
            const nextTab = new bootstrap.Tab(document.querySelector(`a[href="#tab-${tabNumber}"]`));
            nextTab.show();
        }
        
        function prevTab(tabNumber) {
            const prevTab = new bootstrap.Tab(document.querySelector(`a[href="#tab-${tabNumber}"]`));
            prevTab.show();
        }
        
        // Image preview function
        function previewImage(input, previewId) {
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
        }
        
        // Interest calculation function
        function calculateInterest() {
            const loanAmount = parseFloat(document.getElementById('loan_amount').value) || 0;
            const duration = parseInt(document.getElementById('loan_duration').value) || 0;
            
            // Interest rate: 10% per week
            const interestRate = 0.10;
            const interest = loanAmount * interestRate * duration;
            const totalRepayment = loanAmount + interest;
            
            // Update display
            document.getElementById('display_loan_amount').textContent = loanAmount.toFixed(2);
            document.getElementById('interest_amount').textContent = interest.toFixed(2);
            document.getElementById('total_repayment').textContent = totalRepayment.toFixed(2);
            
            // Calculate and display interest percentage
            const interestPercentage = loanAmount > 0 ? (interest / loanAmount) * 100 : 0;
            document.getElementById('interest_percentage').textContent = interestPercentage.toFixed(1) + '%';
        }
        
        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateInterest();
        });
    </script>
</body>
</html>