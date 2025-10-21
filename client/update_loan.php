<?php
// update_loan.php
require 'auth_client.php';
require '../db_connect.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get loan ID from form
    $loan_id = $_POST['loan_id'] ?? null;
    
    if (!$loan_id) {
        $_SESSION['error_message'] = "No loan specified for update.";
        header("Location: my_loans.php");
        exit;
    }
    
    // Verify the loan belongs to the user
    $verify_sql = "SELECT * FROM loan_applications WHERE loan_id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([$loan_id, $user_id]);
    $existing_loan = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_loan) {
        $_SESSION['error_message'] = "Loan not found or you don't have permission to edit it.";
        header("Location: my_loans.php");
        exit;
    }
    
    // Collect form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $nrc = $_POST['nrc'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $collateral_name = $_POST['collateral_name'] ?? '';
    $kin_first_name = $_POST['kin_first_name'] ?? '';
    $kin_last_name = $_POST['kin_last_name'] ?? '';
    $kin_nrc = $_POST['kin_nrc'] ?? '';
    $kin_phone = $_POST['kin_phone'] ?? '';
    $loan_amount = $_POST['loan_amount'] ?? '';
    $loan_duration = $_POST['loan_duration'] ?? '';
    
    // File upload handling
    $upload_dir = "../uploads/loans/";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Handle ID image upload
    $id_image_path = $existing_loan['id_image_path'];
    if (!empty($_FILES['id_image']['name'])) {
        $id_image_name = "id_" . $user_id . "_" . time() . "_" . basename($_FILES['id_image']['name']);
        $id_image_target = $upload_dir . $id_image_name;
        
        if (move_uploaded_file($_FILES['id_image']['tmp_name'], $id_image_target)) {
            $id_image_path = $id_image_target;
        }
    }
    
    // Handle collateral image 1 upload
    $collateral_image1_path = $existing_loan['collateral_image1_path'];
    if (!empty($_FILES['collateral_image1']['name'])) {
        $collateral_image1_name = "collateral1_" . $user_id . "_" . time() . "_" . basename($_FILES['collateral_image1']['name']);
        $collateral_image1_target = $upload_dir . $collateral_image1_name;
        
        if (move_uploaded_file($_FILES['collateral_image1']['tmp_name'], $collateral_image1_target)) {
            $collateral_image1_path = $collateral_image1_target;
        }
    }
    
    // Handle collateral image 2 upload
    $collateral_image2_path = $existing_loan['collateral_image2_path'];
    if (!empty($_FILES['collateral_image2']['name'])) {
        $collateral_image2_name = "collateral2_" . $user_id . "_" . time() . "_" . basename($_FILES['collateral_image2']['name']);
        $collateral_image2_target = $upload_dir . $collateral_image2_name;
        
        if (move_uploaded_file($_FILES['collateral_image2']['tmp_name'], $collateral_image2_target)) {
            $collateral_image2_path = $collateral_image2_target;
        }
    }
    
    try {
        // Update loan application in database
        $update_sql = "UPDATE loan_applications SET 
                        first_name = ?, last_name = ?, date_of_birth = ?, occupation = ?, 
                        nrc = ?, nationality = ?, phone = ?, address = ?, 
                        collateral_name = ?, kin_first_name = ?, kin_last_name = ?, 
                        kin_nrc = ?, kin_phone = ?, loan_amount = ?, loan_duration = ?,
                        id_image_path = ?, collateral_image1_path = ?, collateral_image2_path = ?,
                        updated_at = NOW()
                        WHERE loan_id = ? AND user_id = ?";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            $first_name, $last_name, $date_of_birth, $occupation,
            $nrc, $nationality, $phone, $address,
            $collateral_name, $kin_first_name, $kin_last_name,
            $kin_nrc, $kin_phone, $loan_amount, $loan_duration,
            $id_image_path, $collateral_image1_path, $collateral_image2_path,
            $loan_id, $user_id
        ]);
        
        // Update user profile information
        $update_user_sql = "UPDATE user_table SET 
                            first_name = ?, last_name = ?, phone = ?, NRC = ?, 
                            occupation = ?, address = ?, date_of_birth = ?, nationality = ?,
                            updated_at = NOW()
                            WHERE user_id = ?";
        
        $update_user_stmt = $pdo->prepare($update_user_sql);
        $update_user_stmt->execute([
            $first_name, $last_name, $phone, $nrc,
            $occupation, $address, $date_of_birth, $nationality,
            $user_id
        ]);
        
        $_SESSION['success_message'] = "Loan application updated successfully!";
        header("Location: my_loans.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating loan application: " . $e->getMessage();
        header("Location: edit_loan.php?loan_id=" . $loan_id);
        exit;
    }
} else {
    header("Location: my_loans.php");
    exit;
}
?>