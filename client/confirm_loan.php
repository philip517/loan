<?php
require 'auth_client.php';
require '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Check if we're editing or creating new
    $is_editing = isset($_POST['edit_loan_id']);
    $edit_loan_id = $_POST['edit_loan_id'] ?? null;
    
    try {
        // Handle file uploads
        $id_image = null;
        $collateral_image1 = null;
        $collateral_image2 = null;
        
        // Upload ID image
        if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $id_image = file_get_contents($_FILES['id_image']['tmp_name']);
        } elseif ($is_editing) {
            // If editing and no new image uploaded, keep the existing one
            $existing_sql = "SELECT user_id_image FROM loan WHERE loan_id = ?";
            $existing_stmt = $pdo->prepare($existing_sql);
            $existing_stmt->execute([$edit_loan_id]);
            $existing_data = $existing_stmt->fetch(PDO::FETCH_ASSOC);
            $id_image = $existing_data['user_id_image'] ?? null;
        }
        
        // Upload collateral image 1
        if (isset($_FILES['collateral_image1']) && $_FILES['collateral_image1']['error'] === UPLOAD_ERR_OK) {
            $collateral_image1 = file_get_contents($_FILES['collateral_image1']['tmp_name']);
        } elseif ($is_editing) {
            $existing_sql = "SELECT image1 FROM loan WHERE loan_id = ?";
            $existing_stmt = $pdo->prepare($existing_sql);
            $existing_stmt->execute([$edit_loan_id]);
            $existing_data = $existing_stmt->fetch(PDO::FETCH_ASSOC);
            $collateral_image1 = $existing_data['image1'] ?? null;
        }
        
        // Upload collateral image 2
        if (isset($_FILES['collateral_image2']) && $_FILES['collateral_image2']['error'] === UPLOAD_ERR_OK) {
            $collateral_image2 = file_get_contents($_FILES['collateral_image2']['tmp_name']);
        } elseif ($is_editing) {
            $existing_sql = "SELECT image2 FROM loan WHERE loan_id = ?";
            $existing_stmt = $pdo->prepare($existing_sql);
            $existing_stmt->execute([$edit_loan_id]);
            $existing_data = $existing_stmt->fetch(PDO::FETCH_ASSOC);
            $collateral_image2 = $existing_data['image2'] ?? null;
        }
        
        // Calculate interest (10% per week)
        $loan_amount = floatval($_POST['loan_amount']);
        $duration = intval($_POST['loan_duration']);
        $interest = $loan_amount * 0.10 * $duration;
        
        // Calculate loan end date by adding number of weeks to loan start date
        $loan_start_date = date('Y-m-d'); // Current date as loan start date
        $loan_end_date = date('Y-m-d', strtotime("+$duration weeks", strtotime($loan_start_date)));
        
        if ($is_editing) {
            // Update existing loan - only store loan-specific data
            $loan_sql = "UPDATE loan SET 
                        amount = ?, duration = ?, interest = ?, 
                        collateral_name = ?, user_id_image = ?, image1 = ?, image2 = ?,
                        loan_start_date = ?, loan_end_date = ?,
                        status = 'pending', updated_at = CURRENT_TIMESTAMP
                        WHERE loan_id = ? AND user_id = ?";
            
            $loan_stmt = $pdo->prepare($loan_sql);
            $loan_stmt->execute([
                $loan_amount,
                $duration,
                $interest,
                $_POST['collateral_name'],
                $id_image,
                $collateral_image1,
                $collateral_image2,
                $loan_start_date,
                $loan_end_date,
                $edit_loan_id,
                $user_id
            ]);
            
            // Update kin information
            $kin_sql = "UPDATE kin SET 
                       first_name = ?, last_name = ?, nrc_number = ?, phone = ?
                       WHERE loan_id = ?";
            
            $kin_stmt = $pdo->prepare($kin_sql);
            $kin_stmt->execute([
                $_POST['kin_first_name'],
                $_POST['kin_last_name'],
                $_POST['kin_nrc'],
                $_POST['kin_phone'],
                $edit_loan_id
            ]);
            
            $_SESSION['success_message'] = "Loan application updated successfully! It will be reviewed again.";
            
        } else {
            // Insert new loan - only store loan-specific data
            $loan_sql = "INSERT INTO loan (user_id, amount, duration, interest, 
                         collateral_name, user_id_image, image1, image2, status, 
                         loan_start_date, loan_end_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)";
            
            $loan_stmt = $pdo->prepare($loan_sql);
            $loan_stmt->execute([
                $user_id,
                $loan_amount,
                $duration,
                $interest,
                $_POST['collateral_name'],
                $id_image,
                $collateral_image1,
                $collateral_image2,
                $loan_start_date,
                $loan_end_date
            ]);
            
            $new_loan_id = $pdo->lastInsertId();
            
            // Insert kin information
            $kin_sql = "INSERT INTO kin (loan_id, first_name, last_name, nrc_number, phone) 
                        VALUES (?, ?, ?, ?, ?)";
            
            $kin_stmt = $pdo->prepare($kin_sql);
            $kin_stmt->execute([
                $new_loan_id,
                $_POST['kin_first_name'],
                $_POST['kin_last_name'],
                $_POST['kin_nrc'],
                $_POST['kin_phone']
            ]);
            
            $_SESSION['success_message'] = "Loan application submitted successfully!";
        }
        
        header("Location: loan.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error processing loan application: " . $e->getMessage();
        header("Location: apply_loan.php" . ($is_editing ? "?edit=$edit_loan_id" : ""));
        exit;
    }
} else {
    header("Location: apply_loan.php");
    exit;
}
?>