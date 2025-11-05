<?php
include 'db_connect.php';

// Redirect if no email in session
if (!isset($_SESSION['reset_email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_code = preg_replace('/[^0-9]/', '', $_POST['code']);
    
    if (empty($entered_code) || strlen($entered_code) != 6) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        // Check if code is valid
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
        $stmt->execute([$email, $entered_code]);
        $reset_request = $stmt->fetch();
        
        if ($reset_request) {
            // Check expiration using session timestamp (15 minutes)
            $current_time = time();
            $code_created_at = $_SESSION['code_created_at'];
            $expiration_time = $code_created_at + (15 * 60); // 15 minutes in seconds
            
            if ($current_time < $expiration_time) {
                $_SESSION['reset_verified'] = true;
                header("Location: password_reset.php");
                exit();
            } else {
                $error = "Reset code has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid reset code. Please try again.";
        }
    }
}

// Resend code functionality
if (isset($_GET['resend'])) {
    // Generate new code
    $new_code = sprintf("%06d", mt_rand(1, 999999));
    
    // Update the reset token
    $stmt = $pdo->prepare("UPDATE password_resets SET token = ? WHERE email = ?");
    $stmt->execute([$new_code, $email]);
    
    // Update creation time in session
    $_SESSION['code_created_at'] = time();
    
    // Send new code
    sendResetCode($email, $new_code);
    $success = "A new code has been sent to your email.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"] { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            text-align: center;
            font-size: 18px;
            letter-spacing: 8px;
        }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .resend { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <h2>Enter Reset Code</h2>
    <p>We've sent a 6-digit code to <?php echo htmlspecialchars($email); ?></p>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="code">Enter the 6-digit code:</label>
            <input type="text" id="code" name="code" maxlength="6" required pattern="[0-9]{6}" title="Please enter exactly 6 digits">
        </div>
        <button type="submit">Verify Code</button>
    </form>
    
    <div class="resend">
        <p>Didn't receive the code? <a href="verify-code.php?resend=1">Resend Code</a></p>
        <p><a href="index.php">Use different email</a></p>
    </div>
</body>
</html>