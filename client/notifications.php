<?php
session_start();
include 'auth_client.php'; // Include your authentication check
include '../db_connect.php'; // Include your database connection

// Check if user is super admin (you should implement proper authentication)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'];
    $receiver = $_POST['receiver'];
    $sender_id = $_SESSION['user_id']; // Assuming user_id is stored in session
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (text, sender_id, receiver) VALUES (?, ?, ?)");
        $stmt->execute([$text, $sender_id, $receiver]);
        $success = "Notification sent successfully!";
    } catch(PDOException $e) {
        $error = "Error sending notification: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Send Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        textarea { width: 100%; height: 100px; padding: 8px; }
        select, button { padding: 8px 15px; }
        .success { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <? require 'navbar.php' ?>
    <div class="container">
        <h1>Send Notification</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="receiver">Send to:</label>
                <select name="receiver" id="receiver" required>
                    <option value="admin">Admins Only</option>
                    <option value="client">Clients Only</option>
                    <option value="all">Both Admins & Clients</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="text">Notification Message:</label>
                <textarea name="text" id="text" required placeholder="Enter your notification message..."></textarea>
            </div>
            
            <button type="submit">Send Notification</button>
        </form>
        
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
