<?php
require 'auth_client.php';
require '../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch all messages for this user with loan details, grouped by loan
$message_sql = "SELECT 
        l.loan_id,
        l.loan_number,
        l.amount,
        l.status as loan_status,
        COUNT(m.message_id) as message_count,
        MAX(m.created_at) as last_message_date,
        SUM(CASE WHEN m.status = 'sent' AND m.type = 'admin_to_user' THEN 1 ELSE 0 END) as unread_count,
        m.topic as last_topic,
        SUBSTRING(m.message_text, 1, 100) as last_message_preview
    FROM loan l 
    LEFT JOIN message m ON l.loan_id = m.loan_id 
    WHERE l.user_id = ? AND (m.message_id IS NOT NULL OR l.loan_id IS NOT NULL)
    GROUP BY l.loan_id, l.loan_number, l.amount, l.status
    ORDER BY last_message_date DESC, l.loan_id DESC";
$message_stmt = $pdo->prepare($message_sql);
$message_stmt->execute([$user_id]);
$loan_conversations = $message_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get message statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total_messages,
        SUM(CASE WHEN m.status = 'read' THEN 1 ELSE 0 END) as read_messages,
        SUM(CASE WHEN m.status = 'sent' THEN 1 ELSE 0 END) as unread_messages
        FROM message m 
        LEFT JOIN loan l ON m.loan_id = l.loan_id 
        WHERE l.user_id = ? AND m.type = 'admin_to_user'";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_messages' => 0, 'read_messages' => 0, 'unread_messages' => 0];
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>My Messages</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .conversation-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #0dcaf0;
        }
        .conversation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .conversation-card.has-unread {
            border-left-color: #198754;
            background-color: rgba(25, 135, 84, 0.05);
        }
        .loan-link {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
        }
        .loan-link:hover {
            text-decoration: underline;
        }
        .message-preview {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 400px;
        }
        .loan-status-badge {
            font-size: 0.75rem;
        }
        .unread-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .clickable-row {
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;">
                <div class="container-fluid top-0 overflow-scroll" style="margin-top: 100px;">
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>My Messages</strong></h3>
                        <div>
                            <span class="badge bg-success me-2">
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo $stats['total_messages']; ?> Total
                            </span>
                            <?php if ($stats['unread_messages'] > 0): ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-bell me-1"></i>
                                    <?php echo $stats['unread_messages']; ?> Unread
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Loan Conversations List -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">Loan Conversations</h6>
                                    <div>
                                        <span class="badge bg-info"><?php echo $stats['unread_messages']; ?> unread</span>
                                        <span class="badge bg-secondary"><?php echo $stats['read_messages']; ?> read</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($loan_conversations)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Messages Yet</h5>
                                            <p class="text-muted">You don't have any messages from administrators yet.</p>
                                            <p class="text-muted small">Messages about your loan applications will appear here.</p>
                                            <a href="apply_loan.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus me-2"></i>Apply for a Loan
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($loan_conversations as $conversation): 
                                                $hasUnread = $conversation['unread_count'] > 0;
                                                $cardClass = $hasUnread ? 'has-unread' : '';
                                                
                                                $statusColor = match($conversation['loan_status'] ?? '') {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'pending' => 'warning',
                                                    default => 'secondary'
                                                };
                                            ?>
                                                <div class="list-group-item p-3 conversation-card <?php echo $cardClass; ?>" 
                                                     onclick="viewLoanConversation(<?php echo $conversation['loan_id']; ?>)">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center position-relative" 
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="fas fa-file-invoice-dollar text-white"></i>
                                                                <?php if ($hasUnread): ?>
                                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger unread-badge">
                                                                        <?php echo $conversation['unread_count']; ?>
                                                                        <span class="visually-hidden">unread messages</span>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <div>
                                                                    <h6 class="mb-0">
                                                                        Loan #<?php echo $conversation['loan_number']; ?>
                                                                        <span class="badge bg-<?php echo $statusColor; ?> loan-status-badge ms-2">
                                                                            <?php echo ucfirst($conversation['loan_status']); ?>
                                                                        </span>
                                                                        <?php if ($hasUnread): ?>
                                                                            <span class="badge bg-danger ms-2">New Messages</span>
                                                                        <?php endif; ?>
                                                                    </h6>
                                                                    <p class="mb-0 text-muted small">
                                                                        Amount: K<?php echo number_format($conversation['amount'], 2); ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-end">
                                                                    <span class="badge bg-info">
                                                                        <?php echo $conversation['message_count']; ?> messages
                                                                    </span>
                                                                    <?php if ($conversation['last_message_date']): ?>
                                                                        <div class="text-muted small mt-1">
                                                                            <?php echo date('M j, g:i A', strtotime($conversation['last_message_date'])); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <?php if ($conversation['last_topic']): ?>
                                                                <div class="mb-2">
                                                                    <strong class="text-dark"><?php echo htmlspecialchars($conversation['last_topic']); ?></strong>
                                                                </div>
                                                                <p class="mb-2 message-preview text-muted">
                                                                    <?php echo htmlspecialchars($conversation['last_message_preview']); ?>...
                                                                </p>
                                                            <?php else: ?>
                                                                <p class="mb-2 text-muted">
                                                                    <i>No messages yet for this loan</i>
                                                                </p>
                                                            <?php endif; ?>
                                                            
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">
                                                                    Click to view full conversation
                                                                </small>
                                                                <i class="fas fa-chevron-right text-muted"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistics & Quick Actions -->
                        <div class="col-lg-4 mb-4">
                            <!-- Message Statistics -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Message Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="small fw-bold">Message Status</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-info">
                                                <i class="fas fa-envelope me-1"></i>
                                                Total: <?php echo $stats['total_messages']; ?>
                                            </span>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                Read: <?php echo $stats['read_messages']; ?>
                                            </span>
                                            <?php if ($stats['unread_messages'] > 0): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-bell me-1"></i>
                                                    Unread: <?php echo $stats['unread_messages']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold">Conversations</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-primary">
                                                <i class="fas fa-comments me-1"></i>
                                                <?php echo count($loan_conversations); ?> Loans
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="apply_loan.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Apply for New Loan
                                        </a>
                                        <a href="loan.php" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-list me-1"></i>View My Loans
                                        </a>
                                        <a href="index.php" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Information -->
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Need Help?</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        Click on any loan to view the full conversation with administrators.
                                    </p>
                                    <p class="small text-muted mb-2">
                                        <i class="fas fa-bell text-warning me-2"></i>
                                        Loans with unread messages are highlighted in green.
                                    </p>
                                    <p class="small text-muted">
                                        <i class="fas fa-reply text-info me-2"></i>
                                        You can reply to messages in the conversation view.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logout Modal -->
            <div class="modal fade text-center" role="dialog" tabindex="-1" id="modal-1">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header"></div>
                        <div class="modal-body">
                            <p>Leaving Already?</p>
                        </div>
                        <div class="modal-footer text-end" style="text-align: justify;">
                            <p style="text-align: left;">
                                <button class="btn btn-light" type="button" data-bs-dismiss="modal" style="text-align: center;">No</button>
                                &nbsp;&nbsp;
                                <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="../index.php">Yes</a>
                            </p>
                            <div class="text-center" style="display: inline-block;"></div>
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
        function viewLoanConversation(loanId) {
            window.location.href = 'loan_conversation.php?loan_id=' + loanId;
        }
        
        // Make rows keyboard accessible
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.conversation-card');
            cards.forEach(card => {
                card.setAttribute('tabindex', '0');
                card.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const loanId = this.getAttribute('onclick').match(/viewLoanConversation\((\d+)\)/)[1];
                        viewLoanConversation(loanId);
                    }
                });
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>