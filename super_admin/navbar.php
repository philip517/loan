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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                
                <!-- Loans Management Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="loansDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-hand-holding-usd"></i><span>Loans Management</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="loansDropdown">
                        <li><a class="dropdown-item" href="loan.php"><i class="fas fa-list me-2"></i>All Loans</a></li>
                        <li><a class="dropdown-item" href="loan_request.php"><i class="fas fa-hand-paper me-2"></i>Loan Requests</a></li>
                        
                        <li><a class="dropdown-item" href="overdue.php"><i class="fas fa-exclamation-triangle me-2"></i>Approved Loans Summary</a></li>
                        <li><a class="dropdown-item" href="paid_loan.php"><i class="fas fa-check-circle me-2"></i>Settled Loans</a></li>
                    </ul>
                </li>
                
                <!-- Communications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="communicationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-envelope"></i><span>Communications</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="communicationsDropdown">
                        <li><a class="dropdown-item" href="message.php"><i class="fas fa-envelope me-2"></i>Messages</a></li>
                        <li><a class="dropdown-item" href="notifications.php"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                    </ul>
                </li>
                
                <!-- Financial Management Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="financeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-line"></i><span>Financial Management</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="financeDropdown">
                        <li><a class="dropdown-item" href="finance_main.php"><i class="fas fa-money-bill-wave me-2"></i>Finances</a></li>
                        <li><a class="dropdown-item" href="finance_pending.php"><i class="fas fa-money-bill-wave me-2"></i>Pending</a></li>
                        <li><a class="dropdown-item" href="finance_current.php"><i class="fas fa-money-bill-wave me-2"></i>Current</a></li>
                        <li><a class="dropdown-item" href="finance_paid.php"><i class="fas fa-money-bill-wave me-2"></i>Paid</a></li>
                        <li><a class="dropdown-item" href="finance_rejected.php"><i class="fas fa-money-bill-wave me-2"></i>Rejected</a></li>
                    </ul>
                </li>
                
                <!-- User Management Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users"></i><span>User Management</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="usersDropdown">
                        <li><a class="dropdown-item" href="loan_activity.php"><i class="fas fa-history me-2"></i>Admin Activity</a></li>
                        <li><a class="dropdown-item" href="user.php"><i class="fas fa-user-friends me-2"></i>Clients</a></li>
                        <li><a class="dropdown-item" href="table.php"><i class="fas fa-user-tie me-2"></i>Staff</a></li>
                        <li><a class="dropdown-item" href="add_user.php"><i class="fas fa-user-plus me-2"></i>Add User</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                    </ul>
                </li>
                
                <!-- Quick Links (Non-dropdown items if needed) -->
                <!--
                <li class="nav-item"><a class="nav-link" href="loan_activity.php"><i class="fas fa-history"></i><span>Admin Activity</span></a></li>
                <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
                <li class="nav-item"><a class="nav-link" href="loan_request.php"><i class="fas fa-hand-paper"></i><span>Loan Requests</span></a></li>
                <li class="nav-item"><a class="nav-link" href="table.php"><i class="fas fa-user-tie"></i><span>Staff</span></a></li>
                <li class="nav-item"><a class="nav-link" href="user.php"><i class="fas fa-user-friends"></i><span>Clients</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="fas fa-user-plus"></i><span>Add User</span></a></li>
                -->
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
                                <div class="dropdown-menu shadow dropdown-menu-end animated--grow-in"><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Profile</a><a class="dropdown-item" href="message.php"><i class="fas fa-envelope me-2 fa-sm fa-fw text-gray-400" style="font-size: 12px;"></i>&nbsp;Messages</a><a class="dropdown-item" href="user.php"><i class="fas fa-list me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Clients</a>
                                    <div class="dropdown-divider"></div><a class="dropdown-item" href="../index.php"  ><i class="fas fa-sign-out-alt me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Logout</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

<style>
    /* Change dropdown colors from dark to light blue */
    .sidebar .dropdown-menu {
        background-color: rgba(165, 210, 245, 0.95) !important; /* Slightly lighter blue */
        border: 1px solid rgba(140, 195, 240, 0.5);
    }
    
    .sidebar .dropdown-item {
        color: #2c3e50 !important; /* Dark text for contrast on light blue */
    }
    
    .sidebar .dropdown-item:hover,
    .sidebar .dropdown-item:focus {
        color: #1a252f !important;
        background-color: rgba(150, 200, 240, 0.3) !important; /* Slightly lighter blue hover */
    }
    
    .sidebar .dropdown-item.active,
    .sidebar .dropdown-item.active:hover,
    .sidebar .dropdown-item.active:focus {
        color: #ffffff !important;
        background-color: #3498db !important; /* Blue for active item */
    }
    
    .sidebar .dropdown-divider {
        border-color: rgba(140, 195, 240, 0.5) !important;
    }
</style>

<?php

?>

<script>
    // Get the current page filename
    const currentPage = window.location.pathname.split("/").pop();
    
    // Function to set active class for dropdown items
    function setActiveNavItem() {
        // Select all sidebar links (including dropdown items)
        const navLinks = document.querySelectorAll('#accordionSidebar .nav-link, #accordionSidebar .dropdown-item');
        
        navLinks.forEach(link => {
            // Get the href of the link
            const linkPage = link.getAttribute('href');
            
            // Remove active class from all links first
            link.classList.remove('active');
            
            // Compare and add 'active' class if it matches current page
            if (linkPage === currentPage) {
                link.classList.add('active');
                
                // If this is a dropdown item, also mark the parent dropdown as active
                if (link.classList.contains('dropdown-item')) {
                    const parentDropdown = link.closest('.dropdown');
                    if (parentDropdown) {
                        const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
                        if (dropdownToggle) {
                            dropdownToggle.classList.add('active');
                        }
                    }
                }
            }
        });
    }
    
    // Call the function when page loads
    setActiveNavItem();
    
    // Also update when clicking on dropdown items
    document.querySelectorAll('#accordionSidebar .dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all nav links
            document.querySelectorAll('#accordionSidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Also mark the parent dropdown as active
            const parentDropdown = this.closest('.dropdown');
            if (parentDropdown) {
                const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    dropdownToggle.classList.add('active');
                }
            }
        });
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