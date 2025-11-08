<?php 
require 'auth_client.php';
require '../db_connect.php';

// Get loan ID from URL parameter
$loan_id = $_GET['loan_id'] ?? null;

if (!$loan_id) {
    $_SESSION['error_message'] = "No loan ID provided.";
    header("Location: my_loans.php");
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

// Fetch existing loan data
try {
    $loan_sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.NRC, u.email, u.occupation, u.address, u.date_of_birth, u.nationality,
                        k.first_name as kin_first_name, k.last_name as kin_last_name, 
                        k.nrc_number as kin_nrc, k.phone as kin_phone
                 FROM loan l 
                 JOIN user_table u ON l.user_id = u.user_id 
                 LEFT JOIN kin k ON l.loan_id = k.loan_id 
                 WHERE l.loan_id = ? AND l.user_id = ? AND l.status = 'pending'";
    $loan_stmt = $pdo->prepare($loan_sql);
    $loan_stmt->execute([$loan_id, $user_id]);
    $loan = $loan_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        $_SESSION['error_message'] = "Loan not found, already processed, or you don't have permission to edit this loan.";
        header("Location: my_loans.php");
        exit;
    }
    
} catch (PDOException $e) {
    die("Error fetching loan details: " . $e->getMessage());
}

// Handle form submission for updating loan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_loan'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update user information
        $user_sql = "UPDATE user_table SET 
                    first_name = ?, last_name = ?, phone = ?, NRC = ?, occupation = ?, 
                    address = ?, date_of_birth = ?, nationality = ? 
                    WHERE user_id = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            $_POST['nrc'],
            $_POST['occupation'],
            $_POST['address'],
            $_POST['date_of_birth'],
            $_POST['nationality'],
            $user_id
        ]);
        
        // Calculate new end date based on duration
        $new_duration = $_POST['loan_duration'];
        $start_date = $loan['loan_start_date']; // Use original start date
        $new_end_date = date('Y-m-d', strtotime($start_date . ' + ' . $new_duration . ' weeks'));
        
        // Calculate interest at 10% per week
        $weekly_interest_rate = 0.10; // 10% per week
        $total_interest_rate = $weekly_interest_rate * $new_duration;
        $interest_amount = $_POST['loan_amount'] * $total_interest_rate;
        
        // Update loan information including end date
        $loan_sql = "UPDATE loan SET 
                    amount = ?, duration = ?, interest = ?, collateral_name = ?, loan_end_date = ? 
                    WHERE loan_id = ? AND user_id = ?";
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([
            $_POST['loan_amount'],
            $new_duration,
            $interest_amount,
            $_POST['collateral_name'],
            $new_end_date,
            $loan_id,
            $user_id
        ]);
        
        // Update next of kin information
        $kin_sql = "UPDATE kin SET 
                   first_name = ?, last_name = ?, nrc_number = ?, phone = ? 
                   WHERE loan_id = ?";
        $kin_stmt = $pdo->prepare($kin_sql);
        $kin_stmt->execute([
            $_POST['kin_first_name'],
            $_POST['kin_last_name'],
            $_POST['kin_nrc'],
            $_POST['kin_phone'],
            $loan_id
        ]);
        
        // Handle file uploads if new files are provided
        if (!empty($_FILES['id_image']['name'])) {
            $id_image = file_get_contents($_FILES['id_image']['tmp_name']);
            $update_image_sql = "UPDATE loan SET user_id_image = ? WHERE loan_id = ?";
            $update_image_stmt = $pdo->prepare($update_image_sql);
            $update_image_stmt->execute([$id_image, $loan_id]);
        }
        
        if (!empty($_FILES['collateral_image1']['name'])) {
            $image1 = file_get_contents($_FILES['collateral_image1']['tmp_name']);
            $update_image1_sql = "UPDATE loan SET image1 = ? WHERE loan_id = ?";
            $update_image1_stmt = $pdo->prepare($update_image1_sql);
            $update_image1_stmt->execute([$image1, $loan_id]);
        }
        
        if (!empty($_FILES['collateral_image2']['name'])) {
            $image2 = file_get_contents($_FILES['collateral_image2']['tmp_name']);
            $update_image2_sql = "UPDATE loan SET image2 = ? WHERE loan_id = ?";
            $update_image2_stmt = $pdo->prepare($update_image2_sql);
            $update_image2_stmt->execute([$image2, $loan_id]);
        }
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Loan application updated successfully!";
        header("Location: loan_details.php?loan_id=" . $loan_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update loan application: " . $e->getMessage();
    }
}

