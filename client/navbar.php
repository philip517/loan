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

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <style>
        /* Horizontal Navbar Styles */
        .horizontal-navbar {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            width: 100%;
            margin: 0;
            height: 80px;
            display: flex;
            align-items: center;
        }

        .horizontal-navbar .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
            max-width: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Add padding to body to prevent content from being hidden behind fixed navbar */
        body {
            padding-top: 80px;
            margin: 0;
        }

        .navbar-brand {
            padding: 0;
            margin-right: 2rem;
            min-width: 200px;
        }

        .navbar-brand img {
            transform: rotate(-7deg);
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 1rem 1.5rem;
            margin: 0 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 60px;
        }

        .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: #fff !important;
            background: rgba(255,255,255,0.15);
            font-weight: 600;
        }

        .nav-link i, .nav-link svg {
            margin-right: 0;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            min-width: 30px;
            text-align: center;
        }

        .nav-link span {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e3e6f0;
        }

        /* Center the main navigation */
        .main-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }

        .main-navigation .navbar-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
        }

        /* Right side navigation */
        .right-navigation {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 200px;
        }

        .right-navigation .nav-link {
            flex-direction: row;
            min-height: auto;
        }

        .right-navigation .nav-link i, 
        .right-navigation .nav-link svg {
            margin-bottom: 0;
            margin-right: 0.5rem;
            font-size: 1.3rem;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            transform: scale(0.9);
            transform-origin: top right;
        }

        .nav-item {
            position: relative;
        }

        /* Dropdown Styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-top: 5px;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .horizontal-navbar {
                height: 70px;
            }

            body {
                padding-top: 70px;
            }

            .horizontal-navbar .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .nav-link {
                padding: 0.75rem 1rem;
                margin: 0 0.25rem;
                min-height: 50px;
            }

            .nav-link i, .nav-link svg {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }

            .nav-link span {
                font-size: 0.75rem;
            }

            .navbar-brand {
                min-width: 150px;
                margin-right: 1rem;
            }

            .navbar-brand-text span {
                font-size: 0.9rem;
            }

            .right-navigation {
                min-width: 150px;
            }

            .right-navigation .nav-link i, 
            .right-navigation .nav-link svg {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .horizontal-navbar {
                height: 65px;
                overflow-x: auto;
            }

            body {
                padding-top: 65px;
            }

            .nav-link {
                padding: 0.5rem 0.75rem;
                margin: 0 0.15rem;
            }

            .nav-link i, .nav-link svg {
                font-size: 1.2rem;
                margin-bottom: 0.2rem;
            }

            .nav-link span {
                font-size: 0.7rem;
            }

            .navbar-brand {
                min-width: 120px;
                margin-right: 0.5rem;
            }

            .right-navigation {
                min-width: 120px;
            }

            .right-navigation .nav-link span {
                display: none;
            }

            .right-navigation .nav-link i, 
            .right-navigation .nav-link svg {
                margin-right: 0;
                font-size: 1.1rem;
            }
        }

        /* Ensure full width on all screen sizes */
        @media (max-width: 1200px) {
            .horizontal-navbar .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (max-width: 992px) {
            .horizontal-navbar .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .horizontal-navbar .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .horizontal-navbar .container-fluid {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Fixed Full Width Horizontal Navbar -->
    <nav class="navbar navbar-expand horizontal-navbar" id="horizontalNavbar">
        <div class="container-fluid">
         
            <!-- Centered Main Navigation -->
            <div class="main-navigation">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1.5em" viewBox="0 0 24 24" width="1.5em" fill="currentColor">
                                <path d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"></path>
                            </svg>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="loan.php">
                            <svg xmlns="http://www.w3.org/2000/svg" height="1.5em" viewBox="0 0 24 24" width="1.5em" fill="currentColor">
                                <path d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"></path>
                            </svg>
                            <span>Loans</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply_loan.php">
                            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="1.5em" viewBox="0 0 24 24" width="1.5em" fill="currentColor">
                                <rect fill="none" height="24" width="24"></rect>
                                <path d="M14,2H6C4.9,2,4,2.9,4,4v16c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V8L14,2z M15,11h-4v1h3c0.55,0,1,0.45,1,1v3 c0,0.55-0.45,1-1,1h-1v1h-2v-1H9v-2h4v-1h-3c-0.55,0-1-0.45-1-1v-3c0-0.55,0.45-1,1-1h1V8h2v1h2V11z"></path>
                            </svg>
                            <span>Apply Loan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Right side navigation items -->
            <div class="right-navigation">
                <ul class="navbar-nav">
                    <!-- Notifications Dropdown -->
                </ul>
            </div>
        </div>
    </nav>

    <script>
        // Active link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split("/").pop();
            const navLinks = document.querySelectorAll('.horizontal-navbar .nav-link');
            
            navLinks.forEach(link => {
                const linkPage = link.getAttribute('href');
                if (linkPage === currentPage) {
                    link.classList.add('active');
                }
            });
        });

        // REMOVED auto-hide functionality to keep navbar always visible
    </script>
</body>
</html>