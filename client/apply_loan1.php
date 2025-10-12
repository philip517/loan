
<?php 

require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Loan Application</title>
    <meta name="description" content="Loan Application page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body>
    <div id="wrapper">
        
       <?php require 'navbar.php' ?>

                <div class="container-fluid" style="margin-top: 100PX;">
                    <form class="user">
                        <div>
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation"><a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">Personal Details</a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">Collateral Details</a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">Next Of Kin Details</a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-4">Loan Details</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" role="tabpanel" id="tab-1">
                                    <div class="container">
                                        <div class="card shadow-lg my-5 o-hidden border-0">
                                            <div class="card-body p-0">
                                                <div class="row">
                                                    <div class="col-lg-11">
                                                        <div class="p-5">
                                                            <div class="text-center">
                                                                <h4 class="text-dark mb-4">Personal Details</h4>
                                                            </div>
                                                            <form class="user">
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" id="exampleFirstName-1" placeholder="First Name" name="first_name"></div>
                                                                    <div class="col-sm-6"><input class="form-control form-control-user" type="text" id="exampleLastName-1" placeholder="Last Name" name="last_name"></div>
                                                                </div>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 mb-3 mb-sm-0"><input class="form-control form-control-user" data-bs-toggle="tooltip" data-bss-tooltip="" type="date" name="dateofbirth" value="date of birth" title="Date of Birth"></div>
                                                                    <div class="col-sm-6"><select class="form-select form-control-user">
                                                                            <optgroup label="Position">
                                                                                <option value="1" selected="">Student</option>
                                                                                <option value="2">Worker</option>
                                                                                <option value="3">Unemployed</option>
                                                                                <option value="3">Entreprenuer</option>
                                                                            </optgroup>
                                                                        </select></div>
                                                                </div>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" name="id" placeholder="ID / NRC"></div>
                                                                    <div class="col-sm-6"><select class="form-select form-control-user">
                                                                            <optgroup label="Nationality">
                                                                                <option value="1" selected="">Zambian</option>
                                                                                <option value="2">Other</option>
                                                                            </optgroup>
                                                                        </select></div>
                                                                </div>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" placeholder="phone" name="phone"></div>
                                                                    <div class="col-sm-6"><input class="form-control form-control-user" type="text" placeholder="address" name="address"></div>
                                                                </div>
                                                                <hr>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 col-md-12 mb-3 mb-sm-0"><label class="form-label">Upload ID or NRC</label><input class="rounded-0 form-control form-control-user" type="file" placeholder="phone" name="phone"></div>
                                                                </div>
                                                            </form>
                                                            <h4 class="text-center text-dark mb-4">
                                                                <div class="btn-group" role="group"><a class="btn btn-primary" role="button" href="apply_loan.php#tab-2">NEXT</a></div>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" role="tabpanel" id="tab-2">
                                    <div class="container">
                                        <div class="card shadow-lg my-5 o-hidden border-0">
                                            <div class="card-body p-0">
                                                <div class="row">
                                                    <div class="col-lg-11">
                                                        <div class="p-5">
                                                            <div class="text-center">
                                                                <h4 class="text-dark mb-4">Collateral Details</h4>
                                                            </div>
                                                            <form class="user">
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 col-md-12 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" id="exampleFirstName" placeholder="Item Name" name="item_name"></div>
                                                                </div>
                                                                <div class="mb-3 row"><label class="form-label">Upload image 1</label><input class="rounded-0 form-control ms-3 ps-3 form-control-user" type="file"></div>
                                                                <div class="mb-3 row"><label class="form-label">Upload image 2</label><input class="bg-white rounded-0 form-control ms-3 ps-3 form-control-user" type="file" style="border-radius: 253px;opacity: 1;background: rgb(255,255,255);"></div>
                                                                <hr>
                                                            </form>
                                                            <h4 class="text-center text-dark mb-4"><a href="/"></a><a class="btn btn-primary me-5" role="button" href="#tab-2">BACK</a><a href="#tab-4"></a><a class="btn btn-primary me-5" role="button" href="#tab-2">NEXT</a></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" role="tabpanel" id="tab-3">
                                    <div class="container">
                                        <div class="card shadow-lg my-5 o-hidden border-0">
                                            <div class="card-body p-0">
                                                <div class="row">
                                                    <div class="col-lg-11">
                                                        <div class="p-5">
                                                            <div class="text-center">
                                                                <h4 class="text-dark mb-4">Next Of Kin 1 Details</h4>
                                                            </div>
                                                            <form class="user">
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" id="exampleFirstName-6" placeholder="First Name" name="first_name"></div>
                                                                    <div class="col-sm-6"><input class="form-control form-control-user" type="text" id="exampleLastName-3" placeholder="Last Name" name="last_name"></div>
                                                                </div>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 col-md-12 mb-3 mb-sm-0"><input class="form-control form-control-user" type="text" id="exampleFirstName-7" placeholder="NRC/ID" name="ID_number"></div>
                                                                </div><input class="form-control form-control-user" type="text" name="number" placeholder="Phone Number">
                                                                <div class="mb-3"></div>
                                                                <div class="mb-3">
                                                                    <h4 class="text-center text-dark mb-4"><a href="/"></a><a class="btn btn-primary me-3" role="button" href="#tab-2">BACK</a><a href="#tab-4"></a><a class="btn btn-primary ms-3 me-0" role="button" href="#tab-2">NEXT</a></h4>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" role="tabpanel" id="tab-4">
                                    <div class="container">
                                        <div class="card shadow-lg my-5 o-hidden border-0">
                                            <div class="card-body p-0">
                                                <div class="row">
                                                    <div class="col-lg-11">
                                                        <div class="p-5">
                                                            <div class="text-center">
                                                                <h4 class="text-dark mb-4">Loan Details</h4>
                                                            </div>
                                                            <form class="user">
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 col-md-12 mb-3 mb-sm-0"><input class="form-control form-control-user" type="number" max="5000" min="500" name="loan_amount" placeholder="k1000-k5000"></div>
                                                                </div>
                                                                <div class="mb-3"></div>
                                                                <div class="mb-3"></div>
                                                                <div class="mb-3 row">
                                                                    <div class="col-sm-6 col-md-12"><select class="form-select form-control-user">
                                                                            <optgroup label="Duration">
                                                                                <option value="1"> 1 Week</option>
                                                                                <option value="2">2 Weeks</option>
                                                                                <option value="3">3 Weeks</option>
                                                                                <option value="4">4 Weeks</option>
                                                                                <option value=""></option>
                                                                            </optgroup>
                                                                        </select></div>
                                                                    <div class="col-sm-6 col-md-12 mt-2"><span class="mt-0">Interest:</span></div>
                                                                </div>
                                                                <hr>
                                                                <hr>
                                                            </form>
                                                            <h4 class="text-center text-dark mb-4"><a href="#tab-2"></a><a class="btn btn-primary me-5" role="button" href="#tab-2">BACK</a><a href="" type="button"></a><a class="btn btn-primary me-5" role="button" href="confirm_loan.php" >APPLY</a></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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