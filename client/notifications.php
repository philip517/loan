<?php
require 'auth_client.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch all notifications for this user
$notification_sql = "SELECT n.*, u.first_name as admin_first_name, u.last_name as admin_last_name 
                    FROM notifications n 
                    LEFT JOIN user_table u ON n.created_by = u.user_id 
                    WHERE n.status = 'active' 
                    AND (n.type = 'broadcast' OR (n.type = 'specific' AND n.target_user_id = ?))
                    ORDER BY n.created_at DESC";
$notification_stmt = $pdo->prepare($notification_sql);
$notification_stmt->execute([$user_id]);
$notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Notifications</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="container-fluid" style="margin-top: 100px;">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h3 class="text-dark mb-0">My Notifications</h3>
                <span class="badge bg-primary"><?php echo count($notifications); ?> notifications</span>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h4>No Notifications</h4>
                        <p class="text-muted">You don't have any notifications at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-body">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="text-primary">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if ($notification['type'] === 'broadcast'): ?>
                                            <span class="badge bg-info ms-2">Broadcast</span>
                                        <?php else: ?>
                                            <span class="badge bg-success ms-2">Personal</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                                </div>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                <small class="text-muted">
                                    From: <?php echo htmlspecialchars($notification['admin_first_name'] . ' ' . $notification['admin_last_name']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>