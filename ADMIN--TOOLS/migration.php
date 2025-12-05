<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Migration Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .stat-box h3 {
            margin-top: 0;
            color: #495057;
        }
        .stat-count {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-top: 20px;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .checkmark {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .error-icon {
            color: #dc3545;
            font-weight: bold;
        }
        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            margin: 0 2px;
        }
        .role-admin {
            background-color: #007bff;
            color: white;
        }
        .role-superadmin {
            background-color: #6610f2;
            color: white;
        }
        .role-client {
            background-color: #28a745;
            color: white;
        }
        .user-preview {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        .user-row {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .user-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Migration Tool</h1>
        <p>This tool migrates users from the user_table to separate tables based on role.</p>
        
        <div class="message info">
            <strong>Note:</strong> This migration will:
            <ul>
                <li>Create three separate tables (admin, super_admin, clients) if they don't exist</li>
                <li>Copy data from the user_table to the appropriate tables based on role column</li>
                <li>Preserve all user data and relationships</li>
                <li>Map roles: admin → admin, super_admin → super_admin, client → clients</li>
            </ul>
        </div>

        <?php
        // Database configuration
        $host = '127.0.0.1';
        $dbname = 'sefa_satty';
        $username = 'root'; // Change this to your database username
        $password = ''; // Change this to your database password
        
        $log = "";
        $migrationStatus = "";
        $previewData = [];
        
        // Function to add message to log
        function addLog($message, $type = "info") {
            global $log;
            $timestamp = date("Y-m-d H:i:s");
            $icon = "";
            
            switch($type) {
                case "success":
                    $icon = "✓ ";
                    break;
                case "error":
                    $icon = "✗ ";
                    break;
                case "warning":
                    $icon = "⚠ ";
                    break;
                default:
                    $icon = "ℹ ";
            }
            
            $log .= "[$timestamp] $icon$message\n";
        }
        
        // Check if preview was requested
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
            try {
                // Create database connection
                $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if user_table exists
                $stmt = $conn->query("SHOW TABLES LIKE 'user_table'");
                if ($stmt->rowCount() > 0) {
                    // Get sample data for preview
                    $stmt = $conn->query("SELECT * FROM user_table LIMIT 10");
                    $previewData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Count users by role
                    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM user_table GROUP BY role");
                    $roleCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    addLog("Preview loaded successfully", "success");
                } else {
                    addLog("ERROR: user_table not found!", "error");
                }
                
            } catch (PDOException $e) {
                addLog("Database Error: " . $e->getMessage(), "error");
            }
        }
        
        // Check if migration was requested
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
            try {
                // Create database connection
                $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                addLog("Database connection established successfully", "success");
                
                // Step 1: Check if user_table exists
                addLog("Checking if user_table exists...");
                $stmt = $conn->query("SHOW TABLES LIKE 'user_table'");
                $userTableExists = $stmt->rowCount() > 0;
                
                if (!$userTableExists) {
                    addLog("ERROR: user_table not found!", "error");
                    $migrationStatus = "error";
                } else {
                    addLog("user_table found", "success");
                    
                    // Step 2: Get user_table structure
                    addLog("Getting user_table structure...");
                    $stmt = $conn->query("DESCRIBE user_table");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $columnDefinitions = [];
                    $primaryKey = "";
                    
                    foreach ($columns as $column) {
                        $columnName = $column['Field'];
                        $columnType = $column['Type'];
                        $isNull = $column['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
                        $default = $column['Default'] !== null ? "DEFAULT '" . $column['Default'] . "'" : "";
                        $extra = $column['Extra'];
                        
                        $columnDefinitions[] = "`$columnName` $columnType $isNull $default $extra";
                        
                        if ($column['Key'] == 'PRI') {
                            $primaryKey = $columnName;
                        }
                    }
                    
                    // Check if role column exists
                    $hasRoleColumn = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] == 'role') {
                            $hasRoleColumn = true;
                            break;
                        }
                    }
                    
                    if (!$hasRoleColumn) {
                        addLog("ERROR: role column not found in user_table!", "error");
                        $migrationStatus = "error";
                    } else {
                        addLog("role column found", "success");
                        
                        $tableDefinition = implode(",\n", $columnDefinitions);
                        
                        // Step 3: Create destination tables if they don't exist
                        $tables = ['admin', 'super_admin', 'clients'];
                        
                        foreach ($tables as $table) {
                            addLog("Checking/Creating $table table...");
                            
                            // Check if table exists
                            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() > 0) {
                                addLog("$table table already exists", "success");
                            } else {
                                // Create the table (remove role column from destination tables)
                                $createColumns = array_filter($columnDefinitions, function($col) {
                                    return !str_starts_with(trim($col), '`role`');
                                });
                                
                                $createTableSQL = "CREATE TABLE `$table` (\n" . 
                                    implode(",\n", $createColumns) . 
                                    ",\nPRIMARY KEY (`$primaryKey`)\n" . 
                                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                                
                                $conn->exec($createTableSQL);
                                addLog("$table table created successfully", "success");
                            }
                        }
                        
                        // Step 4: Check and add super_admin specific column
                        addLog("Checking for super_admin specific columns...");
                        $stmt = $conn->query("SHOW COLUMNS FROM super_admin LIKE 'permissions_level'");
                        if ($stmt->rowCount() == 0) {
                            $conn->exec("ALTER TABLE super_admin ADD COLUMN permissions_level INT DEFAULT 1");
                            addLog("Added permissions_level column to super_admin table", "success");
                        }
                        
                        // Step 5: Migrate data
                        addLog("Starting data migration...");
                        
                        // Clear existing data from destination tables
                        $conn->exec("TRUNCATE TABLE admin");
                        $conn->exec("TRUNCATE TABLE super_admin");
                        $conn->exec("TRUNCATE TABLE clients");
                        addLog("Cleared existing data from destination tables", "success");
                        
                        // Get all users from user_table
                        $stmt = $conn->query("SELECT * FROM user_table");
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $adminCount = 0;
                        $superAdminCount = 0;
                        $clientCount = 0;
                        $errorCount = 0;
                        $unknownRoleCount = 0;
                        
                        foreach ($users as $user) {
                            $role = isset($user['role']) ? strtolower(trim($user['role'])) : 'client';
                            
                            // Remove role from data to insert (since destination tables don't have role column)
                            unset($user['role']);
                            
                            // Prepare column names and values
                            $columns = array_keys($user);
                            $columnList = implode(", ", array_map(function($col) {
                                return "`$col`";
                            }, $columns));
                            
                            $placeholders = implode(", ", array_fill(0, count($columns), "?"));
                            $values = array_values($user);
                            
                            // Determine target table based on role
                            $targetTable = '';
                            switch ($role) {
                                case 'admin':
                                    $targetTable = 'admin';
                                    $adminCount++;
                                    break;
                                case 'super_admin':
                                case 'superadmin':
                                case 'super admin':
                                    $targetTable = 'super_admin';
                                    $superAdminCount++;
                                    break;
                                case 'client':
                                case 'customer':
                                case 'user':
                                    $targetTable = 'clients';
                                    $clientCount++;
                                    break;
                                default:
                                    $unknownRoleCount++;
                                    addLog("Unknown role '$role' for user {$user['username']} - skipping", "warning");
                                    continue 2; // Skip to next user
                            }
                            
                            // Insert into target table
                            $sql = "INSERT INTO $targetTable ($columnList) VALUES ($placeholders)";
                            $stmt = $conn->prepare($sql);
                            
                            try {
                                $stmt->execute($values);
                            } catch (Exception $e) {
                                $errorCount++;
                                addLog("Error migrating user {$user['username']}: " . $e->getMessage(), "error");
                            }
                        }
                        
                        // Step 6: Create unified view
                        addLog("Creating unified users view...");
                        $viewSQL = "CREATE OR REPLACE VIEW v_all_users AS
                            SELECT user_id, first_name, last_name, username, email, 'admin' as role, status, created_at FROM admin
                            UNION ALL
                            SELECT user_id, first_name, last_name, username, email, 'client' as role, status, created_at FROM clients
                            UNION ALL
                            SELECT user_id, first_name, last_name, username, email, 'super_admin' as role, status, created_at FROM super_admin";
                        
                        try {
                            $conn->exec($viewSQL);
                            addLog("Unified view created successfully", "success");
                        } catch (Exception $e) {
                            addLog("Warning: Could not create view: " . $e->getMessage(), "warning");
                        }
                        
                        // Step 7: Migration complete
                        $migrationStatus = "success";
                        addLog("Migration completed successfully!", "success");
                        addLog("Total users migrated: " . count($users), "success");
                        addLog("Admins: $adminCount, Super Admins: $superAdminCount, Clients: $clientCount", "success");
                        if ($unknownRoleCount > 0) {
                            addLog("Users with unknown roles (skipped): $unknownRoleCount", "warning");
                        }
                        if ($errorCount > 0) {
                            addLog("Errors during migration: $errorCount", "warning");
                        }
                    }
                }
                
            } catch (PDOException $e) {
                addLog("Database Error: " . $e->getMessage(), "error");
                $migrationStatus = "error";
            }
        }
        
        // Display migration status
        if ($migrationStatus == "success") {
            echo '<div class="message success">';
            echo '<strong>Migration Successful!</strong>';
            echo '</div>';
        } elseif ($migrationStatus == "error") {
            echo '<div class="message error">';
            echo '<strong>Migration Failed!</strong> Check the log below for details.';
            echo '</div>';
        }
        ?>
        
        <form method="POST" action="">
            <button type="submit" name="preview">
                Preview Migration
            </button>
            <button type="submit" name="migrate" onclick="return confirm('WARNING: This will overwrite existing data in admin, super_admin, and clients tables! Are you sure?')">
                Start Migration
            </button>
            <button type="button" onclick="location.reload()">
                Refresh Page
            </button>
        </form>
        
        <?php if (!empty($previewData)): ?>
        <div class="step">
            <h3>Preview Data (First 10 Users)</h3>
            <div class="user-preview">
                <?php foreach ($previewData as $user): ?>
                <div class="user-row">
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong> - 
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    <?php if (isset($user['role'])): ?>
                        <?php 
                        $roleClass = 'role-client';
                        if (strtolower($user['role']) == 'admin') $roleClass = 'role-admin';
                        if (strtolower($user['role']) == 'super_admin' || strtolower($user['role']) == 'superadmin') $roleClass = 'role-superadmin';
                        ?>
                        <span class="role-badge <?php echo $roleClass; ?>">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    <?php endif; ?>
                    <small>(Email: <?php echo htmlspecialchars($user['email']); ?>)</small>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($roleCounts)): ?>
            <h4>User Count by Role:</h4>
            <div class="stats">
                <?php 
                $allRoles = [
                    'admin' => ['count' => 0, 'title' => 'Admin Users'],
                    'super_admin' => ['count' => 0, 'title' => 'Super Admin Users'],
                    'client' => ['count' => 0, 'title' => 'Client Users']
                ];
                
                foreach ($roleCounts as $roleCount) {
                    $roleKey = strtolower($roleCount['role']);
                    if ($roleKey == 'superadmin' || $roleKey == 'super admin') {
                        $roleKey = 'super_admin';
                    } elseif ($roleKey == 'customer' || $roleKey == 'user') {
                        $roleKey = 'client';
                    }
                    
                    if (isset($allRoles[$roleKey])) {
                        $allRoles[$roleKey]['count'] = $roleCount['count'];
                    } else {
                        // Handle unknown roles
                        $allRoles['client']['count'] += $roleCount['count']; // Default to client
                    }
                }
                
                foreach ($allRoles as $role => $data): 
                ?>
                <div class="stat-box">
                    <h3><?php echo $data['title']; ?></h3>
                    <div class="stat-count"><?php echo $data['count']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($log)): ?>
        <div class="step">
            <h3>Migration Log</h3>
            <div class="log"><?php echo htmlspecialchars($log); ?></div>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Admin Users</h3>
                <?php
                if (isset($adminCount)) {
                    echo '<div class="stat-count">' . $adminCount . '</div>';
                } else {
                    try {
                        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM admin");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo '<div class="stat-count">' . $result['count'] . '</div>';
                    } catch (Exception $e) {
                        echo '<div class="stat-count">0</div>';
                    }
                }
                ?>
            </div>
            
            <div class="stat-box">
                <h3>Super Admin Users</h3>
                <?php
                if (isset($superAdminCount)) {
                    echo '<div class="stat-count">' . $superAdminCount . '</div>';
                } else {
                    try {
                        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM super_admin");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo '<div class="stat-count">' . ($result ? $result['count'] : 'N/A') . '</div>';
                    } catch (Exception $e) {
                        echo '<div class="stat-count">0</div>';
                    }
                }
                ?>
            </div>
            
            <div class="stat-box">
                <h3>Client Users</h3>
                <?php
                if (isset($clientCount)) {
                    echo '<div class="stat-count">' . $clientCount . '</div>';
                } else {
                    try {
                        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM clients");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo '<div class="stat-count">' . $result['count'] . '</div>';
                    } catch (Exception $e) {
                        echo '<div class="stat-count">0</div>';
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>Current Database Status</h3>
            <?php
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check tables
                $tables = ['user_table', 'admin', 'super_admin', 'clients'];
                
                foreach ($tables as $table) {
                    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $countStmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                        echo "<p><span class='checkmark'>✓</span> $table table exists ($count records)</p>";
                        
                        // Show column info for user_table
                        if ($table == 'user_table') {
                            $stmt = $conn->query("SHOW COLUMNS FROM user_table");
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            echo "<small>Columns: " . implode(', ', $columns) . "</small><br>";
                            
                            // Check for role column
                            if (in_array('role', $columns)) {
                                echo "<small><span class='checkmark'>✓</span> Role column found</small><br>";
                                
                                // Show unique roles
                                $stmt = $conn->query("SELECT DISTINCT role FROM user_table");
                                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                echo "<small>Available roles: " . implode(', ', $roles) . "</small>";
                            } else {
                                echo "<small><span class='error-icon'>✗</span> Role column NOT found!</small>";
                            }
                        }
                    } else {
                        echo "<p><span class='error-icon'>✗</span> $table table does not exist</p>";
                    }
                }
                
            } catch (PDOException $e) {
                echo "<p><span class='error-icon'>✗</span> Could not connect to database: " . $e->getMessage() . "</p>";
                echo "<p>Please update database credentials in the PHP code.</p>";
            }
            ?>
        </div>
        
        <div class="message info">
            <strong>Database Configuration:</strong>
            <ul>
                <li>Host: <?php echo htmlspecialchars($host); ?></li>
                <li>Database: <?php echo htmlspecialchars($dbname); ?></li>
                <li>Username: <?php echo htmlspecialchars($username); ?></li>
                <li>Password: <?php echo str_repeat('*', strlen($password)); ?></li>
            </ul>
            <p><small>Update these values in the PHP code if needed.</small></p>
        </div>
    </div>
    
    <script>
        // Auto-scroll log to bottom
        window.addEventListener('load', function() {
            var logElement = document.querySelector('.log');
            if (logElement) {
                logElement.scrollTop = logElement.scrollHeight;
            }
        });
    </script>
</body>
</html>