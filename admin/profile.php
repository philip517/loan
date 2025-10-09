
<?php 


require 'auth_admin.php';
require '../config.php'; // include your PDO connection

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Profile</title>
    <meta name="description" content="Profile page">
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
                    <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="loan.php"><i class="fas fa-user"></i><span>Loans</span></a></li>
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
                    <h3 class="text-dark mb-4">Profile</h3>
                    <div class="row d-flex justify-content-center mb-3">
                        <div class="col-lg-8">
                            <div class="row mb-3 d-none">
                                <div class="col">
                                    <div class="card text-white bg-primary shadow">
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col">
                                                    <p class="m-0">Peformance</p>
                                                    <p class="m-0"><strong>65.2%</strong></p>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-rocket fa-2x"></i></div>
                                            </div>
                                            <p class="text-white-50 m-0 small"><i class="fas fa-arrow-up"></i>&nbsp;5% since last month</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card text-white bg-success shadow">
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col">
                                                    <p class="m-0">Peformance</p>
                                                    <p class="m-0"><strong>65.2%</strong></p>
                                                </div>
                                                <div class="col-auto"><i class="fas fa-rocket fa-2x"></i></div>
                                            </div>
                                            <p class="text-white-50 m-0 small"><i class="fas fa-arrow-up"></i>&nbsp;5% since last month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="card shadow mb-3">
                                        <div class="card-header py-3">
                                            <p class="text-primary m-0 fw-bold">User Credentials</p>
                                        </div>
                                        <div class="card-body">
                                            <form class="user">
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="username"><strong>Username</strong></label><input class="form-control form-control-user" type="text" id="username" placeholder="user.name" name="username"></div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="email"><strong>Email Address</strong></label><input class="form-control form-control-user" type="email" id="email" placeholder="user@example.com" name="email"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="first_name"><strong>First Name</strong></label><input class="form-control form-control-user" type="text" id="first_name" placeholder="John" name="first_name"></div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="last_name"><strong>Last Name</strong></label><input class="form-control form-control-user" type="text" id="last_name" aria-describedby="" placeholder="Doe" name="emaillast_name"></div>
                                                    </div>
                                                </div>
                                                <div class="text-center mb-3"><button class="btn btn-primary btn-sm form-control-user" type="submit">Save Settings</button><a class="btn btn-primary btn-sm ms-4 mt-2 form-control-user" role="button" href="#modal-2" data-bs-target="#modal-2" data-bs-toggle="modal">Change Password</a></div>
                                            </form>
                                        </div>
                                        <div class="modal fade" role="dialog" tabindex="-1" id="modal-2">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="text-align: center;">
                                                        <h4 class="modal-title" style="text-align: center;width: 100%;">Change Password</h4>
                                                    </div>
                                                    <div class="modal-body" style="height: 335.3px;width: 100%;">
                                                        <form class="user">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="username-1"><strong>New Password</strong></label><input class="form-control form-control-user" type="password" id="new_password" name="new_password" placeholder="***************"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="first_name-1"><strong>Comfirm New Password</strong></label><input class="form-control form-control-user" type="password" id="conf_new_password" name="com_password" placeholder="******************"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="first_name-2"><strong>Password change Phrase</strong></label><input class="form-control form-control-user" type="text" id="phrase" name="reset_phrase" placeholder="phrase in case i forget password"></div>
                                                                </div>
                                                            </div>
                                                            <div class="text-center mb-3"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button><a class="btn btn-success btn-sm ms-9" role="button" style="--bs-body-color: rgba(51,69,204,0.37);" href="profile.php" target="_top">Save Password</a></div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card shadow mb-3">
                                        <div class="card-header py-3">
                                            <p class="text-primary m-0 fw-bold">User Personal Details</p>
                                        </div>
                                        <div class="card-body">
                                            <form class="user">
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="username-1"><strong>Date of Birth</strong></label><input class="form-control form-control-user" id="date_of_birth" placeholder="00/11/2222" name="date_of_birth"></div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="email-1"><strong>Phone Number</strong></label><input class="form-control form-control-user" type="text" id="phone_number" placeholder="+260771234568" name="phone"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="first_name-1"><strong>Address</strong></label><input class="form-control form-control-user" type="text" id="address" placeholder="23 rd broadwaysteet" name="address"></div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="last_name-1"><strong>NRC Number</strong></label><input class="form-control form-control-user" type="text" id="nrc" placeholder="123456/78/9" name="NRC"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="first_name-1"><strong>Gender</strong></label><select class="form-select form-control-user" id="gender" placeholder="Gender" name="gender">
                                                                <option value="male">Male</option>
                                                                <option value="male">Female</option>
                                                            </select></div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="mb-3"><label class="form-label" for="last_name-1"><strong>Occupation</strong></label><select class="form-select form-control-user" id="occupation" placeholder="Student" name="occupation">
                                                                <option value="student">Student</option>
                                                                <option value="business">Business</option>
                                                                <option value="worker">Worker</option>
                                                                <option value="entreprenuer">Entreprenuer</option>
                                                            </select></div>
                                                    </div>
                                                </div>
                                                <div class="text-center mb-3"><button class="btn btn-primary btn-sm form-control-user" id="submit" type="submit">Save Settings</button></div>
                                            </form>
                                        </div>
                                        <div class="modal fade" role="dialog" tabindex="-1" id="modal-3">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="text-align: center;">
                                                        <h4 class="modal-title" style="text-align: center;width: 100%;">Change Password</h4>
                                                    </div>
                                                    <div class="modal-body" style="height: 335.3px;width: 100%;">
                                                        <form>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="username-1"><strong>New Password</strong></label><input class="form-control" type="password" name="new_password" placeholder="***************"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="first_name-1"><strong>Comfirm New Password</strong></label><input class="form-control" type="password" name="com_password" placeholder="******************"></div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="mb-3"><label class="form-label" for="first_name-2"><strong>Password change Phrase</strong></label><input class="form-control" type="text" name="reset_phrase" placeholder="phrase in case i forget password"></div>
                                                                </div>
                                                            </div>
                                                            <div class="text-center mb-3"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button><a class="btn btn-success btn-sm ms-9" role="button" style="--bs-body-color: rgba(51,69,204,0.37);" href="profile.php" target="_top">Save Password</a></div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer"></div>
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