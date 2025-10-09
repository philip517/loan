<?php
// conn.php
// Database connection using PDO

$host = "localhost";       // or "127.0.0.1"
$dbname = "sefa_satty";    // your database name
$username = "root";        // default XAMPP username
$password = "";            // default XAMPP password (leave blank unless changed)

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Uncomment this line for testing (optional)
    // echo "✅ Database connection successful!";
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
