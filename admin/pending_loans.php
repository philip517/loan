
<?php 

require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loans Tab</title>
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
                <div class="container-fluid" style="margin-top: 100PX;">
                    <div class="d-sm-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-dark mb-0"><strong>LOANS</strong></h3>
                    </div>
                    <div class="row" style="display: flex;text-align: center;">
                        <div class="col">
                            <section class="ps-2 pe-2 pt-3" id="pending_loan_details-1" style="background: rgba(246,194,62,0.13);">
                                <div class="row">
                                    <div class="col" style="text-align: right;"><a class="btn btn-primary btn-sm d-none d-sm-inline-block" role="button" href="loan.php" style="background: var(--bs-danger);text-align: right;">&nbsp;REJECT</a><a class="btn btn-primary btn-sm d-none d-sm-inline-block ms-3 me-3" role="button" href="loan.php" style="background: var(--bs-success);text-align: right;">&nbsp;APPROVE</a><a class="btn btn-primary btn-sm d-none d-sm-inline-block" role="button" href="message.php" style="background: var(--bs-info);text-align: right;">&nbsp;Send Message</a></div>
                                </div>
                                <p class="mt-0 pt-0">Date of Application</p>
                                <div class="row me-0">
                                    <div class="col-md-6 col-lg-6 col-xl-6 mb-4">
                                        <div class="card shadow py-2 border-left-primary">
                                            <div class="card-body text-start">
                                                <h1>Client</h1>
                                                <p>Name:<br>Nrc:<br>Phone:<br><br><br></p>
                                                <h1>Kin</h1>
                                                <p>Name:<br>Nrc:<br>Phone:<br><br><br></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 text-start mb-4">
                                        <div class="card shadow py-2 border-left-success">
                                            <div class="card-body">
                                                <h1>Loan</h1>
                                                <p>Amount:<br>Duration:<br>Interest:<br><br><br></p>
                                                <h1>Collateral&nbsp;</h1>
                                                <p>Name:<br><br><br></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col col-md-12 col-lg-12">
                                        <p class="mt-0 pt-0" style="width: 100%;">Images</p>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 text-start mb-4">
                                        <div class="card shadow py-2 border-left-success">
                                            <div class="card-body">
                                                <h1>Image 1</h1><img src="assets/img/dogs/image3.jpeg" width="199" height="187">
                                                <h1>Image 2</h1><img src="assets/img/dogs/image3.jpeg" width="199" height="187">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="bg-white sticky-footer">
                <div class="container my-auto">
                    <div class="text-center my-auto copyright"><span>Copyright © Brand 2025</span></div>
                </div>
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
            </footer>
        </div><a class="border rounded d-inline scroll-to-top" href="#page-top"><i class="fas fa-angle-up"></i></a>
    </div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>

</html>