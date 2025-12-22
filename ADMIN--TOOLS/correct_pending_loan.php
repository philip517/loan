<?php
// Data Correction for Pending Loans
require '../db_connect.php';
require 'auth_admin.php';

if (isset($_POST['start_correction'])) {
    // Fetch all pending loans
    $stmt = $pdo->query("
        SELECT loan_id, loan_number, amount, loan_start_date, loan_end_date,
               penalty_fee, days_remaining, status, progress, payment_date
        FROM loan 
        WHERE status = 'pending'
        ORDER BY loan_id
    ");
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_loans = count($loans);
    $processed = 0;
    $progress_corrected = 0;
    $payment_date_corrected = 0;
    $penalty_corrected = 0;
    $days_corrected = 0;
    $end_date_cleared = 0;
    
    // Start HTML output
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Correcting Pending Loan Data</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: monospace; margin: 20px; background: #f5f5f5; }
            .log { margin: 5px 0; padding: 10px; border-left: 3px solid #ccc; background: white; }
            .progress-corrected { border-left-color: #007bff; background: #e6f2ff; }
            .payment-corrected { border-left-color: #17a2b8; background: #e6f7ff; }
            .penalty-corrected { border-left-color: #ff9800; background: #fff3e6; }
            .days-corrected { border-left-color: #28a745; background: #e6ffe6; }
            .enddate-corrected { border-left-color: #6f42c1; background: #f0e6ff; }
            .already-correct { border-left-color: #6c757d; background: #f8f9fa; }
            .progress { 
                margin: 20px 0; 
                background: #e9ecef; 
                border-radius: 5px; 
                overflow: hidden; 
                height: 25px;
            }
            .progress-bar { 
                height: 100%; 
                background: linear-gradient(90deg, #007bff, #6f42c1);
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
            .rule-box {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>⏳ Data Correction for Pending Loans</h2>
            <p>Cleaning up data for pending loan applications.</p>
            
            <div class="rule-box">
                <strong>Correction Rules for Pending Loans:</strong>
                <ul>
                    <li>✅ Progress should be NULL (pending loans have no progress status)</li>
                    <li>✅ Payment date should be NULL (not paid yet)</li>
                    <li>✅ Penalty fee should be 0 (no penalties on pending loans)</li>
                    <li>✅ Days remaining should be 0 (loan hasn\'t started)</li>
                    <li>✅ Loan end date should be NULL (not approved yet)</li>
                </ul>
            </div>
            
            <div class="summary">
                <div class="summary-item" style="background: #f8f9fa;">Total Pending Loans: ' . $total_loans . '</div>
                <div class="summary-item" style="background: #e6f2ff;">Processing: <span id="current">0</span></div>
                <div class="summary-item" style="background: #e6ffe6;">Corrected: <span id="updated">0</span></div>
            </div>
            
            <div class="progress">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>
            
            <div id="logs">';
    
    // Process each pending loan
    foreach ($loans as $loan) {
        $processed++;
        $percentage = round(($processed / $total_loans) * 100);
        
        $loan_id = $loan['loan_id'];
        $loan_number = $loan['loan_number'];
        $current_progress = $loan['progress'];
        $current_payment_date = $loan['payment_date'];
        $current_penalty = $loan['penalty_fee'];
        $current_days = $loan['days_remaining'];
        $current_end_date = $loan['loan_end_date'];
        $loan_amount = $loan['amount'];
        $loan_start_date = $loan['loan_start_date'];
        
        // Prepare updates based on correction rules
        $updates_needed = [];
        $update_data = [];
        $corrections_made = [];
        
        // Rule 1: Progress should be NULL for pending loans
        if ($current_progress !== NULL) {
            $updates_needed[] = 'progress = NULL';
            $corrections_made[] = 'progress: ' . ($current_progress ?: 'NULL') . ' → NULL';
        }
        
        // Rule 2: Payment date should be NULL (not paid yet)
        if ($current_payment_date !== NULL) {
            $updates_needed[] = 'payment_date = NULL';
            $corrections_made[] = 'payment_date: ' . ($current_payment_date ? date('M j, Y', strtotime($current_payment_date)) : 'NULL') . ' → NULL';
        }
        
        // Rule 3: Penalty fee should be 0
        if ($current_penalty != 0 && $current_penalty > 0) {
            $updates_needed[] = 'penalty_fee = ?';
            $update_data[] = 0;
            $corrections_made[] = 'penalty: K' . number_format($current_penalty, 2) . ' → K0.00';
        }
        
        // Rule 4: Days remaining should be 0 (loan hasn\'t started)
        if ($current_days != 0) {
            $updates_needed[] = 'days_remaining = ?';
            $update_data[] = 0;
            $corrections_made[] = 'days: ' . $current_days . ' → 0';
        }
        
        // Rule 5: Loan end date should be NULL for pending loans
        if ($current_end_date !== NULL) {
            $updates_needed[] = 'loan_end_date = NULL';
            $corrections_made[] = 'end_date: ' . date('M j, Y', strtotime($current_end_date)) . ' → NULL';
        }
        
        // Update database if corrections are needed
        if (!empty($updates_needed)) {
            $update_query = "UPDATE loan SET " . implode(', ', $updates_needed) . " WHERE loan_id = ?";
            $update_data[] = $loan_id;
            
            $update_stmt = $pdo->prepare($update_query);
            $result = $update_stmt->execute($update_data);
            $rows_affected = $update_stmt->rowCount();
            
            if ($result && $rows_affected > 0) {
                // Count what was corrected
                if (in_array('progress: ' . ($current_progress ?: 'NULL') . ' → NULL', $corrections_made)) $progress_corrected++;
                if (in_array('payment_date: ' . ($current_payment_date ? date('M j, Y', strtotime($current_payment_date)) : 'NULL') . ' → NULL', $corrections_made)) $payment_date_corrected++;
                if (in_array('penalty: K' . number_format($current_penalty, 2) . ' → K0.00', $corrections_made)) $penalty_corrected++;
                if (in_array('days: ' . $current_days . ' → 0', $corrections_made)) $days_corrected++;
                if (in_array('end_date: ' . date('M j, Y', strtotime($current_end_date)) . ' → NULL', $corrections_made)) $end_date_cleared++;
                
                // Determine log class based on correction type
                $log_class = '';
                if ($current_progress !== NULL) {
                    $log_class = 'progress-corrected';
                } else if ($current_payment_date !== NULL) {
                    $log_class = 'payment-corrected';
                } else if ($current_penalty != 0) {
                    $log_class = 'penalty-corrected';
                } else if ($current_days != 0) {
                    $log_class = 'days-corrected';
                } else {
                    $log_class = 'enddate-corrected';
                }
                
                echo '<div class="log ' . $log_class . '">
                    [' . date('H:i:s') . '] ⏳ Corrected Pending Loan #' . $loan_number . '
                    <br><strong>Loan ID:</strong> ' . $loan_id;
                
                foreach ($corrections_made as $correction) {
                    echo ' | ' . $correction;
                }
                
                echo '<br><strong>Amount:</strong> K' . number_format($loan_amount, 2);
                echo ' | <strong>Application Date:</strong> ' . date('M j, Y', strtotime($loan_start_date));
                
                if ($current_progress !== NULL) {
                    echo '<br><span style="color: #007bff;">📊 Progress cleared (pending loans shouldn\'t have progress status)</span>';
                }
                
                if ($current_payment_date !== NULL) {
                    echo '<br><span style="color: #17a2b8;">💰 Payment date cleared (loan not paid yet)</span>';
                }
                
                if ($current_penalty != 0) {
                    echo '<br><span style="color: #ff9800;">⚖️ Penalty reset to 0 (no penalties on pending loans)</span>';
                }
                
                if ($current_days != 0) {
                    echo '<br><span style="color: #28a745;">📅 Days reset to 0 (loan hasn\'t started)</span>';
                }
                
                if ($current_end_date !== NULL) {
                    echo '<br><span style="color: #6f42c1;">📆 End date cleared (not approved yet)</span>';
                }
                
                echo '</div>';
            }
        } else {
            echo '<div class="log already-correct">
                [' . date('H:i:s') . '] ✓ Pending Loan #' . $loan_number . ' already correct
                <br><strong>Loan ID:</strong> ' . $loan_id;
            echo ' | <strong>Progress:</strong> ' . ($current_progress ?: 'NULL');
            echo ' | <strong>Payment Date:</strong> ' . ($current_payment_date ? date('M j, Y', strtotime($current_payment_date)) : 'NULL');
            echo ' | <strong>Penalty:</strong> K' . number_format($current_penalty, 2);
            echo ' | <strong>Days:</strong> ' . $current_days;
            echo ' | <strong>End Date:</strong> ' . ($current_end_date ? date('M j, Y', strtotime($current_end_date)) : 'NULL');
            echo '<br><span style="color: #6c757d;">✅ No corrections needed - pending loan data is clean</span>';
            echo '</div>';
        }
        
        // Update progress bar and counters
        echo '<script>
            document.getElementById("progressBar").style.width = "' . $percentage . '%";
            document.getElementById("current").textContent = "' . $processed . '";
            document.getElementById("updated").textContent = "' . ($progress_corrected + $payment_date_corrected + $penalty_corrected + $days_corrected + $end_date_cleared) . '";
        </script>';
        
        // Flush output
        ob_flush();
        flush();
        
        // Small delay for visibility
        usleep(50000); // 0.05 second
    }
    
    // Summary statistics
    $total_corrections = $progress_corrected + $payment_date_corrected + $penalty_corrected + $days_corrected + $end_date_cleared;
    
    echo '</div>'; // Close logs div
    
    // Final summary
    echo '<div class="summary" style="background: #e3f2fd;">
            <h3>📊 Pending Loan Correction Complete!</h3>
            <div class="summary-item" style="background: #f8f9fa;">Total Pending Loans: ' . $processed . '</div>
            <div class="summary-item" style="background: #e6f2ff;">Progress Cleared: ' . $progress_corrected . '</div>
            <div class="summary-item" style="background: #e6f7ff;">Payment Dates Cleared: ' . $payment_date_corrected . '</div>
            <div class="summary-item" style="background: #fff3e6;">Penalties Reset: ' . $penalty_corrected . '</div>
            <div class="summary-item" style="background: #e6ffe6;">Days Reset: ' . $days_corrected . '</div>
            <div class="summary-item" style="background: #f0e6ff;">End Dates Cleared: ' . $end_date_cleared . '</div>';
    
    echo '<br><br>
            <div style="margin-top: 10px;">
                <strong>Total Corrections Made:</strong> ' . $total_corrections . ' (' . round(($total_corrections / $processed) * 100, 1) . '% of pending loans needed cleaning)
                <br><strong>Clean Pending Loans:</strong> ' . ($processed - ($progress_corrected + $payment_date_corrected + $penalty_corrected + $days_corrected + $end_date_cleared)) . ' (' . round((($processed - $total_corrections) / $processed) * 100, 1) . '% were already clean)
            </div>
        </div>
        
        <h3>Quick Actions:</h3>
        <a href="loan.php" class="btn">← Back to Loans Management</a>
        <a href="correct_paid_loans.php" class="btn" style="background: #28a745;">✅ Correct Paid Loans</a>
        <a href="calculate_days_penalty.php" class="btn" style="background: #ff9800;">📅 Run Days & Penalty Calculator</a>
        <a href="correct_pending_loans.php" class="btn" style="background: #007bff;">🔄 Run Pending Correction Again</a>
        
        <br><br>
        <div style="font-size: 0.9em; color: #666;">
            <strong>Why This Matters:</strong> 
            <ul>
                <li>Pending loans should have NULL progress (they haven\'t started)</li>
                <li>Pending loans shouldn\'t have payment dates (not paid yet)</li>
                <li>Penalties and days remaining should be 0 (loan not active)</li>
                <li>End dates should be NULL (not approved/scheduled yet)</li>
                <li>Clean data prevents confusion in reports and calculations</li>
            </ul>
        </div>
        </div>
    </body>
    </html>';
    
    exit;
}

// Show initial page with form and preview
?>

<!DOCTYPE html>
<html>
<head>
    <title>Correct Pending Loan Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
        .rule-box {
            background: #f8f9fa;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .preview-table {
            font-size: 0.85em;
            margin: 15px 0;
        }
        .btn-correct {
            background: linear-gradient(90deg, #ffc107, #ff9800);
            border: none;
            padding: 12px 30px;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        .btn-correct:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .badge-status {
            font-size: 0.75em;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .badge-pending { background: #ffc107; color: black; }
        .badge-null { background: #6c757d; color: white; }
        .badge-progress {
            background: #17a2b8;
            color: white;
        }
        .badge-progress-paid { background: #28a745; color: white; }
        .badge-progress-current { background: #007bff; color: white; }
        .badge-progress-overdue { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark text-center">
                        <h4 class="mb-0">⏳ Correct Pending Loan Data</h4>
                        <p class="mb-0 mt-2">Clean up data for pending loan applications</p>
                    </div>
                    <div class="card-body">
                        <div class="rule-box">
                            <h6>📋 Correction Rules for Pending Loans:</h6>
                            <ul class="mb-0">
                                <li><strong>Rule 1:</strong> Progress = NULL (no progress status for pending)</li>
                                <li><strong>Rule 2:</strong> Payment Date = NULL (not paid yet)</li>
                                <li><strong>Rule 3:</strong> Penalty Fee = 0 (no penalties)</li>
                                <li><strong>Rule 4:</strong> Days Remaining = 0 (loan hasn\'t started)</li>
                                <li><strong>Rule 5:</strong> Loan End Date = NULL (not scheduled yet)</li>
                            </ul>
                        </div>
                        
                        <?php
                        // Show current statistics
                        try {
                            // General stats
                            $stats_stmt = $pdo->query("
                                SELECT 
                                    COUNT(*) as total_pending,
                                    COUNT(CASE WHEN progress IS NOT NULL THEN 1 END) as has_progress,
                                    COUNT(CASE WHEN payment_date IS NOT NULL THEN 1 END) as has_payment_date,
                                    COUNT(CASE WHEN penalty_fee > 0 THEN 1 END) as has_penalty,
                                    COUNT(CASE WHEN days_remaining != 0 THEN 1 END) as has_days,
                                    COUNT(CASE WHEN loan_end_date IS NOT NULL THEN 1 END) as has_end_date,
                                    COUNT(CASE WHEN progress IS NULL AND payment_date IS NULL AND penalty_fee = 0 AND days_remaining = 0 AND loan_end_date IS NULL THEN 1 END) as already_clean
                                FROM loan
                                WHERE status = 'pending'
                            ");
                            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo '<div class="row">';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Total Pending Loans</h6>
                                        <h3>' . $stats['total_pending'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Already Clean</h6>
                                        <h3 style="color: #28a745;">' . ($stats['already_clean'] ?? 0) . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Has Progress</h6>
                                        <h3 style="color: ' . ($stats['has_progress'] > 0 ? '#007bff' : '#28a745') . ';">' . $stats['has_progress'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Has Payment Date</h6>
                                        <h3 style="color: ' . ($stats['has_payment_date'] > 0 ? '#17a2b8' : '#28a745') . ';">' . $stats['has_payment_date'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Has Penalty</h6>
                                        <h3 style="color: ' . ($stats['has_penalty'] > 0 ? '#ff9800' : '#28a745') . ';">' . $stats['has_penalty'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Has Days Set</h6>
                                        <h3 style="color: ' . ($stats['has_days'] > 0 ? '#28a745' : '#28a745') . ';">' . $stats['has_days'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Has End Date</h6>
                                        <h3 style="color: ' . ($stats['has_end_date'] > 0 ? '#6f42c1' : '#28a745') . ';">' . $stats['has_end_date'] . '</h3>
                                    </div>
                                </div>';
                            echo '</div>';
                            
                            // Show preview of incorrect pending loans
                            $preview_stmt = $pdo->query("
                                SELECT loan_id, loan_number, amount, progress, 
                                       payment_date, penalty_fee, days_remaining, loan_end_date,
                                       loan_start_date
                                FROM loan 
                                WHERE status = 'pending' 
                                AND (progress IS NOT NULL OR payment_date IS NOT NULL OR 
                                     penalty_fee > 0 OR days_remaining != 0 OR loan_end_date IS NOT NULL)
                                ORDER BY loan_id
                                LIMIT 10
                            ");
                            $incorrect_loans = $preview_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($incorrect_loans)) {
                                echo '<div class="mt-4">
                                    <h6>🔍 Preview of Pending Loans Needing Cleaning (First 10):</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm preview-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Loan #</th>
                                                    <th>Progress</th>
                                                    <th>Payment Date</th>
                                                    <th>Penalty</th>
                                                    <th>Days</th>
                                                    <th>End Date</th>
                                                    <th>Applied</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                
                                foreach ($incorrect_loans as $loan) {
                                    $progress_class = $loan['progress'] == 'paid' ? 'progress-paid' : 
                                                    ($loan['progress'] == 'current' ? 'progress-current' : 
                                                    ($loan['progress'] == 'overdue' ? 'progress-overdue' : 
                                                    ($loan['progress'] === NULL ? 'null' : 'progress')));
                                    
                                    echo '<tr>';
                                    echo '<td><strong>#' . $loan['loan_number'] . '</strong></td>';
                                    echo '<td>';
                                    if ($loan['progress'] === NULL) {
                                        echo '<span class="badge badge-null badge-status">NULL</span>';
                                    } else {
                                        echo '<span class="badge badge-progress-' . ($loan['progress'] ?: '') . ' badge-status">' . $loan['progress'] . '</span>';
                                    }
                                    echo '</td>';
                                    echo '<td>' . ($loan['payment_date'] ? date('M j, Y', strtotime($loan['payment_date'])) : '<span class="badge badge-null badge-status">NULL</span>') . '</td>';
                                    echo '<td><span style="color: ' . ($loan['penalty_fee'] > 0 ? '#dc3545' : '#28a745') . ';">K' . number_format($loan['penalty_fee'], 2) . '</span></td>';
                                    echo '<td><span style="color: ' . ($loan['days_remaining'] != 0 ? '#007bff' : '#28a745') . ';">' . $loan['days_remaining'] . '</span></td>';
                                    echo '<td>' . ($loan['loan_end_date'] ? date('M j, Y', strtotime($loan['loan_end_date'])) : '<span class="badge badge-null badge-status">NULL</span>') . '</td>';
                                    echo '<td>' . date('M j, Y', strtotime($loan['loan_start_date'])) . '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table>';
                                echo '<small class="text-muted">Showing ' . count($incorrect_loans) . ' of ' . ($stats['total_pending'] - $stats['already_clean']) . ' pending loans needing cleaning</small>';
                                echo '</div></div>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-warning">Could not fetch statistics: ' . $e->getMessage() . '</div>';
                        }
                        ?>
                        
                        <div class="alert alert-info mt-3">
                            <h6>ℹ️ About This Tool:</h6>
                            <ul class="mb-0">
                                <li>This tool cleans up data for pending loan applications</li>
                                <li>Pending loans should not have progress status, payment dates, or penalties</li>
                                <li>Cleaning ensures accurate reporting and prevents calculation errors</li>
                                <li>Use this before running financial reports or calculations</li>
                                <li>Run after manual data entry or imports</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>⚠️ Important Notes:</h6>
                            <ul class="mb-0">
                                <li>Only loans with status = "pending" will be processed</li>
                                <li>This will clear progress, payment dates, penalties, days, and end dates</li>
                                <li>Changes cannot be undone - consider backing up first</li>
                                <li>Run paid loan correction first if needed</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="start_correction" class="btn btn-correct btn-lg">
                                    <i class="fas fa-broom"></i> Clean Pending Loan Data
                                </button>
                                <div class="btn-group" role="group">
                                    <a href="loan.php" class="btn btn-outline-secondary">
                                        ← Back to Loans
                                    </a>
                                    <a href="correct_paid_loans.php" class="btn btn-outline-success">
                                        ✅ Correct Paid Loans
                                    </a>
                                    <a href="calculate_days_penalty.php" class="btn btn-outline-warning">
                                        📅 Days & Penalties
                                    </a>
                                </div>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <strong>Recommended Order:</strong> 
                                1. Correct Paid Loans → 2. Clean Pending Loans → 3. Calculate Days & Penalties
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>