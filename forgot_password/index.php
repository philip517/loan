<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in users table
        $stmt = $pdo->prepare("SELECT user_id FROM user_table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate random 6-digit code
            $code = sprintf("%06d", mt_rand(1, 999999));
            
            // Delete any existing reset tokens for this email
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            
            // Insert new reset token (only email and token - no expires_at)
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $code]);
            
            // Send email with code
            if (sendResetCode($email, $code)) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['code_created_at'] = time(); // Store creation time in session
                header("Location: verify-code.php");
                exit();
            } else {
                $error = "Failed to send reset code. Please try again.";
            }
        } else {
            $error = "If this email exists in our system, a reset code will be sent.";
            // Don't reveal if email exists or not for security
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Forgot Password</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="success">Password reset successfully! You can now login with your new password.</div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="email">Enter your email address:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit">Send Reset Code</button>
    </form>
    
    <p><a href="../index.php">Back to Login</a></p>
</body>
</html>