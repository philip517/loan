<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

// Fetch active client users
$active_sql = "SELECT user_id, first_name, last_name, username, phone, NRC, email, 
                      occupation, address, date_of_birth, nationality, role, status
               FROM user_table 
               WHERE role IN ('client') AND status = 'active'
               ORDER BY first_name, last_name";
$active_stmt = $pdo->prepare($active_sql);
$active_stmt->execute();
$active_clients = $active_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch deactivated client users
$deactivated_sql = "SELECT user_id, first_name, last_name, username, phone, NRC, email, 
                           occupation, address, date_of_birth, nationality, role, status
                    FROM user_table 
                    WHERE role IN ('client') AND status != 'active'
                    ORDER BY first_name, last_name";
$deactivated_stmt = $pdo->prepare($deactivated_sql);
$deactivated_stmt->execute();
$deactivated_clients = $deactivated_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Clients</title>
    <meta name="description" content="Table for clients">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
    /* Fix the sidebar position */
    #wrapper {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        z-index: 1000;
        overflow-y: auto;
        transition: all 0.3s;
    }

    /* Fix the content wrapper position */
    #content-wrapper {
        margin-left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
    }

    /* Fix the top navbar position */
    .topbar {
        position: fixed !important;
        top: 0;
        left: 250px;
        right: 0;
        z-index: 999;
        height: 70px;
    }

    /* Main content area with proper spacing */
    #content {
        margin-top: 70px; /* Height of top navbar */
        padding: 25px;
        min-height: calc(100vh - 70px);
        overflow-y: auto;
    }

    /* Mobile responsive sidebar */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-250px);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar {
            left: 0;
        }
        
        #content {
            padding: 15px;
        }
    }

    /* Ensure content scrolls properly */
    html, body {
        overflow-x: hidden;
    }
</style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
       <div id="content">
            <div class="container-fluid" style="opacity: 0.97;">
                <div class="container-fluid" style="margin-top: 100px;">
                    <h3 class="text-dark mb-4">Clients</h3>
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                                Active Clients 
                                <span class="badge bg-success ms-1"><?php echo count($active_clients); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="deactivated-tab" data-bs-toggle="tab" data-bs-target="#deactivated" type="button" role="tab" aria-controls="deactivated" aria-selected="false">
                                Deactivated Clients 
                                <span class="badge bg-danger ms-1"><?php echo count($deactivated_clients); ?></span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="clientTabsContent">
                        
                        <!-- Active Clients Tab -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Active Clients</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm active-search" aria-controls="activeTable" placeholder="Search Active Clients..." style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="activeTable">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Occupation</th>
                                                    <th>Status</th>
                                                    <th>NRC</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($active_clients)): ?>
                                                    <tr>
                                                        <td colspan="7" class="empty-state">
                                                            <i class="fas fa-users"></i>
                                                            <p class="text-muted mb-0">No active clients found</p>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($active_clients as $user): ?>
                                                        <tr class="clickable-row" data-user-id="<?php echo $user['user_id']; ?>">
                                                            <td>
                                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($user['occupation'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <span class="badge bg-success status-badge">Active</span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['NRC'] ?? 'N/A'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deactivated Clients Tab -->
                        <div class="tab-pane fade" id="deactivated" role="tabpanel" aria-labelledby="deactivated-tab">
                            <div class="card shadow mt-3">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Deactivated Clients</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-12">
                                            <div class="text-md-end dataTables_filter">
                                                <input type="search" class="form-control form-control-sm deactivated-search" aria-controls="deactivatedTable" placeholder="Search Deactivated Clients..." style="text-align: center;">
                                                <label class="form-label"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table my-0" id="deactivatedTable">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Occupation</th>
                                                    <th>Status</th>
                                                    <th>NRC</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($deactivated_clients)): ?>
                                                    <tr>
                                                        <td colspan="7" class="empty-state">
                                                            <i class="fas fa-user-slash"></i>
                                                            <p class="text-muted mb-0">No deactivated clients found</p>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($deactivated_clients as $user): ?>
                                                        <tr class="clickable-row" data-user-id="<?php echo $user['user_id']; ?>">
                                                            <td>
                                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($user['occupation'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <span class="badge bg-danger status-badge">Deactivated</span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['NRC'] ?? 'N/A'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
                <div class="modal fade text-center" role="dialog" tabindex="-1" id="modal-1">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header"></div>
                            <div class="modal-body">
                                <p>Leaving Already ?</p>
                            </div>
                            <div class="modal-footer text-end" style="text-align: justify;">
                                <p style="text-align: left;">
                                    <button class="btn btn-light" type="button" data-bs-dismiss="modal" style="text-align: center;">No</button>&nbsp;&nbsp;
                                    <a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="login.php">Yes</a>
                                </p>
                                <div class="text-center" style="display: inline-block;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
    // Handle sidebar toggle for mobile screens
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggleTop-1');
        const sidebar = document.querySelector('.sidebar');
        const contentWrapper = document.getElementById('content-wrapper');
        const topbar = document.querySelector('.topbar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && 
                    sidebar.classList.contains('show') &&
                    event.target !== sidebarToggle) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Adjust layout on window resize
        function adjustLayout() {
            if (window.innerWidth > 768) {
                // On larger screens, ensure sidebar is visible
                sidebar.classList.remove('show');
                contentWrapper.style.marginLeft = '250px';
                topbar.style.left = '250px';
            } else {
                // On mobile screens, sidebar is hidden by default
                if (!sidebar.classList.contains('show')) {
                    contentWrapper.style.marginLeft = '0';
                    topbar.style.left = '0';
                }
            }
        }

        window.addEventListener('resize', adjustLayout);
        adjustLayout(); // Initial adjustment
    });
</script>
    <script>
        // Make rows clickable and redirect to user profile
        document.addEventListener('DOMContentLoaded', function() {
            const clickableRows = document.querySelectorAll('.clickable-row');
            
            clickableRows.forEach(row => {
                row.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    if (userId) {
                        window.location.href = `edit_user.php?user_id=${userId}`;
                    }
                });
            });

            // Search functionality for active clients
            const activeSearch = document.querySelector('.active-search');
            if (activeSearch) {
                activeSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#activeTable .clickable-row');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // Search functionality for deactivated clients
            const deactivatedSearch = document.querySelector('.deactivated-search');
            if (deactivatedSearch) {
                deactivatedSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#deactivatedTable .clickable-row');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>