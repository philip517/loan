<?php 
require 'auth_client.php';
require '../db_connect.php'; // include your PDO connection

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}
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
            <!-- Main form that collects all data -->
            <form method="POST" action="confirm_loan.php" class="user" id="loanApplicationForm" enctype="multipart/form-data">
                <div>
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation"><a class="nav-link active" role="tab" data-bs-toggle="tab" href="#tab-1">Personal Details</a></li>
                        <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-2">Collateral Details</a></li>
                        <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-3">Next Of Kin Details</a></li>
                        <li class="nav-item" role="presentation"><a class="nav-link" role="tab" data-bs-toggle="tab" href="#tab-4">Loan Details</a></li>
                    </ul>
                    <div class="tab-content">
                        <!-- Personal Details Tab -->
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
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" id="first_name" placeholder="First Name" name="first_name" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <input class="form-control form-control-user" type="text" id="last_name" placeholder="Last Name" name="last_name" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" data-bs-toggle="tooltip" data-bss-tooltip="" type="date" name="date_of_birth" title="Date of Birth" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <select class="form-select form-control-user" name="occupation" required>
                                                                <optgroup label="Position">
                                                                    <option value="" selected disabled>Select Occupation</option>
                                                                    <option value="student">Student</option>
                                                                    <option value="worker">Worker</option>
                                                                    <option value="unemployed">Unemployed</option>
                                                                    <option value="entreprenuer">Entrepreneur</option>
                                                                </optgroup>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" name="nrc" placeholder="ID / NRC" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <select class="form-select form-control-user" name="nationality" required>
                                                                <optgroup label="Nationality">
                                                                    <option value="" selected disabled>Select Nationality</option>
                                                                    <option value="Zambian">Zambian</option>
                                                                    <option value="Other">Other</option>
                                                                </optgroup>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" placeholder="Phone" name="phone" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <input class="form-control form-control-user" type="text" placeholder="Address" name="address" required>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12 mb-3 mb-sm-0">
                                                            <label class="form-label">Upload ID or NRC</label>
                                                            <input class="rounded-0 form-control form-control-user" type="file" name="id_image" accept="image/*" required>
                                                        </div>
                                                    </div>
                                                    <h4 class="text-center text-dark mb-4">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-primary" onclick="nextTab(2)">NEXT</button>
                                                        </div>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Collateral Details Tab -->
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
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" id="collateral_name" placeholder="Item Name" name="collateral_name" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label class="form-label">Upload image 1</label>
                                                        <input class="rounded-0 form-control ms-3 ps-3 form-control-user" type="file" name="collateral_image1" accept="image/*" required>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label class="form-label">Upload image 2</label>
                                                        <input class="bg-white rounded-0 form-control ms-3 ps-3 form-control-user" type="file" name="collateral_image2" accept="image/*" required style="border-radius: 253px;opacity: 1;background: rgb(255,255,255);">
                                                    </div>
                                                    <hr>
                                                    <h4 class="text-center text-dark mb-4">
                                                        <button type="button" class="btn btn-primary me-5" onclick="prevTab(1)">BACK</button>
                                                        <button type="button" class="btn btn-primary me-5" onclick="nextTab(3)">NEXT</button>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Next Of Kin Details Tab -->
                        <div class="tab-pane" role="tabpanel" id="tab-3">
                            <div class="container">
                                <div class="card shadow-lg my-5 o-hidden border-0">
                                    <div class="card-body p-0">
                                        <div class="row">
                                            <div class="col-lg-11">
                                                <div class="p-5">
                                                    <div class="text-center">
                                                        <h4 class="text-dark mb-4">Next Of Kin Details</h4>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" id="kin_first_name" placeholder="First Name" name="kin_first_name" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <input class="form-control form-control-user" type="text" id="kin_last_name" placeholder="Last Name" name="kin_last_name" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="text" id="kin_nrc" placeholder="NRC/ID" name="kin_nrc" required>
                                                        </div>
                                                    </div>
                                                    <input class="form-control form-control-user" type="text" name="kin_phone" placeholder="Phone Number" required>
                                                    <div class="mb-3"></div>
                                                    <div class="mb-3">
                                                        <h4 class="text-center text-dark mb-4">
                                                            <button type="button" class="btn btn-primary me-3" onclick="prevTab(2)">BACK</button>
                                                            <button type="button" class="btn btn-primary ms-3 me-0" onclick="nextTab(4)">NEXT</button>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loan Details Tab -->
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
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12 mb-3 mb-sm-0">
                                                            <input class="form-control form-control-user" type="number" max="5000" min="500" name="loan_amount" placeholder="k500-k5000" required id="loan_amount">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12">
                                                            <select class="form-select form-control-user" name="loan_duration" required id="loan_duration">
                                                                <optgroup label="Duration">
                                                                    <option value="" selected disabled>Select Duration</option>
                                                                    <option value="1">1 Week</option>
                                                                    <option value="2">2 Weeks</option>
                                                                    <option value="3">3 Weeks</option>
                                                                    <option value="4">4 Weeks</option>
                                                                </optgroup>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-6 col-md-12 mt-2">
                                                            <span class="mt-0">Interest: K<span id="interest_amount">0.00</span> (<span id="interest_percentage">0%</span>)</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-sm-6 col-md-12 mt-2">
                                                            <span class="mt-0"><strong>Total Repayment: K<span id="total_repayment">0.00</span></strong></span>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h4 class="text-center text-dark mb-4">
                                                        <button type="button" class="btn btn-primary me-5" onclick="prevTab(3)">BACK</button>
                                                        <button type="button" class="btn btn-primary me-5" id="reviewButton" disabled>REVIEW</button>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden submit button for the form -->
                <input type="submit" id="realApplyButton" style="display: none;">
            </form>
        </div>

        <!-- Review Modal -->
        <div class="modal fade" role="dialog" tabindex="-1" id="modal-1">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header text-center">
                        <h4 class="modal-title">Loan Application Review</h4>
                        <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <section class="ps-2 pe-2 pt-3" id="pending_loan_details-1" style="background: rgba(246,194,62,0.13);">
                            <div class="row me-0">
                                <div class="col-md-6 col-lg-6 col-xl-6 mb-4">
                                    <h5>Client Details</h5>
                                    <p><strong>First Name:</strong> <span id="review_first_name"></span><br>
                                    <strong>Last Name:</strong> <span id="review_last_name"></span><br>
                                    <strong>NRC:</strong> <span id="review_nrc"></span><br>
                                    <strong>Phone:</strong> <span id="review_phone"></span><br>
                                    <strong>Occupation:</strong> <span id="review_occupation"></span><br>
                                    <strong>Date Of Birth:</strong> <span id="review_dob"></span><br>
                                    <strong>Address:</strong> <span id="review_address"></span></p>
                                </div>
                                <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 text-start mb-4">
                                    <h5>Loan Details</h5>
                                    <p><strong>Amount:</strong> K<span id="review_loan_amount"></span><br>
                                    <strong>Duration:</strong> <span id="review_duration"></span> week(s)<br>
                                    <strong>Interest:</strong> K<span id="review_interest_amount"></span><br>
                                    <strong>Total Repayment:</strong> K<span id="review_total_repayment"></span></p>
                                    
                                    <h5>Next of Kin</h5>
                                    <p><strong>Name:</strong> <span id="review_kin_name"></span><br>
                                    <strong>NRC:</strong> <span id="review_kin_nrc"></span><br>
                                    <strong>Phone:</strong> <span id="review_kin_phone"></span></p>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12 text-start mb-4">
                                    <h5>Collateral Details</h5>
                                    <p><strong>Item Name:</strong> <span id="review_collateral"></span></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Image 1 Preview</h6>
                                            <div id="image1_preview" class="border p-2 text-center" style="min-height: 100px;">
                                                No image selected
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Image 2 Preview</h6>
                                            <div id="image2_preview" class="border p-2 text-center" style="min-height: 100px;">
                                                No image selected
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="button" id="finalApplyButton">Apply for Loan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    
    <script src="assets/js/review.js"></script>
</body>
</html>