// Calculate interest for pre-filled values using 10% per week
$weekly_interest_rate = 0.10;
$total_interest_rate = $weekly_interest_rate * $loan['duration'];
$calculated_interest = $loan['amount'] * $total_interest_rate;
$interest_percentage = $total_interest_rate * 100;
$total_repayment = $loan['amount'] + $calculated_interest;
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Edit Loan Application - <?php echo $loan['loan_number']; ?></title>
    <meta name="description" content="Edit Loan Application">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <link rel="stylesheet" href="assets/css/apply_loan.css">
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
                        <p style="text-align:center;"><h3 class="text-dark mb-0"><strong>EDIT LOAN APPLICATION</strong></h3></p>
                    </div>

                    <!-- Main form that collects all data -->
                    <form method="POST" action="" class="user" id="loanApplicationForm" enctype="multipart/form-data">
                        <input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
                        
                        <div class="row">
                            <div class="col-12">
                                <ul style="display:none;" class="nav nav-tabs" role="tablist">
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
                                                    <i class="fas fa-edit me-2"></i>
                                                    <strong>Editing Mode:</strong> You are currently editing your loan application. All fields are pre-filled with your existing information.
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">First Name</label>
                                                            <input class="form-control form-control-user" type="text" id="first_name" placeholder="Enter First Name" name="first_name" value="<?php echo htmlspecialchars($loan['first_name']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input class="form-control form-control-user" type="text" id="last_name" placeholder="Enter Last Name" name="last_name" value="<?php echo htmlspecialchars($loan['last_name']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input class="form-control form-control-user" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($loan['date_of_birth']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Occupation</label>
                                                            <select class="form-select form-control-user" name="occupation" required>
                                                                <option value="" disabled>Select Occupation</option>
                                                                <option value="student" <?php echo ($loan['occupation'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                                                <option value="worker" <?php echo ($loan['occupation'] == 'worker') ? 'selected' : ''; ?>>Worker</option>
                                                                <option value="unemployed" <?php echo ($loan['occupation'] == 'unemployed') ? 'selected' : ''; ?>>Unemployed</option>
                                                                <option value="entreprenuer" <?php echo ($loan['occupation'] == 'entreprenuer') ? 'selected' : ''; ?>>Entrepreneur</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">NRC Number</label>
                                                            <input class="form-control form-control-user" type="text" name="nrc" placeholder="Enter NRC Number" value="<?php echo htmlspecialchars($loan['NRC']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nationality</label>
                                                            <select class="form-select form-control-user" name="nationality" required>
                                                                <option value="" disabled>Select Nationality</option>
                                                                <option value="Zambian" <?php echo ($loan['nationality'] == 'Zambian') ? 'selected' : ''; ?>>Zambian</option>
                                                                <option value="Other" <?php echo ($loan['nationality'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control form-control-user" type="text" placeholder="Enter Phone Number" name="phone" value="<?php echo htmlspecialchars($loan['phone']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Address</label>
                                                            <input class="form-control form-control-user" type="text" placeholder="Enter Address" name="address" value="<?php echo htmlspecialchars($loan['address']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label class="form-label">Upload ID/NRC Document (Leave empty to keep current)</label>
                                                            <input class="form-control" type="file" name="id_image" accept="image/*" onchange="previewImage(this, 'id_preview')">
                                                            <small class="form-text text-muted">Current document will be kept if no new file is selected</small>
                                                            <?php if ($loan['user_id_image']): ?>
                                                                <div class="mt-2">
                                                                    <span class="badge bg-info">Current document available</span>
                                                                </div>
                                                            <?php endif; ?>
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
                                                            <input class="form-control form-control-user" type="text" id="collateral_name" placeholder="Enter collateral item name" name="collateral_name" value="<?php echo htmlspecialchars($loan['collateral_name']); ?>" required>
                                                            <small class="form-text text-muted">Describe the item you're using as collateral</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 1 (Leave empty to keep current)</label>
                                                            <input class="form-control" type="file" name="collateral_image1" accept="image/*" onchange="previewImage(this, 'image1_preview')">
                                                            <?php if ($loan['image1']): ?>
                                                                <div class="mt-2">
                                                                    <span class="badge bg-info">Current image available</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Collateral Image 2 (Leave empty to keep current)</label>
                                                            <input class="form-control" type="file" name="collateral_image2" accept="image/*" onchange="previewImage(this, 'image2_preview')">
                                                            <?php if ($loan['image2']): ?>
                                                                <div class="mt-2">
                                                                    <span class="badge bg-info">Current image available</span>
                                                                </div>
                                                            <?php endif; ?>
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
                                                            <input class="form-control form-control-user" type="text" id="kin_first_name" placeholder="Enter First Name" name="kin_first_name" value="<?php echo htmlspecialchars($loan['kin_first_name']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_last_name" placeholder="Enter Last Name" name="kin_last_name" value="<?php echo htmlspecialchars($loan['kin_last_name']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">NRC Number</label>
                                                            <input class="form-control form-control-user" type="text" id="kin_nrc" placeholder="Enter NRC Number" name="kin_nrc" value="<?php echo htmlspecialchars($loan['kin_nrc']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input class="form-control form-control-user" type="text" name="kin_phone" placeholder="Enter Phone Number" value="<?php echo htmlspecialchars($loan['kin_phone']); ?>" required>
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
                                                            <input class="form-control form-control-user" type="number" max="5000" min="500" name="loan_amount" placeholder="Enter amount (K500 - K5000)" value="<?php echo $loan['amount']; ?>" required id="loan_amount" oninput="calculateInterest()">
                                                            <small class="form-text text-muted">Amount must be between K500 and K5000</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Loan Duration</label>
                                                            <select class="form-select form-control-user" name="loan_duration" required id="loan_duration" onchange="calculateInterest()">
                                                                <option value="" disabled>Select Duration</option>
                                                                <option value="1" <?php echo ($loan['duration'] == 1) ? 'selected' : ''; ?>>1 Week</option>
                                                                <option value="2" <?php echo ($loan['duration'] == 2) ? 'selected' : ''; ?>>2 Weeks</option>
                                                                <option value="3" <?php echo ($loan['duration'] == 3) ? 'selected' : ''; ?>>3 Weeks</option>
                                                                <option value="4" <?php echo ($loan['duration'] == 4) ? 'selected' : ''; ?>>4 Weeks</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Interest Rate Information -->
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Interest Rate:</strong> 10% per week. 
                                                    <span id="interest_rate_display">Total interest rate: <?php echo number_format($interest_percentage, 1); ?>%</span>
                                                </div>
                                                
                                                <!-- Date Information Display -->
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="card bg-light">
                                                            <div class="card-body">
                                                                <h6 class="card-title">Current Dates</h6>
                                                                <p class="mb-1"><strong>Start Date:</strong> <?php echo $loan['loan_start_date']; ?></p>
                                                                <p class="mb-0"><strong>Current End Date:</strong> <?php echo $loan['loan_end_date']; ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card bg-light">
                                                            <div class="card-body">
                                                                <h6 class="card-title">New End Date</h6>
                                                                <p class="mb-0"><strong id="new_end_date_display"><?php echo $loan['loan_end_date']; ?></strong></p>
                                                                <small class="text-muted">This will be updated based on the duration selected</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-4">
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Loan Amount</h6>
                                                                <h4 class="text-primary">K<span id="display_loan_amount"><?php echo number_format($loan['amount'], 2); ?></span></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Interest</h6>
                                                                <h4 class="text-warning">K<span id="interest_amount"><?php echo number_format($calculated_interest, 2); ?></span></h4>
                                                                <small id="interest_percentage"><?php echo number_format($interest_percentage, 1); ?>%</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h6 class="card-title">Total Repayment</h6>
                                                                <h4 class="text-success">K<span id="total_repayment"><?php echo number_format($total_repayment, 2); ?></span></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Hidden field for interest amount -->
                                                <input type="hidden" name="interest_amount" id="hidden_interest_amount" value="<?php echo $calculated_interest; ?>">
                                                
                                                <div class="alert alert-warning mt-4">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Important:</strong> By updating this application, you agree to the terms and conditions of the loan. The loan end date will be recalculated based on the new duration.
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="button" class="btn btn-secondary me-3" onclick="prevTab(3)">
                                                        <i class="fas fa-arrow-left me-2"></i>Back
                                                    </button>
                                                    <button type="submit" name="update_loan" class="btn btn-success px-4">
                                                        <i class="fas fa-save me-2"></i>Update Application
                                                    </button>
                                                    <a href="loan_details.php?loan_id=<?php echo $loan_id; ?>" class="btn btn-outline-secondary ms-2">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </a>
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
        // Tab navigation functions
        function nextTab(tabNumber) {
            document.querySelectorAll('.nav-tabs .nav-link')[tabNumber - 1].click();
        }
        
        function prevTab(tabNumber) {
            document.querySelectorAll('.nav-tabs .nav-link')[tabNumber - 1].click();
        }
        
        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.style.maxHeight = '200px';
                preview.style.maxWidth = '100%';
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        // Calculate new end date based on duration
        function calculateNewEndDate(duration) {
            const startDate = '<?php echo $loan['loan_start_date']; ?>';
            if (!startDate) return '';
            
            const start = new Date(startDate);
            const newEndDate = new Date(start);
            newEndDate.setDate(start.getDate() + (duration * 7)); // Add weeks as days
            
            return newEndDate.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        }
        
        // Format date for display
        function formatDateForDisplay(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Interest calculation function - 10% per week
        function calculateInterest() {
            const loanAmount = parseFloat(document.getElementById('loan_amount').value) || 0;
            const duration = parseInt(document.getElementById('loan_duration').value) || 0;
            
            // Fixed interest rate of 10% per week
            const weeklyInterestRate = 0.10; // 10% per week
            const totalInterestRate = weeklyInterestRate * duration;
            const interestAmount = loanAmount * totalInterestRate;
            const totalRepayment = loanAmount + interestAmount;
            const interestPercentage = totalInterestRate * 100;
            
            // Update interest rate display
            document.getElementById('interest_rate_display').textContent = 'Total interest rate: ' + interestPercentage.toFixed(1) + '%';
            
            // Calculate and display new end date
            if (duration > 0) {
                const newEndDate = calculateNewEndDate(duration);
                if (newEndDate) {
                    document.getElementById('new_end_date_display').textContent = formatDateForDisplay(newEndDate);
                }
            }
            
            // Update display
            document.getElementById('display_loan_amount').textContent = loanAmount.toFixed(2);
            document.getElementById('interest_amount').textContent = interestAmount.toFixed(2);
            document.getElementById('interest_percentage').textContent = interestPercentage.toFixed(1) + '%';
            document.getElementById('total_repayment').textContent = totalRepayment.toFixed(2);
            
            // Update hidden field
            document.getElementById('hidden_interest_amount').value = interestAmount.toFixed(2);
        }
        
        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateInterest();
        });
    </script>
</body>
</html>