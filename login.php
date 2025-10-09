<?php
session_start();
require 'config.php'; // include your PDO connection
require 'route.php';
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Login</title>
    <meta name="description" content="Login Page">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900&display=swap">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>

<body class="bg-gradient-primary" style="background: var(--bs-light);margin-top: 50px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-12 col-xl-10 flex-column justify-content-center" style="width: 517px;">
                <div class="card shadow-lg my-5 o-hidden border-0">
                    <div class="card-body p-0">
                        <div class="row justify-content-center">
                            <div class="col-auto col-sm-auto col-md-5 col-lg-8 text-center">
                                <div class="p-5">
                                    <img src="assets/img/white%20logo.png" width="79" height="81">
                                    <div class="text-center">
                                        <h5 class="text-dark mb-4">Welcome Back!</h5>
                                    </div>

                                    <!-- Display error message -->
                                    <?php if (!empty($error)) : ?>
                                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                    <?php endif; ?>

                                    <!-- Login Form -->
                                    <form class="user" action="" method="POST">
                                        <div class="mb-3">
                                            <input class="form-control form-control-user" 
                                                type="email" 
                                                placeholder="Enter Email Address..." 
                                                name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <input class="form-control form-control-user" 
                                                type="password" 
                                                placeholder="Password" 
                                                name="password" required>
                                        </div>
                                        <button class="btn btn-primary d-block w-100 btn-user" type="submit">Login</button>
                                        <hr>
                                    </form>

                                    <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div>
                                    <div class="text-center"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>

</html>
