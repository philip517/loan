<?php 
require 'auth_client.php';
require '../db_connect.php';

// Get loan ID from URL parameter
$loan_id = $_GET['loan_id'] ?? null;

if (!$loan_id) {
    $_SESSION['error_message'] = "No loan ID provided.";
    header("Location: messages.php");
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

// Fetch loan details for this user only
try {
    $loanStmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.phone, u.email
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        WHERE l.loan_id = ? AND l.user_id = ?
    ");
    $loanStmt->execute([$loan_id, $user_id]);
    $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        $_SESSION['error_message'] = "Loan not found or you don't have permission to view this loan.";
        header("Location: messages.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching loan details: " . $e->getMessage());
}

// Fetch messages for this loan
try {
    $messagesStmt = $pdo->prepare("
        SELECT m.* 
        FROM message m 
        WHERE m.loan_id = ?
        ORDER BY m.created_at ASC
    ");
    $messagesStmt->execute([$loan_id]);
    $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    $error = "Error fetching messages: " . $e->getMessage();
}

// Mark admin messages as read when viewing conversation
if (!empty($messages)) {
    $message_ids = array_column($messages, 'message_id');
    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
    
    $update_sql = "UPDATE message SET status = 'read' 
                   WHERE message_id IN ($placeholders) 
                   AND status = 'sent' 
                   AND type = 'admin_to_user'";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute($message_ids);
}

// Handle form submission for new messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $topic = $_POST['topic'] ?? '';
    $message_text = $_POST['message_text'] ?? '';
    
    if (!empty($topic) && !empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO message (status, topic, message_text, type, loan_id, created_at) 
                VALUES ('sent', ?, ?, 'user_to_admin', ?, NOW())
            ");
            $stmt->execute([$topic, $message_text, $loan_id]);
            
            $_SESSION['success_message'] = "Message sent successfully!";
            header("Location: loan_conversation.php?loan_id=" . $loan_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Conversation - <?php echo $loan['loan_number']; ?></title>
    <meta name="description" content="Loan Conversation">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .message-bubble {
            max-width: 70%;
            margin-bottom: 1rem;
            padding: 1rem;
        }
        .admin-message {
            margin-left: auto;
            background: #007bff;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        .user-message {
            margin-right: auto;
            background: #f8f9fa;
            color: #333;
            border-radius: 18px 18px 18px 4px;
            border: 1px solid #dee2e6;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .loan-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
        }
        .status-badge {
            font-size: 0.7rem;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;">
                <div class="container-fluid" style="margin-top: 80px;">
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Loan Header -->
                    <div class="card loan-header shadow-lg mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="text-white mb-1">
                                        <i class="fas fa-comments me-2"></i>Conversation for Loan #<?php echo $loan['loan_number']; ?>
                                    </h3>
                                    <p class="text-white mb-0">
                                        Your Loan: <strong>K<?php echo number_format($loan['amount'], 2); ?></strong> | 
                                        Status: <span class="badge bg-light text-dark"><?php echo ucfirst($loan['status']); ?></span>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="loan_details.php?loan_id=<?php echo $loan_id; ?>" class="btn btn-light me-2">
                                        <i class="fas fa-eye me-1"></i>View Loan Details
                                    </a>
                                    <a href="messages.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Messages
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Messages -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">Conversation History</h6>
                                    <span class="badge bg-primary"><?php echo count($messages); ?> messages</span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($messages)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No messages yet for this loan</p>
                                            <p class="text-muted small">Start a conversation by sending a message below</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="message-container">
                                            <?php foreach ($messages as $message): 
                                                $isAdmin = $message['type'] === 'admin_to_user';
                                            ?>
                                                <div class="message-bubble <?php echo $isAdmin ? 'admin-message' : 'user-message'; ?>">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <strong>
                                                            <?php echo $isAdmin ? 'Administrator' : 'You'; ?>
                                                        </strong>
                                                        <span class="message-time">
                                                            <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <h6 class="mb-2"><?php echo htmlspecialchars($message['topic']); ?></h6>
                                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></p>
                                                    <div class="mt-2">
                                                        <small class="badge status-badge bg-<?php 
                                                            echo $message['status'] == 'sent' ? 'info' : 
                                                                 ($message['status'] == 'received' ? 'success' : 'secondary'); 
                                                        ?>">
                                                            <?php echo ucfirst($message['status']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Send Message -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Send Message</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="topic" class="form-label">Topic</label>
                                            <select class="form-select" id="topic" name="topic" required>
                                                <option value="" selected disabled>Select Topic</option>
                                                <option value="Loan Application Status">Loan Application Status</option>
                                                <option value="Additional Information">Additional Information</option>
                                                <option value="Collateral Questions">Collateral Questions</option>
                                                <option value="Payment Schedule">Payment Schedule</option>
                                                <option value="Extension Request">Extension Request</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="message_text" class="form-label">Message</label>
                                            <textarea class="form-control" id="message_text" name="message_text" rows="6" 
                                                      placeholder="Type your message to the administrator..." required></textarea>
                                        </div>
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i> 
                                                Your message will be sent to the loan administrators.
                                            </small>
                                        </div>
                                        <button type="submit" name="send_message" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Loan Quick Info -->
                            <div class="card shadow mt-4">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Loan Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Loan Number:</strong><br><?php echo $loan['loan_number']; ?></p>
                                    <p><strong>Amount:</strong><br>K<?php echo number_format($loan['amount'], 2); ?></p>
                                    <p><strong>Duration:</strong><br><?php echo $loan['duration']; ?> weeks</p>
                                    <p><strong>Status:</strong><br>
                                        <span class="badge bg-<?php 
                                            echo $loan['status'] == 'approved' ? 'success' : 
                                                 ($loan['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($loan['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="bg-white sticky-footer">
                <div class="container my-auto">
                    <div class="text-center my-auto copyright"><span>Copyright © SEFA SATTY 2025</span></div>
                </div>
            </footer>
        </div>
        <a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        });
    </script>
</body>
</html>