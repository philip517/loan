
<?php 

require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Messaging</title>
    <meta name="description" content="Messaging Tab">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;filter: blur(0px);">
                <div class="container-fluid top-0 overflow-scroll" style="margin-top: 100PX;">
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>Messages</strong></h3>
                    </div>
                    <div class="row" style="text-align: justify;">
                        <div class="col col-lg-10" style="margin: 0px;margin-top: 00PX;">
                            <div class="card ms-0 ps-0" style="text-align: right;margin: 10px;padding: 10px;background: var(--bs-primary-bg-subtle);">
                                <div class="modal fade text-center" role="dialog" tabindex="-1" id="modal-1">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header"></div>
                                            <div class="modal-body">
                                                <p>Leaving Already ?</p>
                                            </div>
                                            <div class="modal-footer text-end" style="text-align: justify;">
                                                <p style="text-align: left;"><button class="btn btn-light" type="button" data-bs-dismiss="modal" style="text-align: center;">No</button>&nbsp;&nbsp;<a class="btn btn-primary" role="button" style="background: var(--bs-danger);" href="login.php">Yes</a></p>
                                                <div class="text-center" style="display: inline-block;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-start ms-3 me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar4.jpeg">
                                    <div class="bg-success status-indicator"></div>
                                </div>
                                <div class="fw-bold">
                                    <div class="text-truncate" style="text-align: left;"><span class="text-start ms-3" style="margin: 5px;">Hi there! I am wondering if you can help me with a problem I've been having.</span></div>
                                    <p class="ms-3 mb-0 small text-gray-500" style="text-align: left;">Emily Fowler - 58m</p>
                                </div>
                            </div>
                            <div class="card ms-0 ps-0" style="text-align: right;margin: 10px;padding: 10px;">
                                <div class="me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar4.jpeg">
                                    <div class="bg-success status-indicator"></div>
                                </div>
                                <div class="fw-bold">
                                    <div class="text-truncate"><span style="margin: 5px;">Hi there! I am wondering if you can help me with a problem I've been having.</span></div>
                                    <p class="me-2 mb-0 small text-gray-500">Emily Fowler - 58m</p>
                                </div>
                            </div>
                        </div>
                        <div class="col col-lg-10">
                            <div class="card ms-0 ps-0" style="text-align: right;margin: 10px;padding: 10px;">
                                <div class="card-body p-sm-5">
                                    <form class="user" method="post">
                                        <div class="mb-3" name="topic"><select class="form-select form-control-user" id="type" name="type" required="">
                                                <option value="messsage">Message</option>
                                                <option value="notification">Notification</option>
                                            </select></div>
                                        <div class="mb-3" name="topic" placeholder="this is the topic of the message"><input class="form-control form-control-user" type="text" id="topic" name="topic" placeholder="this is the topic of the message"></div>
                                        <div class="mb-3"><textarea class="form-control" id="message" name="message" rows="6" placeholder="Message"></textarea></div>
                                        <div><button class="btn btn-primary w-100 d-block" type="submit">Send </button></div>
                                    </form>
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
        </div><a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>

</html>