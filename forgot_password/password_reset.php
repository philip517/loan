<?php
include 'db_connect.php';

// Redirect if not verified
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'] || !isset($_SESSION['reset_email'])) {
    error_log("Password reset attempt without verification. Session: " . print_r($_SESSION, true));
    header("Location: index.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Log reset attempt
error_log("Password reset process started for email: " . $email);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
        error_log("Empty password fields for email: " . $email);
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        error_log("Password mismatch for email: " . $email);
    } elseif (strlen($password)< 3) {
        $error = "Password must be at least 4 characters long.";
        error_log("Password too short for email: " . $email);
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Step 1: Get user_id from user_table using email
            $stmt = $pdo->prepare("SELECT user_id FROM user_table WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                error_log("User not found in user_table for email: " . $email);
                throw new Exception("User not found.");
                header("Location: index.php");
                exit();
            }
            
            $user_id = $user['user_id'];
            error_log("Found user_id: " . $user_id . " for email: " . $email);
            
            // Step 2: Update password in login_details table using user_id
            $stmt = $pdo->prepare("UPDATE login_details SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $update_count = $stmt->rowCount();
            
            error_log("login_details update - Rows affected: " . $update_count . " for user_id: " . $user_id);

            // Step 4: Delete used reset token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            $delete_count = $stmt->rowCount();
            error_log("password_resets cleanup - Rows deleted: " . $delete_count . " for email: " . $email);
            
            // Commit transaction
            $pdo->commit();
            
            // Log successful password reset
            error_log("SUCCESS: Password reset completed for user_id: " . $user_id . " email: " . $email);
            
            // Clear session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);
            unset($_SESSION['code_created_at']);
            
            // Redirect to login with success message
            header("Location: ../index.php?success=1");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            
            // Log detailed error information
            $error_message = "Password reset ERROR for email: " . $email . " - " . $e->getMessage();
            error_log($error_message);
            error_log("Error trace: " . $e->getTraceAsString());
            
            // Log database error info if available
            if (isset($stmt)) {
                error_log("SQL error info: " . print_r($stmt->errorInfo(), true));
            }
            
            $error = "Error resetting password. Please try again.";
        }
    }
}

// Log page access
error_log("Reset password page accessed for email: " . $email . " - Method: " . $_SERVER['REQUEST_METHOD']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .debug-info { 
            background: #f8f9fa; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            font-size: 12px; 
            display: none; /* Set to block for debugging */
        }
    </style>
</head>
<body>
    <h2>Reset Your Password</h2>
    
    <!-- Debug info (enable for troubleshooting) -->
    <div class="debug-info" style="display: none;">
        <strong>Debug Info:</strong><br>
        Email: <?php echo htmlspecialchars($email); ?><br>
        Session: <?php echo isset($_SESSION['reset_verified']) ? 'Verified' : 'Not Verified'; ?><br>
        Timestamp: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required minlength="8">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>
        
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>