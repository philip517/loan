<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch user data from database
$user_sql = "SELECT first_name, last_name, phone, NRC, email, occupation, address, date_of_birth, nationality 
             FROM user_table 
             WHERE user_id = ?";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Set default values from user profile
$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$phone = $user_data['phone'] ?? '';
$nrc = $user_data['NRC'] ?? '';
$occupation = $user_data['occupation'] ?? '';
$address = $user_data['address'] ?? '';
$date_of_birth = $user_data['date_of_birth'] ?? '';
$nationality = $user_data['nationality'] ?? '';
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Application</title>
    <meta name="description" content="Loan Application page">
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
    
    /* Two-Row, Two-Column Tab Layout */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        padding: 10px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto;
        gap: 8px;
    }
    
    .nav-tabs .nav-item {
        margin-bottom: 0;
        display: flex;
    }
    
    .nav-tabs .nav-link {
        color: #495057 !important;
        font-weight: 600;
        padding: 12px 15px;
        border: 1px solid #dee2e6;
        border-bottom: none;
        background-color: #e9ecef;
        border-radius: 6px 6px 0 0;
        transition: all 0.3s ease;
        white-space: nowrap;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        min-height: 60px;
    }
    
    .nav-tabs .nav-link.active {
        font-weight: 700;
        color: #0056b3 !important;
        background-color: #ffffff;
        border: 2px solid #007bff;
        border-bottom: 3px solid #007bff;
        box-shadow: 0 2px 4px rgba(0,123,255,0.2);
        transform: translateY(-1px);
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
        color: #007bff !important;
        background-color: #ffffff;
        border-color: #007bff;
        transform: translateY(-2px);
    }
    
    .nav-tabs .nav-link i {
        color: inherit !important;
        margin-right: 8px;
        font-size: 1.1em;
        flex-shrink: 0;
    }
    
    .nav-tabs .nav-link span {
        flex: 1;
        text-align: center;
    }
    
    .tab-content {
        padding: 25px 0;
        background: #ffffff;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .nav-tabs {
            gap: 6px;
            padding: 8px;
        }
        
        .nav-tabs .nav-link {
            padding: 10px 12px;
            font-size: 0.9rem;
            min-height: 55px;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 6px;
            font-size: 1em;
        }
        
        .tab-content {
            padding: 20px 0;
        }
    }

    @media (max-width: 576px) {
        .nav-tabs {
            gap: 4px;
            padding: 6px;
        }
        
        .nav-tabs .nav-link {
            padding: 8px 10px;
            font-size: 0.85rem;
            min-height: 50px;
            flex-direction: column;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 0;
            margin-bottom: 4px;
            font-size: 0.9em;
        }
        
        .nav-tabs .nav-link span {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 400px) {
        .nav-tabs {
            grid-template-columns: 1fr;
            grid-template-rows: repeat(4, auto);
        }
        
        .nav-tabs .nav-link {
            min-height: 45px;
            flex-direction: row;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 8px;
            margin-bottom: 0;
        }
    }

    /* Ensure proper tab order in grid */
    .nav-tabs .nav-item:nth-child(1) { grid-column: 1; grid-row: 1; }
    .nav-tabs .nav-item:nth-child(2) { grid-column: 2; grid-row: 1; }
    .nav-tabs .nav-item:nth-child(3) { grid-column: 1; grid-row: 2; }
    .nav-tabs .nav-item:nth-child(4) { grid-column: 2; grid-row: 2; }
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
                        <h3 class="text-dark mb-0"><strong>LOAN APPLICATION</strong></h3>
                    </div>

                    <!-- Main form that collects all data -->
                    <form method="POST" action="confirm_loan.php" class="user" id="loanApplicationForm" enctype="multipart/form-data">
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
                                                    <strong>Tip:</strong> Your personal information has been pre-filled from your profile. You can update it if needed.
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                First Name 
                                                                <?php if (!empty($first_name)): ?>
                                                                    <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                                <?php endif; ?>
                                                            </label>
                                                            <input class="form-control form-control-user" type="text" id="first_name" placeholder="Enter First Name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                Last Name 
                                                                <?php if (!empty($last_name)): ?>
                                                                    <span class="badge bg-success auto-fill-badge">Auto-filled</span>
                                                                <?php endif; ?>
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
                                                            <input class="form-control" type="file" name="id_image" accept="image/*" required onchange="previewImage(this, 'id_preview')">
                                                            <small class="form-text text-muted">Upload a clear image of your ID or NRC document</small>
                                                            <div class="mt-2 text-center">
                                                                <img id="id_preview" class="preview-image" src="" style="display: none;">
                                                            </div>
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
                                                            <input class="form-control form-control-user" type="text" id="collateral_name" placeholder="Enter collateral item name" name="collateral_name" required>
                                                            <small class="form-text text-muted">Describe the item you're using as collateral</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 1</label>
                                                            <input class="form-control" type="file" name="collateral_image1" accept="image/*" required onchange="previewImage(this, 'image1_preview')">
                                                            <div class="mt-2 text-center">
                                                                <img id="image1_preview" class="preview-image" src="" style="display: none;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 2</label>
                                                            <input class="form-control" type="file" name="collateral_image2" accept="image/*" required onchange="previewImage(this, 'image2_preview')">
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
                                                            <input class="form-control form-control-user" type="text" id="kin_first_name" placeholder="Enter First Name" name="kin_first_name" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_last_name" placeholder="Enter Last Name" name="kin_last_name" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">NRC Number</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_nrc" placeholder="Enter NRC Number" name="kin_nrc" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control form-control-user" type="text" name="kin_phone" placeholder="Enter Phone Number" required>
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
                                                            <input class="form-control form-control-user" type="number" max="5000" min="500" name="loan_amount" placeholder="Enter amount (K500 - K5000)" required id="loan_amount" oninput="calculateInterest()">
                                                            <small class="form-text text-muted">Amount must be between K500 and K5000</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Loan Duration</label>
                                                            <select class="form-select form-control-user" name="loan_duration" required id="loan_duration" onchange="calculateInterest()">
                                                                <option value="" disabled selected>Select Duration</option>
                                                                <option value="1">1 Week</option>
                                                                <option value="2">2 Weeks</option>
                                                                <option value="3">3 Weeks</option>
                                                                <option value="4">4 Weeks</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Loan Amount</h6>
                                                                <h4 class="text-primary">K<span id="display_loan_amount">0.00</span></h4>
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
                                                    <strong>Important:</strong> By submitting this application, you agree to the terms and conditions of the loan.
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-secondary me-3" onclick="prevTab(3)">
                                                        <i class="fas fa-arrow-left me-2"></i>Back
                                                    </button>
                                                    <button type="button" class="btn btn-success px-4" id="reviewButton">
                                                        <i class="fas fa-eye me-2"></i>Review Application
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden submit button for the form -->
                        <input type="submit" id="realApplyButton" style="display: none;">
                    </form>
                </div>
            </div>

            <!-- Review Modal -->
            <?php include 'loan_review_modal.php'; ?>

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
    <script src="assets/js/apply_loan.js"></script>
    <script src="assets/js/review.js"></script>
</body>
</html>







