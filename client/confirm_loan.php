<?php
session_start();
require '../db_connect.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Handle file uploads
        $id_image_data = null;
        $collateral_image1_data = null;
        $collateral_image2_data = null;

        // Upload ID image
        if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $id_image_data = file_get_contents($_FILES['id_image']['tmp_name']);
        }

        // Upload collateral image 1
        if (isset($_FILES['collateral_image1']) && $_FILES['collateral_image1']['error'] === UPLOAD_ERR_OK) {
            $collateral_image1_data = file_get_contents($_FILES['collateral_image1']['tmp_name']);
        }

        // Upload collateral image 2
        if (isset($_FILES['collateral_image2']) && $_FILES['collateral_image2']['error'] === UPLOAD_ERR_OK) {
            $collateral_image2_data = file_get_contents($_FILES['collateral_image2']['tmp_name']);
        }

        // Calculate interest based on your JavaScript logic (12% per week)
        $loan_amount = floatval($_POST['loan_amount']);
        $loan_duration = intval($_POST['loan_duration']);
        
        $interest_rate = 0.12; // 12% per week (matches your JS)
        $interest_amount = $loan_amount * $interest_rate * $loan_duration;
        $total_repayment = $loan_amount + $interest_amount;
        
        $loan_start_date = date('Y-m-d');
        $loan_end_date = date('Y-m-d', strtotime("+$loan_duration weeks"));

        // Insert into loan table
        $loan_sql = "INSERT INTO loan (amount, duration, interest, loan_start_date, loan_end_date, 
                      collateral_name, image1, image2, user_id, user_id_image) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([
            $loan_amount,
            $loan_duration,
            $interest_amount,
            $loan_start_date,
            $loan_end_date,
            $_POST['collateral_name'],
            $collateral_image1_data,
            $collateral_image2_data,
            $user_id,
            $id_image_data
        ]);

        $loan_id = $pdo->lastInsertId();

        // Insert into kin table
        $kin_sql = "INSERT INTO kin (first_name, last_name, nrc_number, phone, loan_id) 
                    VALUES (?, ?, ?, ?, ?)";
        
        $kin_stmt = $pdo->prepare($kin_sql);
        $kin_stmt->execute([
            $_POST['kin_first_name'],
            $_POST['kin_last_name'],
            $_POST['kin_nrc'],
            $_POST['kin_phone'],
            $loan_id
        ]);

        // Update user details in user_table
        $user_sql = "UPDATE user_table SET 
                     first_name = ?, 
                     last_name = ?, 
                     phone = ?, 
                     NRC = ?, 
                     date_of_birth = ?, 
                     address = ?, 
                     occupation = ?, 
                     nationality = ? 
                     WHERE user_id = ?";
        
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            $_POST['nrc'],
            $_POST['date_of_birth'],
            $_POST['address'],
            $_POST['occupation'],
            $_POST['nationality'],
            $user_id
        ]);

        // Commit transaction
        $pdo->commit();

        // After successful processing, show success message
$_SESSION['success_message'] = "Loan application submitted successfully!";

// Instead of redirecting, include a success page or go back
header("Location: apply_loan.php?success=1");
exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Log error and redirect to error page
        error_log("Loan application error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to submit loan application. Please try again.";
        header("Location: apply_loan.php");
        exit;
    }
} else {
    // If not POST request, redirect back to application form
    header("Location: apply_loan.php");
    exit;
}
?>