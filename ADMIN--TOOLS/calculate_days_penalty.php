<?php
// Days Remaining and Penalty Fee Calculator
require '../db_connect.php';
require 'auth_admin.php';

if (isset($_POST['start_calculation'])) {
    // Fetch all loans with their end dates - INCLUDING payment_date
    $stmt = $pdo->query("
        SELECT loan_id, loan_number, amount, loan_end_date, 
               penalty_fee, days_remaining, status, progress, payment_date
        FROM loan 
        WHERE loan_end_date IS NOT NULL
        ORDER BY loan_id
    ");
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_loans = count($loans);
    $processed = 0;
    $updated_penalty = 0;
    $updated_days = 0;
    $marked_overdue = 0;
    $marked_current = 0;
    $marked_paid = 0;
    $skipped_paid = 0;
    
    // Start HTML output
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Calculating Days & Penalties</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: monospace; margin: 20px; background: #f5f5f5; }
            .log { margin: 5px 0; padding: 10px; border-left: 3px solid #ccc; background: white; }
            .penalty-updated { border-left-color: #dc3545; background: #ffe6e6; }
            .days-updated { border-left-color: #007bff; background: #e6f2ff; }
            .overdue-updated { border-left-color: #ff9800; background: #fff3e6; }
            .current-updated { border-left-color: #28a745; background: #e6ffe6; }
            .paid-updated { border-left-color: #6f42c1; background: #f0e6ff; }
            .skipped { border-left-color: #6c757d; background: #f8f9fa; }
            .progress { 
                margin: 20px 0; 
                background: #e9ecef; 
                border-radius: 5px; 
                overflow: hidden; 
                height: 25px;
            }
            .progress-bar { 
                height: 100%; 
                background: linear-gradient(90deg, #007bff, #28a745);
                transition: width 0.5s ease;
            }
            .summary { 
                background: white; 
                padding: 15px; 
                border-radius: 5px; 
                margin: 20px 0; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .summary-item {
                display: inline-block;
                margin: 0 15px;
                padding: 10px;
                border-radius: 5px;
                font-weight: bold;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            h2 { color: #333; }
            .btn {
                padding: 10px 20px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                margin-top: 20px;
            }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>📅 Days Remaining & Penalty Fee Calculation</h2>
            
            <div class="summary">
                <div class="summary-item" style="background: #f8f9fa;">Total Loans: ' . $total_loans . '</div>
                <div class="summary-item" style="background: #e6f2ff;">Processing: <span id="current">0</span></div>
                <div class="summary-item" style="background: #e6ffe6;">Updated: <span id="updated">0</span></div>
            </div>
            
            <div class="progress">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>
            
            <div id="logs">';
    
    // Process each loan
    foreach ($loans as $loan) {
        $processed++;
        $percentage = round(($processed / $total_loans) * 100);
        
        $loan_id = $loan['loan_id'];
        $loan_number = $loan['loan_number'];
        $end_date = $loan['loan_end_date'];
        $current_penalty = $loan['penalty_fee'];
        $current_days = $loan['days_remaining'];
        $status = $loan['status'];
        $progress = $loan['progress'];
        $payment_date = $loan['payment_date'];
        
        // CHECK 1: If loan has payment_date, skip penalty calculation and mark as paid
        if ($payment_date !== NULL && $payment_date !== '') {
            $skipped_paid++;
            
            // Update progress to 'paid' if not already
            if ($progress !== 'paid') {
                $update_stmt = $pdo->prepare("UPDATE loan SET progress = 'paid' WHERE loan_id = ?");
                $update_stmt->execute([$loan_id]);
                $marked_paid++;
                
                echo '<div class="log paid-updated">
                    [' . date('H:i:s') . '] ✅ Updated Loan #' . $loan_number . ' to Paid
                    <br><strong>Loan ID:</strong> ' . $loan_id;
                echo ' | <strong>Progress:</strong> ' . ($progress ?: 'NULL') . ' → paid';
                echo '<br><strong>Payment Date:</strong> ' . date('M j, Y', strtotime($payment_date));
                echo ' | <strong>End Date:</strong> ' . date('M j, Y', strtotime($end_date));
                echo '<br><span style="color: #6f42c1;">⏭️ Skipped penalty calculation (Already paid)</span>';
                echo '</div>';
            } else {
                echo '<div class="log skipped">
                    [' . date('H:i:s') . '] ✅ Skipped Loan #' . $loan_number . '
                    <br><strong>Status:</strong> Paid (Payment: ' . date('M j, Y', strtotime($payment_date)) . ')
                    <br><span style="color: #6f42c1;">⏭️ Already paid - no calculation needed</span>
                </div>';
            }
            
            // Update progress bar and continue to next loan
            echo '<script>
                document.getElementById("progressBar").style.width = "' . $percentage . '%";
                document.getElementById("current").textContent = "' . $processed . '";
            </script>';
            
            ob_flush();
            flush();
            usleep(50000);
            
            continue; // Skip to next loan
        }
        
        // Calculate days remaining (negative if overdue)
        $today = new DateTime();
        $end_date_obj = new DateTime($end_date);
        $interval = $today->diff($end_date_obj);
        $days_remaining = $interval->days;
        
        // If end date is in the past, days remaining is negative
        if ($today > $end_date_obj) {
            $days_remaining = -$days_remaining;
        }
        
        // Calculate penalty fee (K15 per day overdue)
        $penalty_fee = 0;
        $days_overdue = 0;
        
        if ($days_remaining < 0 && $status == 'approved') {
            $days_overdue = abs($days_remaining);
            $penalty_fee = $days_overdue * 15;
        }
        
        // Determine progress status (only for non-paid loans)
        $new_progress = $progress;
        
        if ($status == 'approved') {
            if ($days_remaining < 0) {
                $new_progress = 'overdue';
                $marked_overdue++;
            } else if ($days_remaining >= 0) {
                $new_progress = 'current';
                $marked_current++;
            }
        } else if ($status == 'pending') {
            $new_progress = NULL; // pending loans don't have progress
        }
        
        // Prepare updates
        $updates_needed = [];
        $update_data = [];
        
        // Check if penalty fee needs update
        if ($penalty_fee != $current_penalty) {
            $updates_needed[] = 'penalty_fee = ?';
            $update_data[] = $penalty_fee;
        }
        
        // Check if days remaining needs update
        if ($days_remaining != $current_days) {
            $updates_needed[] = 'days_remaining = ?';
            $update_data[] = $days_remaining;
        }
        
        // Check if progress needs update
        if ($new_progress != $progress) {
            $updates_needed[] = 'progress = ?';
            $update_data[] = $new_progress;
        }
        
        // Update database if needed
        if (!empty($updates_needed)) {
            $update_query = "UPDATE loan SET " . implode(', ', $updates_needed) . " WHERE loan_id = ?";
            $update_data[] = $loan_id;
            
            $update_stmt = $pdo->prepare($update_query);
            $result = $update_stmt->execute($update_data);
            $rows_affected = $update_stmt->rowCount();
            
            if ($result && $rows_affected > 0) {
                // Log the update
                $log_class = '';
                $log_icon = '';
                
                if ($penalty_fee != $current_penalty) {
                    $updated_penalty++;
                    $log_class = 'penalty-updated';
                    $log_icon = '💰';
                } else if ($days_remaining != $current_days) {
                    $updated_days++;
                    $log_class = 'days-updated';
                    $log_icon = '📅';
                } else if ($new_progress != $progress) {
                    $log_class = $new_progress . '-updated';
                    $log_icon = '🔄';
                }
                
                echo '<div class="log ' . $log_class . '">
                    [' . date('H:i:s') . '] ' . $log_icon . ' Updated Loan #' . $loan_number . '
                    <br><strong>Loan ID:</strong> ' . $loan_id;
                
                if ($days_remaining != $current_days) {
                    echo ' | <strong>Days:</strong> ' . $current_days . ' → ' . $days_remaining;
                }
                
                if ($penalty_fee != $current_penalty) {
                    echo ' | <strong>Penalty:</strong> K' . $current_penalty . ' → K' . $penalty_fee;
                    if ($days_overdue > 0) {
                        echo ' (' . $days_overdue . ' days × K15)';
                    }
                }
                
                if ($new_progress != $progress) {
                    echo ' | <strong>Progress:</strong> ' . ($progress ?: 'NULL') . ' → ' . $new_progress;
                }
                
                echo '<br><strong>End Date:</strong> ' . date('M j, Y', strtotime($end_date));
                echo ' | <strong>Today:</strong> ' . date('M j, Y');
                
                if ($days_remaining < 0) {
                    echo ' | <span style="color: #dc3545;">⚠️ ' . abs($days_remaining) . ' days overdue</span>';
                } else if ($days_remaining > 0) {
                    echo ' | <span style="color: #28a745;">✓ ' . $days_remaining . ' days remaining</span>';
                } else {
                    echo ' | <span style="color: #ff9800;">⏰ Due today!</span>';
                }
                
                echo '</div>';
            }
        } else {
            echo '<div class="log skipped">
                [' . date('H:i:s') . '] ⏭️ Skipped Loan #' . $loan_number . '
                <br><strong>Status:</strong> ' . $status;
            
            if ($progress) {
                echo ' | <strong>Progress:</strong> ' . $progress;
            }
            
            echo ' | <strong>Days:</strong> ' . $current_days;
            echo ' | <strong>Penalty:</strong> K' . $current_penalty;
            
            if ($days_remaining < 0) {
                echo ' | <span style="color: #dc3545;">⚠️ ' . abs($days_remaining) . ' days overdue</span>';
            } else if ($days_remaining > 0) {
                echo ' | <span style="color: #28a745;">✓ ' . $days_remaining . ' days remaining</span>';
            }
            
            echo '</div>';
        }
        
        // Update progress bar and counters
        echo '<script>
            document.getElementById("progressBar").style.width = "' . $percentage . '%";
            document.getElementById("current").textContent = "' . $processed . '";
            document.getElementById("updated").textContent = "' . ($updated_penalty + $updated_days) . '";
        </script>';
        
        // Flush output
        ob_flush();
        flush();
        
        // Small delay for visibility
        usleep(50000); // 0.05 second
    }
    
    // Summary statistics
    $total_updated = $updated_penalty + $updated_days;
    
    echo '</div>'; // Close logs div
    
    // Final summary
    echo '<div class="summary" style="background: #e3f2fd;">
            <h3>📊 Calculation Complete!</h3>
            <div class="summary-item" style="background: #f8f9fa;">Total Loans Processed: ' . $processed . '</div>
            <div class="summary-item" style="background: #ffe6e6;">Penalty Fees Updated: ' . $updated_penalty . '</div>
            <div class="summary-item" style="background: #e6f2ff;">Days Remaining Updated: ' . $updated_days . '</div>
            <div class="summary-item" style="background: #fff3e6;">Marked Overdue: ' . $marked_overdue . '</div>
            <div class="summary-item" style="background: #e6ffe6;">Marked Current: ' . $marked_current . '</div>
            <div class="summary-item" style="background: #f0e6ff;">Marked/Updated Paid: ' . $marked_paid . '</div>
            <div class="summary-item" style="background: #f8f9fa;">Skipped Paid: ' . $skipped_paid . '</div>';
    
    echo '<br><br>
            <div style="margin-top: 10px;">
                <strong>Total Updates Made:</strong> ' . $total_updated . ' (' . round(($total_updated / $processed) * 100, 1) . '% of loans)
                <br><strong>Paid Loans Skipped:</strong> ' . $skipped_paid . ' (' . round(($skipped_paid / $total_loans) * 100, 1) . '%)
            </div>
        </div>
        
        <h3>Quick Actions:</h3>
        <a href="loan.php" class="btn">← Back to Loans Management</a>
        <a href="calculate_days_penalty.php" class="btn" style="background: #28a745;">🔄 Run Calculation Again</a>
        
        <br><br>
        <div style="font-size: 0.9em; color: #666;">
            <strong>Note:</strong> 
            <ul>
                <li>Penalty fee: K15 per day for overdue approved loans</li>
                <li>Days remaining: Positive = days until due, Negative = days overdue</li>
                <li>Loans with payment_date are skipped and marked as paid</li>
                <li>Progress automatically set based on days remaining and payment status</li>
            </ul>
        </div>
        </div>
    </body>
    </html>';
    
    exit;
}

// Show initial page with form
?>

<!DOCTYPE html>
<html>
<head>
    <title>Days & Penalty Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border: none;
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .formula-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .btn-calculate {
            background: linear-gradient(90deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        .btn-calculate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">📅 Days Remaining & Penalty Calculator</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">This tool will calculate and update days remaining and penalty fees for all loans based on their end dates.</p>
                        
                        <?php
                        // Show current stats
                        try {
                            $stats_stmt = $pdo->query("
                                SELECT 
                                    COUNT(*) as total_loans,
                                    SUM(CASE WHEN days_remaining < 0 THEN 1 ELSE 0 END) as overdue_loans,
                                    SUM(CASE WHEN days_remaining >= 0 AND days_remaining IS NOT NULL THEN 1 ELSE 0 END) as current_loans,
                                    SUM(penalty_fee) as total_penalties,
                                    COUNT(CASE WHEN progress = 'overdue' THEN 1 END) as marked_overdue,
                                    COUNT(CASE WHEN progress = 'paid' THEN 1 END) as paid_loans,
                                    COUNT(CASE WHEN payment_date IS NOT NULL THEN 1 END) as has_payment_date
                                FROM loan
                                WHERE loan_end_date IS NOT NULL
                            ");
                            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo '<div class="row">';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Total Loans</h6>
                                        <h3>' . $stats['total_loans'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Current Overdue</h6>
                                        <h3 style="color: ' . ($stats['overdue_loans'] > 0 ? '#dc3545' : '#28a745') . ';">' . $stats['overdue_loans'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Total Penalties</h6>
                                        <h3 style="color: #ff9800;">K' . number_format($stats['total_penalties'] ?? 0, 2) . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Marked Overdue</h6>
                                        <h3>' . ($stats['marked_overdue'] ?? 0) . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Paid Loans</h6>
                                        <h3 style="color: #6f42c1;">' . ($stats['paid_loans'] ?? 0) . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Have Payment Date</h6>
                                        <h3>' . ($stats['has_payment_date'] ?? 0) . '</h3>
                                    </div>
                                </div>';
                            echo '</div>';
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-warning">Could not fetch statistics: ' . $e->getMessage() . '</div>';
                        }
                        ?>
                        
                        <div class="formula-box">
                            <h6>📝 Calculation Rules:</h6>
                            <ul class="mb-0">
                                <li><strong>Days Remaining:</strong> Today's Date - Loan End Date</li>
                                <li><strong>Penalty Fee:</strong> K15 × Days Overdue (for approved loans only)</li>
                                <li><strong>Loans with payment_date:</strong> Skipped and marked as paid</li>
                                <li><strong>Progress Status:</strong> Automatically set based on days remaining</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>⚠️ Important Notes:</h6>
                            <ul class="mb-0">
                                <li>Only loans with end dates will be processed</li>
                                <li>Loans with payment_date will be skipped (treated as paid)</li>
                                <li>Penalties only apply to approved overdue loans</li>
                                <li>Progress status will be updated automatically</li>
                                <li>This process may take a while for many loans</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="start_calculation" class="btn btn-calculate btn-lg">
                                    <i class="fas fa-calculator"></i> Start Days & Penalty Calculation
                                </button>
                                <a href="loan.php" class="btn btn-outline-secondary">
                                    ← Back to Loans Management
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>