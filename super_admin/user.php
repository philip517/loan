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
        .sticky-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60px;
        }
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
        .role-badge {
            font-size: 0.75em;
            padding: 0.25em 0.6em;
        }
        .status-badge {
            font-size: 0.7em;
            padding: 0.3em 0.6em;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }
        .tab-pane {
            padding-top: 1rem;
        }
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
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