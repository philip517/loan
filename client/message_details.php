<?php
require 'auth_client.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$message_id = $_GET['message_id'] ?? null;

if (!$user_id || !$message_id) {
    header("Location: loan.php");
    exit;
}

// Fetch specific message
$message_sql = "SELECT m.*, l.loan_id, l.amount 
               FROM message m 
               LEFT JOIN loan l ON m.loan_id = l.loan_id 
               WHERE m.message_id = ? AND l.user_id = ? AND m.type = 'admin_to_user'";
$message_stmt = $pdo->prepare($message_sql);
$message_stmt->execute([$message_id, $user_id]);
$message = $message_stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    header("Location: messages.php");
    exit;
}

// Mark message as read
$update_sql = "UPDATE message SET status = 'read' WHERE message_id = ?";
$update_stmt = $pdo->prepare($update_sql);
$update_stmt->execute([$message_id]);
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Message Details</title>
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
                <h3 class="text-dark mb-0">Message Details</h3>
                <a class="btn btn-primary btn-sm" href="messages.php">&larr; Back to Messages</a>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4 class="text-primary"><?php echo htmlspecialchars($message['topic']); ?></h4>
                            <p class="text-muted mb-0">Received: <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?></p>
                        </div>
                        <?php if ($message['loan_id']): ?>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-info">Loan: K<?php echo number_format($message['amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="border-top pt-3">
                        <h5 class="mb-3">Message:</h5>
                        <div class="bg-light p-4 rounded">
                            <?php echo nl2br(htmlspecialchars($message['message_text'])); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="messages.php" class="btn btn-secondary">Back to Messages</a>
                        <?php if ($message['loan_id']): ?>
                            <a href="loan.php" class="btn btn-primary">View My Loans</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>