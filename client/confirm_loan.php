<?php
require 'auth_client.php';
require '../db_connect.php';

// Loan number generation function
function generateLoanNumber($pdo) {
    $current_date = date('ymd'); // Format: YYMMDD
    $today = date('Y-m-d');
    
    try {
        // Check if we have a sequence for today
        $stmt = $pdo->prepare("SELECT last_number FROM loan_sequence WHERE date = ?");
        $stmt->execute([$today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Increment existing sequence
            $sequence_num = $result['last_number'] + 1;
            $stmt = $pdo->prepare("UPDATE loan_sequence SET last_number = ? WHERE date = ?");
            $stmt->execute([$sequence_num, $today]);
        } else {
            // Create new sequence for today
            $sequence_num = 1;
            $stmt = $pdo->prepare("INSERT INTO loan_sequence (date, last_number) VALUES (?, ?)");
            $stmt->execute([$today, $sequence_num]);
        }
        
        // Format: YYMMDDNNN
        return $current_date . str_pad($sequence_num, 3, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        // Fallback: use timestamp-based number
        return $current_date . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Handle file uploads - only for new loans
        $id_image = null;
        $collateral_image1 = null;
        $collateral_image2 = null;
        
        // Upload ID image
        if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
            $id_image = file_get_contents($_FILES['id_image']['tmp_name']);
        }
        
        // Upload collateral image 1
        if (isset($_FILES['collateral_image1']) && $_FILES['collateral_image1']['error'] === UPLOAD_ERR_OK) {
            $collateral_image1 = file_get_contents($_FILES['collateral_image1']['tmp_name']);
        }
        
        // Upload collateral image 2
        if (isset($_FILES['collateral_image2']) && $_FILES['collateral_image2']['error'] === UPLOAD_ERR_OK) {
            $collateral_image2 = file_get_contents($_FILES['collateral_image2']['tmp_name']);
        }
        
        // Calculate interest (10% per week)
        $loan_amount = floatval($_POST['loan_amount']);
        $duration = intval($_POST['loan_duration']);
        $interest = $loan_amount * 0.10 * $duration;
        
        // Calculate loan end date by adding number of weeks to loan start date
        $loan_start_date = date('Y-m-d'); // Current date as loan start date
        $loan_end_date = date('Y-m-d', strtotime("+$duration weeks", strtotime($loan_start_date)));
        
        // Generate loan number for new loan
        $loan_number = generateLoanNumber($pdo);
        
        // Insert new loan with loan number
        $loan_sql = "INSERT INTO loan (loan_number, user_id, amount, duration, interest, 
                     collateral_name, user_id_image, image1, image2, status, 
                     loan_start_date, loan_end_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)";
        
        $loan_stmt = $pdo->prepare($loan_sql);
        $loan_stmt->execute([
            $loan_number,
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
        
        $_SESSION['success_message'] = "Loan application submitted successfully! Your loan number is: " . $loan_number;
        
        header("Location: loan.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error processing loan application: " . $e->getMessage();
        header("Location: apply_loan.php");
        exit;
    }
} else {
    header("Location: apply_loan.php");
    exit;
}
?>