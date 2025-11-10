<?php 
require 'auth_admin.php';
require '../db_connect.php';

// Fetch loans with message counts
try {
    $stmt = $pdo->prepare("
        SELECT 
            l.loan_id,
            l.loan_number,
            u.first_name,
            u.last_name,
            l.status as loan_status,
            COUNT(m.message_id) as message_count,
            MAX(m.created_at) as last_message_date
        FROM loan l
        LEFT JOIN user_table u ON l.user_id = u.user_id
        LEFT JOIN message m ON l.loan_id = m.loan_id
        WHERE m.message_id IS NOT NULL
        GROUP BY l.loan_id, l.loan_number, u.first_name, u.last_name, l.status
        ORDER BY last_message_date DESC
    ");
    $stmt->execute();
    $loansWithMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $loansWithMessages = [];
    $error = "Error fetching loans with messages: " . $e->getMessage();
}

// Fetch messages without loan association
try {
    $stmt = $pdo->prepare("
        SELECT m.* 
        FROM message m 
        WHERE m.loan_id IS NULL
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $generalMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $generalMessages = [];
}

// Handle form submission for new messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'] ?? '';
    $message_text = $_POST['message'] ?? '';
    $type = 'admin_to_user'; // Always set to admin_to_user
    $loan_id = $_POST['loan_id'] ?? null;
    
    if (!empty($topic) && !empty($message_text)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO message (status, topic, message_text, type, loan_id, created_at) 
                VALUES ('sent', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$topic, $message_text, $type, $loan_id]);
            
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
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
    <title>Admin Messaging</title>
    <meta name="description" content="Admin Messaging System">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .loan-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #0d6efd;
        }
        .loan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .message-count-badge {
            font-size: 0.8rem;
        }
        .client-name {
            color: #495057;
            font-weight: 600;
        }
        .loan-status-badge {
            font-size: 0.75rem;
        }
        .last-message {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .general-messages-card {
            border-left: 4px solid #6c757d;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;">
                <div class="container-fluid top-0 overflow-scroll" style="margin-top: 100px;">
                    <!-- Error/Success Messages -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>Loan Messages</strong></h3>
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="fas fa-plus me-2"></i>Compose New Message
                        </button>
                    </div>
                    
                    <div class="row">
                        <!-- Loans with Messages -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold m-0">Loans with Messages</h6>
                                    <span class="badge bg-primary"><?php echo count($loansWithMessages); ?> loans</span>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($loansWithMessages)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No loans with messages found</p>
                                            <p class="text-muted small">Messages will appear here once sent or received</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($loansWithMessages as $loan): 
                                                $statusColor = match($loan['loan_status']) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'pending' => 'warning',
                                                    default => 'secondary'
                                                };
                                            ?>
                                                <div class="list-group-item p-3 loan-card" 
                                                     onclick="window.location.href='loan_messages.php?loan_id=<?php echo $loan['loan_id']; ?>'">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="fas fa-file-invoice-dollar text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <div>
                                                                    <h6 class="mb-0">
                                                                        Loan #<?php echo htmlspecialchars($loan['loan_number']); ?>
                                                                        <span class="badge bg-<?php echo $statusColor; ?> loan-status-badge ms-2">
                                                                            <?php echo ucfirst($loan['loan_status']); ?>
                                                                        </span>
                                                                    </h6>
                                                                    <p class="mb-0 client-name">
                                                                        <?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-end">
                                                                    <span class="badge bg-info message-count-badge">
                                                                        <?php echo $loan['message_count']; ?> message(s)
                                                                    </span>
                                                                    <?php if ($loan['last_message_date']): ?>
                                                                        <div class="text-muted small mt-1">
                                                                            Last: <?php echo date('M j, g:i A', strtotime($loan['last_message_date'])); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="last-message">
                                                                    Click to view all messages for this loan
                                                                </span>
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

                            <!-- General Messages (No Loan Association) -->
                            <?php if (!empty($generalMessages)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="text-dark fw-bold m-0">General Messages</h6>
                                    <span class="badge bg-secondary"><?php echo count($generalMessages); ?> messages</span>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($generalMessages as $message): ?>
                                            <div class="list-group-item p-3 general-messages-card">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-envelope text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($message['topic']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <p class="mb-1 text-muted">
                                                            <?php echo htmlspecialchars($message['message_text']); ?>
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                Type: <?php echo $message['type']; ?> | 
                                                                Status: <span class="badge bg-<?php 
                                                                    echo $message['status'] == 'sent' ? 'info' : 
                                                                         ($message['status'] == 'received' ? 'success' : 'secondary'); 
                                                                ?>"><?php echo ucfirst($message['status']); ?></span>
                                                            </small>
                                                            <small class="text-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>No loan association
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Statistics/Quick Actions -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="text-primary fw-bold m-0">Message Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    try {
                                        // Count total messages
                                        $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM message");
                                        $totalMessages = $totalStmt->fetchColumn();
                                        
                                        // Count messages with loan association
                                        $loanMsgStmt = $pdo->query("SELECT COUNT(*) as loan_messages FROM message WHERE loan_id IS NOT NULL");
                                        $loanMessages = $loanMsgStmt->fetchColumn();
                                        
                                        // Count messages by type
                                        $typeStmt = $pdo->query("
                                            SELECT type, COUNT(*) as count 
                                            FROM message 
                                            GROUP BY type
                                        ");
                                        $typeCounts = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                    } catch (PDOException $e) {
                                        $totalMessages = $loanMessages = 0;
                                        $typeCounts = [];
                                    }
                                    ?>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold">Overview</h6>
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex justify-content-between">
                                                <span>Total Messages:</span>
                                                <span class="badge bg-primary"><?php echo $totalMessages; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Loan Messages:</span>
                                                <span class="badge bg-success"><?php echo $loanMessages; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>General Messages:</span>
                                                <span class="badge bg-secondary"><?php echo $totalMessages - $loanMessages; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold">By Type</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-primary">Admin to User: <?php echo $typeCounts['admin_to_user'] ?? 0; ?></span>
                                            <span class="badge bg-warning text-dark">User to Admin: <?php echo $typeCounts['user_to_admin'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6 class="small fw-bold">Quick Actions</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#composeModal">
                                                <i class="fas fa-plus me-1"></i>New Message
                                            </button>
                                            <a href="loan.php" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-list me-1"></i>View All Loans
                                            </a>
                                            <a href="loan_review.php" class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-search me-1"></i>Review Loans
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compose Message Modal -->
            <div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="composeModalLabel">Compose New Message</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> All messages sent from this page will be recorded as "Admin to User" type.
                                </div>
                                <div class="mb-3">
                                    <label for="topic" class="form-label">Topic</label>
                                    <input class="form-control" type="text" id="topic" name="topic" 
                                           placeholder="Enter message topic" required>
                                </div>
                                <div class="mb-3">
                                    <label for="loan_id" class="form-label">Associated Loan (Optional)</label>
                                    <select class="form-select" id="loan_id" name="loan_id">
                                        <option value="">No associated loan</option>
                                      <?php
try {
    $loanStmt = $pdo->query("
        SELECT l.loan_id, l.loan_number, u.first_name, u.last_name 
        FROM loan l 
        JOIN user_table u ON l.user_id = u.user_id 
        ORDER BY l.loan_id DESC 
        LIMIT 20
    ");
    $loans = $loanStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($loans as $loan) {
        $clientName = htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']);
        echo "<option value=\"{$loan['loan_id']}\">Loan #{$loan['loan_number']} - {$clientName}</option>";
    }
} catch (PDOException $e) {
    echo "<option value=''>Error loading loans</option>";
}
?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" 
                                              placeholder="Enter your message here" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </form>
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