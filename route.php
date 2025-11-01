<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        // Join login_details with user_table using email
        $stmt = $pdo->prepare("
            SELECT login_details.*, user_table.role, user_table.email, user_table.user_id, user_table.first_name, user_table.last_name, user_table.status
            FROM login_details 
            INNER JOIN user_table ON login_details.user_id = user_table.user_id 
            WHERE user_table.email = :email
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Check if user exists
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user status is not active
            if ($user['status'] !== 'active') {
                $error = "User account is deactivated by admin, Contact Support for more info.";
            }
            // If user is active, proceed with password verification
            elseif (password_verify($password, $user['password'])) {
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                    exit();
                } 
                elseif ($user['role'] === 'super_admin') {
                    header("Location: super_admin/index.php");
                    exit();
                }
                else {
                    header("Location: client/index.php");
                    exit();
                }

            } else {
                $error = "Invalid password. Please try again.";
            }

        } else {
            $error = "No account found with that email.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>