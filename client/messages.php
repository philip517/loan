<?php
require 'auth_client.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch all messages for this user
$message_sql = "SELECT m.*, l.loan_id, l.amount 
               FROM message m 
               LEFT JOIN loan l ON m.loan_id = l.loan_id 
               WHERE l.user_id = ? AND m.type = 'admin_to_user' 
               ORDER BY m.created_at DESC";
$message_stmt = $pdo->prepare($message_sql);
$message_stmt->execute([$user_id]);
$messages = $message_stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read when viewing all messages
if (!empty($messages)) {
    $message_ids = array_column($messages, 'message_id');
    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
    $update_sql = "UPDATE message SET status = 'read' WHERE message_id IN ($placeholders) AND status = 'sent'";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute($message_ids);
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Messages</title>
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
                <h3 class="text-dark mb-0">My Messages</h3>
            </div>
            
            <?php if (empty($messages)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                        <h4>No Messages</h4>
                        <p class="text-muted">You don't have any messages from administrators yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-body">
                        <?php foreach ($messages as $message): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="text-primary"><?php echo htmlspecialchars($message['topic']); ?></h5>
                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                </div>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></p>
                                <?php if ($message['loan_id']): ?>
                                    <small class="text-muted">Related to Loan: K<?php echo number_format($message['amount'], 2); ?></small>
                                <?php endif; ?>
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