<?php
// Fetch admin details
$stmt = $pdo->prepare("SELECT first_name,last_name FROM user_table WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Include and get notification data
$notification_data = include 'pending_notification.php';
$notifications = $notification_data['notifications'] ?? [];
$unread_count = $notification_data['unread_count'] ?? 0;
?>

<nav class="navbar fixed-top position-static float-start align-items-start p-0 sidebar sidebar-dark accordion bg-gradient-primary navbar-dark">
    <div class="container-fluid d-flex flex-column p-0">
        <a class="navbar-brand d-flex justify-content-center align-items-center m-0 sidebar-brand" href="#">
            <img src="assets/img/white%20logo.png" width="63" height="78" style="transform: rotate(-7deg);">
            <div class="mx-3 sidebar-brand-text">
                <span><?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></span>
            </div>
        </a>
        <hr class="my-0 sidebar-divider">
        <ul class="navbar-nav text-light" id="accordionSidebar">
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="messages.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 24 24" width="1em" fill="currentColor">
                    <path d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"></path>
                </svg>
                <span>Messages</span>
            </a></li>
            <li class="nav-item"><a class="nav-link" href="loan.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 24 24" width="1em" fill="currentColor">
                    <path d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"></path>
                </svg>
                <span>Loans</span>
            </a></li>
            <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
            <li class="nav-item mb-0"><a class="nav-link" href="apply_loan.php">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="1em" viewBox="0 0 24 24" width="1em" fill="currentColor">
                    <rect fill="none" height="24" width="24"></rect>
                    <path d="M14,2H6C4.9,2,4,2.9,4,4v16c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V8L14,2z M15,11h-4v1h3c0.55,0,1,0.45,1,1v3 c0,0.55-0.45,1-1,1h-1v1h-2v-1H9v-2h4v-1h-3c-0.55,0-1-0.45-1-1v-3c0-0.55,0.45-1,1-1h1V8h2v1h2V11z"></path>
                </svg>
                <span>Apply for Loan</span>
            </a></li>
        </ul>
        <div class="text-center d-none d-md-inline">
            <button class="btn rounded-circle border-0" id="sidebarToggle" type="button"></button>
        </div>
    </div>
</nav>

<div class="d-flex flex-column" id="content-wrapper">
    <div id="content">
        <nav class="navbar navbar-expand fixed-top bg-white shadow z-3 mb-4 topbar">
            <div class="container-fluid">
                <button class="btn btn-link d-md-none me-3 rounded-circle" id="sidebarToggleTop-1" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="navbar-nav flex-nowrap ms-auto">
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <div class="nav-item dropdown no-arrow">
                            <a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger badge-counter" style="position: absolute; transform: scale(0.7); transform-origin: top right; top: 0px; right: 0px;"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                                <i class="fas fa-bell fa-fw"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-list animated--grow-in">
                                <h6 class="dropdown-header">Notifications Center</h6>
                                
                                <?php if (empty($notifications)): ?>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <div class="me-3 dropdown-list-image">
                                            <i class="fas fa-bell-slash text-gray-400 fa-2x"></i>
                                        </div>
                                        <div class="fw-normal">
                                            <div class="text-muted small">No notifications</div>
                                            <span class="small text-gray-500">You're all caught up!</span>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <a class="dropdown-item d-flex align-items-center" href="notification_details.php?id=<?php echo $notification['notification_id']; ?>">
                                            <div class="me-3 dropdown-list-image">
                                                <?php if ($notification['type'] === 'broadcast'): ?>
                                                    <i class="fas fa-bullhorn text-warning fa-2x"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user-tag text-primary fa-2x"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fw-normal">
                                                <div class="text-truncate" style="max-width: 200px;">
                                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                                </div>
                                                <div class="small text-gray-500 text-truncate" style="max-width: 200px;">
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?>
                                                    <?php if ($notification['type'] === 'broadcast'): ?>
                                                        <span class="badge bg-info ms-2">All Users</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success ms-2">For You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <a class="dropdown-item text-center small text-gray-500" href="notifications.php">Show All Notifications</a>
                            </div>
                        </div>
                    </li>

                    <!-- Messages Dropdown (keep existing messages dropdown if needed) -->
                    <!-- <li class="nav-item dropdown no-arrow mx-1"> ... </li> -->

                    <!-- User Profile Dropdown -->
                    <div class="d-none d-sm-block topbar-divider"></div>
                    <li class="nav-item dropdown no-arrow">
                        <div class="nav-item dropdown no-arrow">
                            <a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                <span class="d-none d-lg-inline me-2 text-gray-600 small"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                <img class="border rounded-circle img-profile" src="assets/img/avatars/avatar1.jpeg">
                            </a>
                            <div class="dropdown-menu shadow dropdown-menu-end animated--grow-in">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Profile
                                </a>
                                <a class="dropdown-item" href="notifications.php">
                                    <i class="fas fa-bell me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Notifications
                                    <?php if ($unread_count > 0): ?>
                                        <span class="badge bg-danger ms-2"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a class="dropdown-item" href="loan.php">
                                    <i class="fas fa-list me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Loans
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#modal-1" data-bs-target="#modal-1" data-bs-toggle="modal">
                                    <i class="fas fa-sign-out-alt me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Logout
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <style>
            .sidebar-badge {
                position: absolute;
                transform: scale(0.7);
                transform-origin: top right;
                top: 8px;
                right: 8px;
            }
            .nav-item {
                position: relative;
            }
        </style>

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
        </script>