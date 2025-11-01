<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

// Fetch all admin and super_admin users from database
$user_sql = "SELECT user_id, first_name, last_name, username, phone, NRC, email, 
                    occupation, address, date_of_birth, nationality, role
             FROM user_table 
             WHERE role IN ('client')
             ORDER BY first_name, last_name";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute();
$client_users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Admins</title>
    <meta name="description" content="Table for administrators">
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
        .table-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
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
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <p class="text-primary m-0 fw-bold">Client Info</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-lg-12">
                                    <div class="text-md-end dataTables_filter" id="dataTable_filter">
                                        <input type="search" class="form-control form-control-sm" aria-controls="dataTable" placeholder="Search Clients..." style="text-align: center;" id="searchInput">
                                        <label class="form-label"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-2 table" id="dataTable" role="grid" aria-describedby="dataTable_info">
                                <table class="table my-0" id="dataTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Occupation</th>
                                            <th>Role</th>
                                            <th>NRC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($client_users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted">No Clients found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($client_users as $user): ?>
                                                <tr class="clickable-row" data-user-id="<?php echo $user['user_id']; ?>">
                                                    <td>
                                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['occupation'] ?? 'N/A'); ?></td>
                                                    <td>
                                                            <span class="badge bg-primary role-badge">client</span>
                                                    
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['NRC'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                  
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-md-6 align-self-center">
                                    <p id="dataTable_info" class="dataTables_info" role="status" aria-live="polite">
                                        Showing <?php echo count($client_users); ?> Clients(s)
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <nav class="d-lg-flex justify-content-lg-end dataTables_paginate paging_simple_numbers">
                                        <ul class="pagination">
                                            <li class="page-item disabled"><a class="page-link" aria-label="Previous" href="#"><span aria-hidden="true">«</span></a></li>
                                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                                            <li class="page-item"><a class="page-link" aria-label="Next" href="#"><span aria-hidden="true">»</span></a></li>
                                        </ul>
                                    </nav>
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

            // Add search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.clickable-row');
                    
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