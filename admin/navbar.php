<?php  
// Fetch admin details
$stmt = $pdo->prepare("SELECT first_name,last_name FROM user_table WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC); 

// Fetch unread messages from users
$message_stmt = $pdo->prepare("
    SELECT m.*, l.loan_number, u.first_name, u.last_name 
    FROM message m 
    LEFT JOIN loan l ON m.loan_id = l.loan_id 
    LEFT JOIN user_table u ON l.user_id = u.user_id 
    WHERE m.status = 'sent' AND m.type = 'user_to_admin' 
    ORDER BY m.created_at DESC 
    LIMIT 5
");
$message_stmt->execute();
$unread_messages = $message_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total unread messages
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count 
    FROM message 
    WHERE status = 'sent' AND type = 'user_to_admin'
");
$count_stmt->execute();
$unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

?>
<nav class="navbar z-3 align-items-start p-0 sidebar sidebar-dark accordion bg-gradient-primary navbar-dark">
        <div class="container-fluid d-flex flex-column p-0">
            <a class="navbar-brand d-flex justify-content-center align-items-center m-0 sidebar-brand" href="index.php">
                <div class="me-0 sidebar-brand-icon rotate-n-15"><img src="assets/img/white%20logo.png" width="65" height="60" style="transform: rotate(10deg);"></div>
                <div class="mx-3 sidebar-brand-text"><span>SEFA SATTY</span></div>
            </a>
            <hr class="my-0 sidebar-divider">
            <ul class="navbar-nav text-light" id="accordionSidebar">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link" href="loan.php"><i class="fas fa-hand-holding-usd"></i><span>Loans</span></a></li>
                <li class="nav-item"><a class="nav-link" href="message.php"><i class="fas fa-envelope"></i><span>Messages</span></a></li>
                <li class="nav-item"><a class="nav-link" href="loan_request.php"><i class="fas fa-envelope"></i><span>Loan Request</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="overdue.php"><i class="fas fa-user-circle"></i><span>Overdue</span></a></li>

            </ul>
        </div>
    </nav>

    <div class="d-flex flex-column" id="content-wrapper">
        <nav class="navbar navbar-expand fixed-top bg-white shadow z-1 mb-4 topbar">
                <div class="container-fluid"><button class="btn btn-link d-md-none me-3 rounded-circle" id="sidebarToggleTop-1" type="button"><i class="fas fa-bars"></i></button>
                    <ul class="navbar-nav flex-nowrap ms-auto">
                        <li class="nav-item mx-1 dropdown no-arrow">
                            <div class="nav-item dropdown no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger badge-counter"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                                <i class="fas fa-envelope fa-fw"></i></a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-list animated--grow-in">
                                    <h6 class="dropdown-header">Messages Center</h6>
                                    <?php if (empty($unread_messages)): ?>
                                        <a class="dropdown-item text-center small text-gray-500" href="#">
                                            <div class="py-3">
                                                <i class="fas fa-envelope-open fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">No new messages</p>
                                            </div>
                                        </a>
                                    <?php else: ?>
                                        <?php foreach ($unread_messages as $message): 
                                            $client_name = $message['first_name'] . ' ' . $message['last_name'];
                                            $loan_info = $message['loan_id'] ? "Loan #" . $message['loan_number'] : "General Message";
                                        ?>
                                            <a class="dropdown-item d-flex align-items-center" href="loan_messages.php?loan_id=<?php echo $message['loan_id']; ?>">
                                                <div class="me-3">
                                                    <div class="bg-primary icon-circle"><i class="fas fa-envelope text-white"></i></div>
                                                </div>
                                                <div>
                                                    <p class="mb-0 small"><strong><?php echo htmlspecialchars($message['topic']); ?></strong></p>
                                                    <p class="mb-0 small text-truncate" style="max-width: 200px;">
                                                        From: <?php echo htmlspecialchars($client_name); ?> | <?php echo $loan_info; ?>
                                                    </p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <a class="dropdown-item text-center small text-gray-500" href="message.php">Show All Messages</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown no-arrow">
                            <div class="nav-item dropdown no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><span class="d-none d-lg-inline me-2 text-gray-600 small"><?php echo $user['first_name']." ".$user['last_name'];?></span></a>
                                <div class="dropdown-menu shadow dropdown-menu-end animated--grow-in"><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Profile</a><a class="dropdown-item" href="message.php"><i class="fas fa-envelope me-2 fa-sm fa-fw text-gray-400" style="font-size: 12px;"></i>&nbsp;Messages</a>
                                    <div class="dropdown-divider"></div><a class="dropdown-item" href="../index.php"  ><i class="fas fa-sign-out-alt me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Logout</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>



<script>
    // Get the current page filename
    const currentPage = window.location.pathname.split("/").pop();

    // Select all sidebar links
    const navLinks = document.querySelectorAll('#accordionSidebar .nav-link');

    navLinks.forEach(link => {
        // Get the href of the link
        const linkPage = link.getAttribute('href');

        // Compare and add 'active' class if it matches
        if (linkPage === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Auto-refresh message count every 30 seconds
    setInterval(function() {
        fetch('get_message_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.nav-item .badge-counter');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error fetching message count:', error));
    }, 30000);
</script>