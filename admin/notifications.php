
<?php

include 'auth_admin.php'; // Include your authentication check
include '../db_connect.php'; // Include your database connection



// Fetch notifications for admins (receiver = 'admin' or 'all')
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.username as sender_name 
        FROM notifications n 
        LEFT JOIN user_table u ON n.sender_id = u.user_id 
        WHERE n.receiver IN ('admin', 'all') 
        ORDER BY n.date DESC
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching notifications: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Review - <?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?></title>
    <meta name="description" content="Loan Review Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <title>Admin - Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .notification { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .notification-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .sender { font-weight: bold; color: #333; }
        .date { color: #666; font-size: 0.9em; }
        .message { margin-top: 10px; }
        .receiver-badge { background: #007bff; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8em; }
    </style>
</head>
<body>
    <?php require 'navbar.php';?>
    <div class="container">
        <h1>Admin Notifications</h1>
        
        <?php if (empty($notifications)): ?>
            <p>No notifications found.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification">
                    <div class="notification-header">
                        <span class="sender">From: <?php echo htmlspecialchars($notification['sender_name'] ?? 'System'); ?></span>
                        <span class="receiver-badge">
                            <?php echo htmlspecialchars($notification['receiver']); ?>
                        </span>
                    </div>
                    <div class="date">
                        <?php echo date('M j, Y g:i A', strtotime($notification['date'])); ?>
                    </div>
                    <div class="message">
                        <?php echo nl2br(htmlspecialchars($notification['text'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>