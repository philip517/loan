<?php
require 'auth_admin.php';
require '../db_connect.php'; // PDO connection

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}



// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update credentials
    if (isset($_POST['update_credentials'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        $stmt = $pdo->prepare("UPDATE user_table SET username=?, email=?, first_name=?, last_name=? WHERE user_id=?");
        $stmt->execute([$username, $email, $first_name, $last_name, $user_id]);
        header("Location: profile.php");
        exit;
    }

    // Update personal details
    if (isset($_POST['update_personal'])) {
        $date_of_birth = $_POST['date_of_birth'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $NRC = $_POST['NRC'];
        $gender = $_POST['gender'];
        $occupation = $_POST['occupation'];

        $stmt = $pdo->prepare("UPDATE user_table SET date_of_birth=?, phone=?, address=?, NRC=?, gender=?, occupation=? WHERE user_id=?");
        $stmt->execute([$date_of_birth, $phone, $address, $NRC, $gender, $occupation, $user_id]);
        header("Location: profile.php");
        exit;
    }

 
    // Update password and/or reset phrase
if (isset($_POST['update_password'])) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['com_password']);
    $reset_phrase = trim($_POST['reset_phrase']);

    if ($new_password !== '' || $confirm_password !== '') {
        // Password fields have data, so update password
        if ($new_password === $confirm_password) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE login_details SET password=?, password_reset_phrase=? WHERE user_id=?");
            $stmt->execute([$hashedPassword, $reset_phrase, $user_id]);
            header("Location: profile.php");
            exit;
        } else {
            $password_error = "Passwords do not match!";
        }
    } else {
        // Only update reset phrase, password remains unchanged
        $stmt = $pdo->prepare("UPDATE login_details SET password_reset_phrase=? WHERE user_id=?");
        $stmt->execute([$reset_phrase, $user_id]);
        header("Location: profile.php");
        exit;
    }
}

}


// Fetch existing admin data
//$stmt = $pdo->prepare("SELECT * FROM user_table WHERE user_id = ?");
$stmt = $pdo->prepare("
    SELECT u.*, l.password_reset_phrase 
    FROM user_table u
    LEFT JOIN login_details l ON u.user_id = l.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Profile</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <?php require 'navbar.php'; ?>
        <div id="content">
            <div class="container-fluid" style="margin-top:100px;">
                <h3 class="text-dark mb-4">Profile</h3>
                <div class="row d-flex justify-content-center mb-3">
                    <div class="col-lg-8">

                        <!-- Credentials -->
                        <div class="card shadow mb-3">
                            <div class="card-header py-3"><p class="text-primary m-0 fw-bold">User Credentials</p></div>
                            <div class="card-body">
                                <form method="post" class="user">
                                    <input type="hidden" name="update_credentials" value="1">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="username"><strong>Username</strong></label>
                                            <input class="form-control form-control-user" type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username'] ?? '') ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" for="email"><strong>Email</strong></label>
                                            <input class="form-control form-control-user" type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col">
                                            <label class="form-label" for="first_name"><strong>First Name</strong></label>
                                            <input class="form-control form-control-user" type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($admin['first_name'] ?? '') ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" for="last_name"><strong>Last Name</strong></label>
                                            <input class="form-control form-control-user" type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($admin['last_name'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-3 form-control-user" type="submit">Save Settings</button>
                                </form>
                            </div>
                        </div>

                        <!-- Personal Details -->
                        <div class="card shadow mb-3">
                            <div class="card-header py-3"><p class="text-primary m-0 fw-bold">Personal Details</p></div>
                            <div class="card-body">
                                <form method="post" class="user">
                                    <input type="hidden" name="update_personal" value="1">
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label" for="date_of_birth"><strong>Date of Birth</strong></label>
                                            <input class="form-control form-control-user" type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($admin['date_of_birth'] ?? '') ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" for="phone"><strong>Phone</strong></label>
                                            <input class="form-control form-control-user" type="text" id="phone" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col">
                                            <label class="form-label" for="address"><strong>Address</strong></label>
                                            <input class="form-control form-control-user" type="text" id="address" name="address" value="<?= htmlspecialchars($admin['address'] ?? '') ?>">
                                        </div>
                                        <div class="col">
                                            <label class="form-label" for="NRC"><strong>NRC</strong></label>
                                            <input class="form-control form-control-user" type="text" id="NRC" name="NRC" value="<?= htmlspecialchars($admin['NRC'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col">
                                            <label class="form-label" for="gender"><strong>Gender</strong></label>
                                            <select class="form-select form-control-user" name="gender">
                                                <option value="male" <?= ($admin['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="female" <?= ($admin['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                            </select>
                                        </div>
                                        <div class="col">
                                            <label class="form-label" for="occupation"><strong>Occupation</strong></label>
                                            <select class="form-select form-control-user" name="occupation">
                                                <option value="student" <?= ($admin['occupation'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                                                <option value="business" <?= ($admin['occupation'] ?? '') === 'business' ? 'selected' : '' ?>>Business</option>
                                                <option value="worker" <?= ($admin['occupation'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                                                <option value="entreprenuer" <?= ($admin['occupation'] ?? '') === 'entreprenuer' ? 'selected' : '' ?>>Entrepreneur</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-3 form-control-user" type="submit">Save Personal Details</button>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="card shadow mb-3">
                            <div class="card-header py-3"><p class="text-primary m-0 fw-bold">Change Password</p></div>
                            <div class="card-body">
                                <?php if (!empty($password_error)) echo '<div class="alert alert-danger">'.$password_error.'</div>'; ?>
                                <form method="post" class="user">
                                    <input type="hidden" name="update_password" value="1">
                                    <div class="mb-3">
                                        <label class="form-label" for="new_password"><strong>New Password</strong></label>
                                        <input class="form-control form-control-user" type="password" id="new_password" name="new_password" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="com_password"><strong>Confirm New Password</strong></label>
                                        <input class="form-control form-control-user" type="password" id="com_password" name="com_password" >
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="reset_phrase"><strong>Reset Phrase</strong></label>
                                        <input class="form-control form-control-user" type="text" id="reset_phrase" name="reset_phrase" value="<?= htmlspecialchars($admin['password_reset_phrase'] ?? '') ?>">
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-2 form-control-user" type="submit">Save Password</button>
                                </form>
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
