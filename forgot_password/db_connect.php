<?php
// config.php
session_start();

// Manual PHPMailer inclusion
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$host = "localhost";       // or "127.0.0.1"
$dbname = "sefa_satty";    // your database name
$username = "root";        // default XAMPP username
$password = "";            // default XAMPP password (leave blank unless changed)

// Email configuration (using PHPMailer or similar)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'philiphilip517@gmail.com');
define('SMTP_PASS', 'yfkk ddaq zahy foun');
define('SMTP_PORT', 587);



try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Uncomment this line for testing (optional)
    // echo "✅ Database connection successful!";
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Email function
function sendResetCode($email, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@yoursite.com', 'Your Website');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code';
        $mail->Body    = "
            <html>
            <head>
                <title>Password Reset</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .code { font-size: 24px; font-weight: bold; color: #007bff; }
                </style>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Your password reset code is: <span class='code'>{$code}</span></p>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Your password reset code is: {$code}. This code will expire in 15 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

?>

