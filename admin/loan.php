

<?php 
require 'auth_admin.php';
require '../db_connect.php'; // include your PDO connection

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
        <?php require 'navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content" style="background: rgba(255,255,255,0.09);opacity: 1;filter: blur(0px);"> 
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