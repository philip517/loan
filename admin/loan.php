

<?php 

session_start();
require 'auth_admin.php';
require '../config.php'; // include your PDO connection

?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Table - Brand</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <nav class="navbar z-3 align-items-start p-0 sidebar sidebar-dark accordion bg-gradient-primary navbar-dark">
            <div class="container-fluid d-flex flex-column p-0"><a class="navbar-brand d-flex justify-content-center align-items-center m-0 sidebar-brand" href="index.php">
                    <div class="me-0 sidebar-brand-icon rotate-n-15"><img class="me-0 pe-0" src="assets/img/white%20logo.png" width="65" height="60" style="transform: rotate(10deg);"></div>
                    <div class="mx-3 sidebar-brand-text"><span>SEFA SATTY</span></div>
                </a>
                <hr class="my-0 sidebar-divider">
                <ul class="navbar-nav text-light" id="accordionSidebar">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                    <li class="nav-item"><a class="nav-link active" href="loan.php"><i class="fas fa-user"></i><span>Loans</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="table.php"><i class="fas fa-table"></i><span>Staff</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="message.php"><i class="fas fa-table"></i><span>Messages</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="far fa-user-circle"></i><span>Login</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-circle"></i><span>Add User</span></a></li>
                </ul>
                <div class="text-center d-none d-md-inline"><button class="btn rounded-circle border-0" id="sidebarToggle" type="button"></button></div>
            </div>
        </nav>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <nav class="navbar navbar-expand fixed-top bg-white shadow z-1 mb-4 topbar">
                    <div class="container-fluid"><button class="btn btn-link d-md-none me-3 rounded-circle" id="sidebarToggleTop-1" type="button"><i class="fas fa-bars"></i></button>
                        <ul class="navbar-nav flex-nowrap ms-auto">
                            <li class="nav-item dropdown d-sm-none no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><i class="fas fa-search"></i></a>
                                <div class="dropdown-menu p-3 dropdown-menu-end animated--grow-in" aria-labelledby="searchDropdown">
                                    <form class="w-100 me-auto navbar-search">
                                        <div class="input-group"><input class="bg-light border-0 form-control small" type="text" placeholder="Search for ..."><button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button></div>
                                    </form>
                                </div>
                            </li>
                            <li class="nav-item mx-1 dropdown no-arrow">
                                <div class="nav-item dropdown no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><span class="badge bg-danger badge-counter">3+</span><i class="fas fa-bell fa-fw"></i></a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-list animated--grow-in">
                                        <h6 class="dropdown-header">alerts center</h6><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3">
                                                <div class="bg-primary icon-circle"><i class="fas fa-file-alt text-white"></i></div>
                                            </div>
                                            <div><span class="small text-gray-500">December 12, 2019</span>
                                                <p>A new monthly report is ready to download!</p>
                                            </div>
                                        </a><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3">
                                                <div class="bg-success icon-circle"><i class="fas fa-donate text-white"></i></div>
                                            </div>
                                            <div><span class="small text-gray-500">December 7, 2019</span>
                                                <p>$290.29 has been deposited into your account!</p>
                                            </div>
                                        </a><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3">
                                                <div class="bg-warning icon-circle"><i class="fas fa-exclamation-triangle text-white"></i></div>
                                            </div>
                                            <div><span class="small text-gray-500">December 2, 2019</span>
                                                <p>Spending Alert: We've noticed unusually high spending for your account.</p>
                                            </div>
                                        </a><a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item mx-1 dropdown no-arrow">
                                <div class="nav-item dropdown no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><span class="badge bg-danger badge-counter">7</span><i class="fas fa-envelope fa-fw"></i></a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-list animated--grow-in">
                                        <h6 class="dropdown-header">alerts center</h6><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar4.jpeg">
                                                <div class="bg-success status-indicator"></div>
                                            </div>
                                            <div class="fw-bold">
                                                <div class="text-truncate"><span>Hi there! I am wondering if you can help me with a problem I've been having.</span></div>
                                                <p class="mb-0 small text-gray-500">Emily Fowler - 58m</p>
                                            </div>
                                        </a><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar2.jpeg">
                                                <div class="status-indicator"></div>
                                            </div>
                                            <div class="fw-bold">
                                                <div class="text-truncate"><span>I have the photos that you ordered last month!</span></div>
                                                <p class="mb-0 small text-gray-500">Jae Chun - 1d</p>
                                            </div>
                                        </a><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar3.jpeg">
                                                <div class="bg-warning status-indicator"></div>
                                            </div>
                                            <div class="fw-bold">
                                                <div class="text-truncate"><span>Last month's report looks great, I am very happy with the progress so far, keep up the good work!</span></div>
                                                <p class="mb-0 small text-gray-500">Morgan Alvarez - 2d</p>
                                            </div>
                                        </a><a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="me-3 dropdown-list-image"><img class="rounded-circle" src="assets/img/avatars/avatar5.jpeg">
                                                <div class="bg-success status-indicator"></div>
                                            </div>
                                            <div class="fw-bold">
                                                <div class="text-truncate"><span>Am I a good boy? The reason I ask is because someone told me that people say this to all dogs, even if they aren't good...</span></div>
                                                <p class="mb-0 small text-gray-500">Chicken the Dog · 2w</p>
                                            </div>
                                        </a><a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                                    </div>
                                </div>
                                <div class="shadow dropdown-list dropdown-menu dropdown-menu-end" aria-labelledby="alertsDropdown"></div>
                            </li>
                            <li class="nav-item dropdown no-arrow">
                                <div class="nav-item dropdown no-arrow"><a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#"><span class="d-none d-lg-inline me-2 text-gray-600 small">Valerie Luna</span><img class="border rounded-circle img-profile" src="assets/img/avatars/avatar1.jpeg" width="32" height="32"></a>
                                    <div class="dropdown-menu shadow dropdown-menu-end animated--grow-in"><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Profile</a><a class="dropdown-item" href="message.php"><i class="fas fa-envelope me-2 fa-sm fa-fw text-gray-400" style="font-size: 12px;"></i>&nbsp;Messages</a><a class="dropdown-item" href="table.php"><i class="fas fa-list me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Clients</a>
                                        <div class="dropdown-divider"></div><a class="dropdown-item" href="#modal-1" data-bs-target="#modal-1" data-bs-toggle="modal"><i class="fas fa-sign-out-alt me-2 fa-sm fa-fw text-gray-400"></i>&nbsp;Logout</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
                <div class="container-fluid" style="margin-top: 100px;">
                    <h3 class="text-dark mb-4">Loans</h3>
                    <div>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation"><a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">Pending</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">Approved</a></li>
                            <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">Rejected</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" role="tabpanel" id="tab-1">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6 col-lg-12">
                                                                    <div class="text-md-end dataTables_filter" id="dataTable_filter-1"><label class="form-label"></label></div>
                                                                </div>
                                                            </div>
                                                            <div class="table-responsive mt-2 table" id="dataTable-2" role="grid" aria-describedby="dataTable_info">
                                                                <table class="table my-0" id="dataTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Name</th>
                                                                            <th>Position</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Airi Satou</td>
                                                                            <td>Accountant</td>
                                                                            <td>Tokyo</td>
                                                                            <td>2008/11/28</td>
                                                                            <td>$162,700</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Angelica Ramos</td>
                                                                            <td>Chief Executive Officer(CEO)</td>
                                                                            <td>London</td>
                                                                            <td>2009/10/09<br></td>
                                                                            <td>$1,200,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Ashton Cox</td>
                                                                            <td>Junior Technical Author</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2009/01/12<br></td>
                                                                            <td>$86,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Bradley Greer</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2012/10/13<br></td>
                                                                            <td>$132,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Brenden Wagner</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2011/06/07<br></td>
                                                                            <td>$206,850</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Brielle Williamson</td>
                                                                            <td>Integration Specialist</td>
                                                                            <td>New York</td>
                                                                            <td>2012/12/02<br></td>
                                                                            <td>$372,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Bruno Nash<br></td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2011/05/03<br></td>
                                                                            <td>$163,500</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Caesar Vance</td>
                                                                            <td>Pre-Sales Support</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/12<br></td>
                                                                            <td>$106,450</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Cara Stevens</td>
                                                                            <td>Sales Assistant</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/06<br></td>
                                                                            <td>$145,600</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Cedric Kelly</td>
                                                                            <td>Senior JavaScript Developer</td>
                                                                            <td>Edinburgh</td>
                                                                            <td>2012/03/29<br></td>
                                                                            <td>$433,060</td>
                                                                        </tr>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td><strong>Name</strong></td>
                                                                            <td><strong>Position</strong></td>
                                                                            <td><strong>Collateral</strong></td>
                                                                            <td>Duration</td>
                                                                            <td><strong>Loan</strong></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 align-self-center">
                                                                    <p id="dataTable_info-1" class="dataTables_info" role="status" aria-live="polite">Showing 1 to 10 of 27</p>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" role="tabpanel" id="tab-2">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6 col-lg-12">
                                                                    <div class="text-md-end dataTables_filter" id="dataTable_filter"><input type="search" class="form-control form-control-sm" aria-controls="dataTable" placeholder="Search" style="text-align: center;"><label class="form-label"></label></div>
                                                                </div>
                                                            </div>
                                                            <div class="table-responsive mt-2 table" id="dataTable-1" role="grid" aria-describedby="dataTable_info">
                                                                <table class="table my-0" id="dataTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Name</th>
                                                                            <th>Position</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Airi Satou</td>
                                                                            <td>Accountant</td>
                                                                            <td>Tokyo</td>
                                                                            <td>2008/11/28</td>
                                                                            <td>$162,700</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Angelica Ramos</td>
                                                                            <td>Chief Executive Officer(CEO)</td>
                                                                            <td>London</td>
                                                                            <td>2009/10/09<br></td>
                                                                            <td>$1,200,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Ashton Cox</td>
                                                                            <td>Junior Technical Author</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2009/01/12<br></td>
                                                                            <td>$86,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Bradley Greer</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2012/10/13<br></td>
                                                                            <td>$132,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Brenden Wagner</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2011/06/07<br></td>
                                                                            <td>$206,850</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Brielle Williamson</td>
                                                                            <td>Integration Specialist</td>
                                                                            <td>New York</td>
                                                                            <td>2012/12/02<br></td>
                                                                            <td>$372,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Bruno Nash<br></td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2011/05/03<br></td>
                                                                            <td>$163,500</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Caesar Vance</td>
                                                                            <td>Pre-Sales Support</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/12<br></td>
                                                                            <td>$106,450</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Cara Stevens</td>
                                                                            <td>Sales Assistant</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/06<br></td>
                                                                            <td>$145,600</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Cedric Kelly</td>
                                                                            <td>Senior JavaScript Developer</td>
                                                                            <td>Edinburgh</td>
                                                                            <td>2012/03/29<br></td>
                                                                            <td>$433,060</td>
                                                                        </tr>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td><strong>Name</strong></td>
                                                                            <td><strong>Position</strong></td>
                                                                            <td><strong>Collateral</strong></td>
                                                                            <td>Duration</td>
                                                                            <td><strong>Loan</strong></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 align-self-center">
                                                                    <p id="dataTable_info" class="dataTables_info" role="status" aria-live="polite">Showing 1 to 10 of 27</p>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" role="tabpanel" id="tab-3">
                                <div class="container">
                                    <div class="card shadow-lg my-5 o-hidden border-0">
                                        <div class="card-body p-0">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="card shadow">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6 col-lg-12">
                                                                    <div class="text-md-end dataTables_filter" id="dataTable_filter-2"><input type="search" class="form-control form-control-sm" aria-controls="dataTable" placeholder="Search" style="text-align: center;"><label class="form-label"></label></div>
                                                                </div>
                                                            </div>
                                                            <div class="table-responsive mt-2 table" id="dataTable-3" role="grid" aria-describedby="dataTable_info">
                                                                <table class="table my-0" id="dataTable">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Name</th>
                                                                            <th>Position</th>
                                                                            <th>Collateral</th>
                                                                            <th>Duration</th>
                                                                            <th>Loan</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Airi Satou</td>
                                                                            <td>Accountant</td>
                                                                            <td>Tokyo</td>
                                                                            <td>2008/11/28</td>
                                                                            <td>$162,700</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Angelica Ramos</td>
                                                                            <td>Chief Executive Officer(CEO)</td>
                                                                            <td>London</td>
                                                                            <td>2009/10/09<br></td>
                                                                            <td>$1,200,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Ashton Cox</td>
                                                                            <td>Junior Technical Author</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2009/01/12<br></td>
                                                                            <td>$86,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Bradley Greer</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2012/10/13<br></td>
                                                                            <td>$132,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Brenden Wagner</td>
                                                                            <td>Software Engineer</td>
                                                                            <td>San Francisco</td>
                                                                            <td>2011/06/07<br></td>
                                                                            <td>$206,850</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar1.jpeg">Brielle Williamson</td>
                                                                            <td>Integration Specialist</td>
                                                                            <td>New York</td>
                                                                            <td>2012/12/02<br></td>
                                                                            <td>$372,000</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar2.jpeg">Bruno Nash<br></td>
                                                                            <td>Software Engineer</td>
                                                                            <td>London</td>
                                                                            <td>2011/05/03<br></td>
                                                                            <td>$163,500</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar3.jpeg">Caesar Vance</td>
                                                                            <td>Pre-Sales Support</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/12<br></td>
                                                                            <td>$106,450</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar4.jpeg">Cara Stevens</td>
                                                                            <td>Sales Assistant</td>
                                                                            <td>New York</td>
                                                                            <td>2011/12/06<br></td>
                                                                            <td>$145,600</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><img class="rounded-circle me-2" width="30" height="30" src="assets/img/avatars/avatar5.jpeg">Cedric Kelly</td>
                                                                            <td>Senior JavaScript Developer</td>
                                                                            <td>Edinburgh</td>
                                                                            <td>2012/03/29<br></td>
                                                                            <td>$433,060</td>
                                                                        </tr>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td><strong>Name</strong></td>
                                                                            <td><strong>Position</strong></td>
                                                                            <td><strong>Collateral</strong></td>
                                                                            <td>Duration</td>
                                                                            <td><strong>Loan</strong></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 align-self-center">
                                                                    <p id="dataTable_info-2" class="dataTables_info" role="status" aria-live="polite">Showing 1 to 10 of 27</p>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
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