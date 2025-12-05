<?php
// Loan Interest Calculator - DEBUG VERSION
require '../db_connect.php';
require 'auth_admin.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    $test_stmt = $pdo->query("SELECT 1");
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px;'>
          Database connection: <strong>✓ SUCCESS</strong></div>";
} catch (PDOException $e) {
    die("<div style='background: #f8d7da; padding: 10px; margin: 10px;'>
         Database connection: <strong>✗ FAILED</strong><br>
         Error: " . $e->getMessage() . "</div>");
}

if (isset($_POST['start_calculation'])) {
    // Fetch all loans
    $stmt = $pdo->query("SELECT loan_id, loan_number, amount, duration, interest FROM loan");
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_loans = count($loans);
    $processed = 0;
    $updated = 0;
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Calculating Loan Interest - DEBUG</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .log { margin: 5px 0; padding: 10px; border-left: 3px solid #ccc; }
            .updated { border-left-color: green; background: #f0fff0; }
            .skipped { border-left-color: orange; background: #fff8f0; }
            .error { border-left-color: red; background: #ffe6e6; }
            .progress { margin: 20px 0; }
            .debug { background: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h2>Loan Interest Calculation Progress - DEBUG MODE</h2>
        
        <div class="debug">
            <h4>Debug Information:</h4>
            <p><strong>Total loans found:</strong> ' . $total_loans . '</p>
            <p><strong>PHP Memory Limit:</strong> ' . ini_get('memory_limit') . '</p>
            <p><strong>Max Execution Time:</strong> ' . ini_get('max_execution_time') . ' seconds</p>
        </div>
        
        <div class="progress">
            <div style="background: #4CAF50; height: 20px; width: 0%;" id="progressBar"></div>
        </div>
        <p>Processing: <span id="current">0</span> / <span id="total">' . $total_loans . '</span></p>
        <div id="logs">';
    
    // Process each loan
    foreach ($loans as $loan) {
        $processed++;
        $percentage = round(($processed / $total_loans) * 100);
        
        echo '<div class="debug">Processing loan #' . $loan['loan_id'] . ' (' . $loan['loan_number'] . ')</div>';
        
        // Calculate interest
        $interest_calculated = round(($loan['amount'] * 0.10) * $loan['duration'], 2);
        
        echo '<div class="debug">
              Calculation: (K' . number_format($loan['amount'], 2) . ' × 10%) × ' . $loan['duration'] . ' weeks = K' . number_format($interest_calculated, 2) . '
              <br>Existing interest: K' . number_format($loan['interest'], 2) . '
              <br>Difference: ' . ($interest_calculated - $loan['interest']) . '
              </div>';
        
        if ($interest_calculated != $loan['interest']) {
            try {
                // Update database
                $update_stmt = $pdo->prepare("UPDATE loan SET interest = ? WHERE loan_id = ?");
                $result = $update_stmt->execute([$interest_calculated, $loan['loan_id']]);
                
                // Check if update was successful
                $rowCount = $update_stmt->rowCount();
                
                if ($result && $rowCount > 0) {
                    $updated++;
                    echo '<div class="log updated">
                        [' . date('H:i:s') . '] ✅ UPDATED Loan #' . $loan['loan_number'] . ' 
                        (ID: ' . $loan['loan_id'] . ')
                        <br>Old interest: K' . number_format($loan['interest'], 2) . '
                        <br>New interest: K' . number_format($interest_calculated, 2) . '
                        <br>Rows affected: ' . $rowCount . '
                    </div>';
                } else {
                    echo '<div class="log error">
                        [' . date('H:i:s') . '] ❌ UPDATE FAILED Loan #' . $loan['loan_number'] . '
                        <br>SQL executed: UPDATE loan SET interest = ' . $interest_calculated . ' WHERE loan_id = ' . $loan['loan_id'] . '
                        <br>Rows affected: ' . $rowCount . '
                    </div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="log error">
                    [' . date('H:i:s') . '] ❌ DATABASE ERROR Loan #' . $loan['loan_number'] . '
                    <br>Error: ' . $e->getMessage() . '
                </div>';
            }
        } else {
            echo '<div class="log skipped">
                [' . date('H:i:s') . '] ⚠️ SKIPPED Loan #' . $loan['loan_number'] . ': 
                Already has correct interest (K' . number_format($loan['interest'], 2) . ')
            </div>';
        }
        
        // Update progress
        echo '<script>
            document.getElementById("progressBar").style.width = "' . $percentage . '%";
            document.getElementById("current").textContent = "' . $processed . '";
        </script>';
        
        // Flush output
        ob_flush();
        flush();
        
        // Small delay for visibility
        usleep(50000); // 0.05 second
    }
    
    // Verify updates by counting loans with new interest
    $verify_stmt = $pdo->query("SELECT COUNT(*) as updated_count FROM loan WHERE interest = (amount * 0.10 * duration)");
    $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo '</div>
        <div class="debug" style="background: #e3f2fd;">
            <h4>Verification Results:</h4>
            <p><strong>Loans that match formula:</strong> ' . $verify_result['updated_count'] . ' / ' . $total_loans . '</p>
            <p><strong>Loans processed:</strong> ' . $processed . '</p>
            <p><strong>Loans updated:</strong> ' . $updated . '</p>
            <p><strong>Loans skipped:</strong> ' . ($processed - $updated) . '</p>
        </div>
        
        <h3>Quick Database Check:</h3>
        <form method="POST" action="check_loans.php" target="_blank">
            <button type="submit" class="btn btn-info">Check Loan Data Now</button>
        </form>
        
        <br>
        <a href="loan.php" class="btn btn-secondary">← Back to Loans Management</a>
    </body>
    </html>';
    
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Interest Calculator - DEBUG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Loan Interest Calculator - DEBUG MODE</h4>
        </div>
        <div class="card-body">
            <p>This tool will calculate and update interest for all loans in the database.</p>
            <p><strong>Calculation Formula:</strong> Interest = (Loan Amount × 10%) × Duration (weeks)</p>
            
            <div class="alert alert-info">
                <h5>Database Connection Test:</h5>
                <?php
                // Quick test query
                try {
                    $test = $pdo->query("SELECT COUNT(*) as count FROM loan");
                    $result = $test->fetch(PDO::FETCH_ASSOC);
                    echo "<p><strong>Total loans in database:</strong> " . $result['count'] . "</p>";
                    
                    // Check interest column
                    $interest_test = $pdo->query("SELECT loan_id, loan_number, amount, duration, interest FROM loan LIMIT 3");
                    $sample = $interest_test->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p><strong>Sample loans:</strong></p>";
                    echo "<pre>";
                    foreach ($sample as $loan) {
                        echo "ID: {$loan['loan_id']}, Number: {$loan['loan_number']}, 
                              Amount: K{$loan['amount']}, Duration: {$loan['duration']} weeks, 
                              Interest: K{$loan['interest']}\n";
                    }
                    echo "</pre>";
                } catch (PDOException $e) {
                    echo "<p class='text-danger'><strong>Error:</strong> " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
            
            <form method="POST">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will update interest for ALL loans. Make sure you have a backup.
                </div>
                
                <button type="submit" name="start_calculation" class="btn btn-primary btn-lg">
                    <i class="fas fa-calculator"></i> Start Calculation (Debug Mode)
                </button>
                <a href="loan.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>