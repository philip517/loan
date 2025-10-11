
<?php
require 'db_connect.php'; // PDO connection

// Initialize form variables
$first_name = $last_name = $email = $password = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and collect form inputs
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Check if email already exists
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_table WHERE email = ?");
        $stmt_check->execute([$email]);
        $email_exists = $stmt_check->fetchColumn();

        if ($email_exists) {
            $error_message = "This email is already registered. Please use another email. ".'<br> Or <br><a class="small" href="index.php">SignIn instead?</a>';
        } else {
            // Start transaction
            $pdo->beginTransaction();

            // Insert into user_table
            $stmt = $pdo->prepare("INSERT INTO user_table (first_name, last_name, username, email, role) VALUES (:first_name, :last_name, :username, :email, :role)");
            $username = $first_name . $last_name; // simple username generation
            $role = 'user';
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':username' => $username,
                ':email' => $email,
                ':role' => $role
            ]);

            // Get last inserted user_id
            $user_id = $pdo->lastInsertId();

            // Insert into login_details
            $stmt2 = $pdo->prepare("INSERT INTO login_details (username, password, user_id) VALUES (:username, :password, :user_id)");
            $stmt2->execute([
                ':username' => $username,
                ':password' => $hashed_password,
                ':user_id' => $user_id
            ]);

            // Commit transaction
            $pdo->commit();

            // Clear form data after success
            $first_name = $last_name = $email = $password = "";

            // Redirect to the same page to clear POST and show alert
            echo "<script>alert('Account created successfully!');window.location.href='register.php';</script>";
            exit;
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error: " . $e->getMessage();
        // Form will retain user input because variables are not cleared
    }
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Register</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900&display=swap">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>
<body class="bg-gradient-primary" style="background: var(--bs-gray-100);">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-12 col-xl-12 flex-column justify-content-center" style="margin-top:100px;width: 517px;">
            <div class="card shadow-lg my-5 o-hidden border-0">
                <div class="card-body p-0">
                    <div class="row justify-content-center">
                        <div class="col-auto col-sm-auto col-md-5 col-lg-8 col-xl-12 text-center">
                            <div class="p-5"><img src="assets/img/white%20logo.png" width="79" height="81">
                                <div class="text-center">
                                    <h5 class="text-dark mb-4">Welcome</h5>

<form class="user" method="post">
    <?php if(!empty($error_message)) echo '<div class="alert alert-danger">'.$error_message.'</div>'; ?>
    <div class="mb-3 row">
        <div class="col-sm-6 mb-3 mb-sm-0">
            <input class="form-control form-control-user" type="text" name="first_name" placeholder="First Name" required value="<?= htmlspecialchars($first_name) ?>">
        </div>
        <div class="col-sm-6">
            <input class="form-control form-control-user" type="text" name="last_name" placeholder="Last Name" required value="<?= htmlspecialchars($last_name) ?>">
        </div>
    </div>
    <div class="mb-3 row">
        <div class="col-sm-6 mb-3 mb-sm-0">
            <input class="form-control form-control-user" type="password" name="password" placeholder="Password" required>
        </div>
        <div class="col-sm-6">
            <input class="form-control form-control-user" type="email" name="email" placeholder="example@gmail.com" required value="<?= htmlspecialchars($email) ?>">
        </div>
    </div>
    <button class="btn btn-primary form-control-user" type="submit">Create Account</button>
</form>

</div>
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

 -->
