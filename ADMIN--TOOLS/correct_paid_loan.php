<?php
// Data Correction for Paid Loans
require '../db_connect.php';
require 'auth_admin.php';

if (isset($_POST['start_correction'])) {
    // Fetch all loans with payment dates
    $stmt = $pdo->query("
        SELECT loan_id, loan_number, amount, loan_end_date, 
               penalty_fee, days_remaining, status, progress, payment_date
        FROM loan 
        WHERE payment_date IS NOT NULL
        ORDER BY loan_id
    ");
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_loans = count($loans);
    $processed = 0;
    $status_corrected = 0;
    $progress_corrected = 0;
    $penalty_reset = 0;
    $days_reset = 0;
    
    // Start HTML output
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Correcting Paid Loan Data</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: monospace; margin: 20px; background: #f5f5f5; }
            .log { margin: 5px 0; padding: 10px; border-left: 3px solid #ccc; background: white; }
            .status-corrected { border-left-color: #28a745; background: #e6ffe6; }
            .progress-corrected { border-left-color: #6f42c1; background: #f0e6ff; }
            .penalty-reset { border-left-color: #ff9800; background: #fff3e6; }
            .days-reset { border-left-color: #007bff; background: #e6f2ff; }
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
            .rule-box {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
                border-left: 4px solid #dc3545;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>✅ Data Correction for Paid Loans</h2>
            <p>Correcting loans with payment dates to ensure consistent data.</p>
            
            <div class="rule-box">
                <strong>Correction Rules:</strong>
                <ul>
                    <li>✅ All loans with payment_date should have: status = "approved"</li>
                    <li>✅ All loans with payment_date should have: progress = "paid"</li>
                    <li>✅ Penalty fees should be reset to 0 (paid loans shouldn\'t have penalties)</li>
                    <li>✅ Days remaining should be reset to 0 (loan is complete)</li>
                </ul>
            </div>
            
            <div class="summary">
                <div class="summary-item" style="background: #f8f9fa;">Total Paid Loans: ' . $total_loans . '</div>
                <div class="summary-item" style="background: #e6f2ff;">Processing: <span id="current">0</span></div>
                <div class="summary-item" style="background: #e6ffe6;">Corrected: <span id="updated">0</span></div>
            </div>
            
            <div class="progress">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>
            
            <div id="logs">';
    
    // Process each loan with payment date
    foreach ($loans as $loan) {
        $processed++;
        $percentage = round(($processed / $total_loans) * 100);
        
        $loan_id = $loan['loan_id'];
        $loan_number = $loan['loan_number'];
        $current_status = $loan['status'];
        $current_progress = $loan['progress'];
        $current_penalty = $loan['penalty_fee'];
        $current_days = $loan['days_remaining'];
        $payment_date = $loan['payment_date'];
        $loan_amount = $loan['amount'];
        
        // Prepare updates based on correction rules
        $updates_needed = [];
        $update_data = [];
        $corrections_made = [];
        
        // Rule 1: Status must be 'approved' for paid loans
        if ($current_status !== 'approved') {
            $updates_needed[] = 'status = ?';
            $update_data[] = 'approved';
            $corrections_made[] = 'status: ' . $current_status . ' → approved';
        }
        
        // Rule 2: Progress must be 'paid'
        if ($current_progress !== 'paid') {
            $updates_needed[] = 'progress = ?';
            $update_data[] = 'paid';
            $corrections_made[] = 'progress: ' . ($current_progress ?: 'NULL') . ' → paid';
        }
        
        // Rule 3: Penalty fee should be 0 for paid loans
        if ($current_penalty != 0 && $current_penalty > 0) {
            $updates_needed[] = 'penalty_fee = ?';
            $update_data[] = 0;
            $corrections_made[] = 'penalty: K' . number_format($current_penalty, 2) . ' → K0.00';
        }
        
        // Rule 4: Days remaining should be 0 for paid loans
        if ($current_days != 0) {
            $updates_needed[] = 'days_remaining = ?';
            $update_data[] = 0;
            $corrections_made[] = 'days: ' . $current_days . ' → 0';
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
                if (strpos(implode('|', $corrections_made), 'status:') !== false) $status_corrected++;
                if (strpos(implode('|', $corrections_made), 'progress:') !== false) $progress_corrected++;
                if (strpos(implode('|', $corrections_made), 'penalty:') !== false) $penalty_reset++;
                if (strpos(implode('|', $corrections_made), 'days:') !== false) $days_reset++;
                
                // Determine log class based on correction type
                $log_class = '';
                if ($current_status !== 'approved') {
                    $log_class = 'status-corrected';
                } else if ($current_progress !== 'paid') {
                    $log_class = 'progress-corrected';
                } else if ($current_penalty != 0) {
                    $log_class = 'penalty-reset';
                } else {
                    $log_class = 'days-reset';
                }
                
                echo '<div class="log ' . $log_class . '">
                    [' . date('H:i:s') . '] ✅ Corrected Loan #' . $loan_number . '
                    <br><strong>Loan ID:</strong> ' . $loan_id;
                
                foreach ($corrections_made as $correction) {
                    echo ' | ' . $correction;
                }
                
                echo '<br><strong>Amount:</strong> K' . number_format($loan_amount, 2);
                echo ' | <strong>Payment Date:</strong> ' . date('M j, Y', strtotime($payment_date));
                
                if ($current_status !== 'approved') {
                    echo '<br><span style="color: #28a745;">📋 Status corrected to "approved" (required for paid loans)</span>';
                }
                
                if ($current_progress !== 'paid') {
                    echo '<br><span style="color: #6f42c1;">🏷️ Progress set to "paid" (loan is completed)</span>';
                }
                
                if ($current_penalty != 0) {
                    echo '<br><span style="color: #ff9800;">💰 Penalty reset to 0 (paid loans shouldn\'t have penalties)</span>';
                }
                
                if ($current_days != 0) {
                    echo '<br><span style="color: #007bff;">📅 Days remaining reset to 0 (loan is complete)</span>';
                }
                
                echo '</div>';
            }
        } else {
            echo '<div class="log already-correct">
                [' . date('H:i:s') . '] ✓ Loan #' . $loan_number . ' already correct
                <br><strong>Loan ID:</strong> ' . $loan_id;
            echo ' | <strong>Status:</strong> ' . $current_status;
            echo ' | <strong>Progress:</strong> ' . ($current_progress ?: 'NULL');
            echo ' | <strong>Penalty:</strong> K' . number_format($current_penalty, 2);
            echo ' | <strong>Days:</strong> ' . $current_days;
            echo '<br><strong>Payment Date:</strong> ' . date('M j, Y', strtotime($payment_date));
            echo '<br><span style="color: #6c757d;">✅ No corrections needed - data is already correct</span>';
            echo '</div>';
        }
        
        // Update progress bar and counters
        echo '<script>
            document.getElementById("progressBar").style.width = "' . $percentage . '%";
            document.getElementById("current").textContent = "' . $processed . '";
            document.getElementById("updated").textContent = "' . ($status_corrected + $progress_corrected + $penalty_reset + $days_reset) . '";
        </script>';
        
        // Flush output
        ob_flush();
        flush();
        
        // Small delay for visibility
        usleep(50000); // 0.05 second
    }
    
    // Summary statistics
    $total_corrections = $status_corrected + $progress_corrected + $penalty_reset + $days_reset;
    
    echo '</div>'; // Close logs div
    
    // Final summary
    echo '<div class="summary" style="background: #e3f2fd;">
            <h3>📊 Data Correction Complete!</h3>
            <div class="summary-item" style="background: #f8f9fa;">Total Loans Processed: ' . $processed . '</div>
            <div class="summary-item" style="background: #e6ffe6;">Status Corrected: ' . $status_corrected . '</div>
            <div class="summary-item" style="background: #f0e6ff;">Progress Corrected: ' . $progress_corrected . '</div>
            <div class="summary-item" style="background: #fff3e6;">Penalties Reset: ' . $penalty_reset . '</div>
            <div class="summary-item" style="background: #e6f2ff;">Days Reset: ' . $days_reset . '</div>';
    
    echo '<br><br>
            <div style="margin-top: 10px;">
                <strong>Total Corrections Made:</strong> ' . $total_corrections . ' (' . round(($total_corrections / $processed) * 100, 1) . '% of loans needed correction)
                <br><strong>Perfect Loans:</strong> ' . ($processed - ($status_corrected + $progress_corrected + $penalty_reset + $days_reset)) . ' (' . round((($processed - $total_corrections) / $processed) * 100, 1) . '% were already correct)
            </div>
        </div>
        
        <h3>Quick Actions:</h3>
        <a href="loan.php" class="btn">← Back to Loans Management</a>
        <a href="calculate_days_penalty.php" class="btn" style="background: #ff9800;">📅 Run Days & Penalty Calculator</a>
        <a href="correct_paid_loans.php" class="btn" style="background: #28a745;">🔄 Run Correction Again</a>
        
        <br><br>
        <div style="font-size: 0.9em; color: #666;">
            <strong>Summary of Corrections:</strong> 
            <ul>
                <li>All loans with payment dates should be marked as "approved" and "paid"</li>
                <li>Paid loans should have penalty fees reset to 0</li>
                <li>Paid loans should have days remaining set to 0</li>
                <li>This ensures consistent financial reporting</li>
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
    <title>Correct Paid Loan Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .preview-table {
            font-size: 0.85em;
            margin: 15px 0;
        }
        .btn-correct {
            background: linear-gradient(90deg, #28a745, #20c997);
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
        .badge-approved { background: #28a745; color: white; }
        .badge-pending { background: #ffc107; color: black; }
        .badge-rejected { background: #dc3545; color: white; }
        .badge-paid { background: #6f42c1; color: white; }
        .badge-current { background: #007bff; color: white; }
        .badge-overdue { background: #fd7e14; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0">✅ Correct Paid Loan Data</h4>
                        <p class="mb-0 mt-2">Ensure all loans with payment dates have consistent status and progress</p>
                    </div>
                    <div class="card-body">
                        <div class="rule-box">
                            <h6>📋 Correction Rules:</h6>
                            <ul class="mb-0">
                                <li><strong>Rule 1:</strong> All loans with payment_date → status = "approved"</li>
                                <li><strong>Rule 2:</strong> All loans with payment_date → progress = "paid"</li>
                                <li><strong>Rule 3:</strong> Paid loans → penalty_fee = 0 (no penalties for paid loans)</li>
                                <li><strong>Rule 4:</strong> Paid loans → days_remaining = 0 (loan is complete)</li>
                            </ul>
                        </div>
                        
                        <?php
                        // Show current statistics
                        try {
                            // General stats
                            $stats_stmt = $pdo->query("
                                SELECT 
                                    COUNT(*) as total_paid_loans,
                                    COUNT(CASE WHEN status != 'approved' THEN 1 END) as wrong_status,
                                    COUNT(CASE WHEN progress != 'paid' THEN 1 END) as wrong_progress,
                                    COUNT(CASE WHEN penalty_fee > 0 THEN 1 END) as has_penalty,
                                    COUNT(CASE WHEN days_remaining != 0 THEN 1 END) as wrong_days,
                                    COUNT(CASE WHEN status = 'approved' AND progress = 'paid' AND penalty_fee = 0 AND days_remaining = 0 THEN 1 END) as already_correct
                                FROM loan
                                WHERE payment_date IS NOT NULL
                            ");
                            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo '<div class="row">';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Total Paid Loans</h6>
                                        <h3>' . $stats['total_paid_loans'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Already Correct</h6>
                                        <h3 style="color: #28a745;">' . ($stats['already_correct'] ?? 0) . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Wrong Status</h6>
                                        <h3 style="color: ' . ($stats['wrong_status'] > 0 ? '#dc3545' : '#28a745') . ';">' . $stats['wrong_status'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Wrong Progress</h6>
                                        <h3 style="color: ' . ($stats['wrong_progress'] > 0 ? '#dc3545' : '#28a745') . ';">' . $stats['wrong_progress'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Has Penalties</h6>
                                        <h3 style="color: ' . ($stats['has_penalty'] > 0 ? '#fd7e14' : '#28a745') . ';">' . $stats['has_penalty'] . '</h3>
                                    </div>
                                </div>';
                            echo '<div class="col-md-6">
                                    <div class="stats-card">
                                        <h6>Wrong Days</h6>
                                        <h3 style="color: ' . ($stats['wrong_days'] > 0 ? '#007bff' : '#28a745') . ';">' . $stats['wrong_days'] . '</h3>
                                    </div>
                                </div>';
                            echo '</div>';
                            
                            // Show preview of incorrect loans
                            $preview_stmt = $pdo->query("
                                SELECT loan_id, loan_number, amount, status, progress, 
                                       penalty_fee, days_remaining, payment_date
                                FROM loan 
                                WHERE payment_date IS NOT NULL 
                                AND (status != 'approved' OR progress != 'paid' OR penalty_fee > 0 OR days_remaining != 0)
                                ORDER BY loan_id
                                LIMIT 10
                            ");
                            $incorrect_loans = $preview_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($incorrect_loans)) {
                                echo '<div class="mt-4">
                                    <h6>🔍 Preview of Incorrect Loans (First 10):</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm preview-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Loan #</th>
                                                    <th>Status</th>
                                                    <th>Progress</th>
                                                    <th>Penalty</th>
                                                    <th>Days</th>
                                                    <th>Payment Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                
                                foreach ($incorrect_loans as $loan) {
                                    $status_class = $loan['status'] == 'approved' ? 'approved' : 
                                                  ($loan['status'] == 'pending' ? 'pending' : 'rejected');
                                    $progress_class = $loan['progress'] == 'paid' ? 'paid' : 
                                                    ($loan['progress'] == 'current' ? 'current' : 
                                                    ($loan['progress'] == 'overdue' ? 'overdue' : 'none'));
                                    
                                    echo '<tr>';
                                    echo '<td><strong>#' . $loan['loan_number'] . '</strong></td>';
                                    echo '<td><span class="badge badge-' . $status_class . ' badge-status">' . $loan['status'] . '</span></td>';
                                    echo '<td><span class="badge badge-' . $progress_class . ' badge-status">' . ($loan['progress'] ?: 'NULL') . '</span></td>';
                                    echo '<td><span style="color: ' . ($loan['penalty_fee'] > 0 ? '#dc3545' : '#28a745') . ';">K' . number_format($loan['penalty_fee'], 2) . '</span></td>';
                                    echo '<td><span style="color: ' . ($loan['days_remaining'] != 0 ? '#007bff' : '#28a745') . ';">' . $loan['days_remaining'] . '</span></td>';
                                    echo '<td>' . date('M j, Y', strtotime($loan['payment_date'])) . '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table>';
                                echo '<small class="text-muted">Showing ' . count($incorrect_loans) . ' of ' . ($stats['total_paid_loans'] - $stats['already_correct']) . ' incorrect loans</small>';
                                echo '</div></div>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-warning">Could not fetch statistics: ' . $e->getMessage() . '</div>';
                        }
                        ?>
                        
                        <div class="alert alert-info mt-3">
                            <h6>ℹ️ About This Tool:</h6>
                            <ul class="mb-0">
                                <li>This tool fixes data inconsistencies for loans that have been paid</li>
                                <li>It ensures paid loans are correctly marked as "approved" and "paid"</li>
                                <li>Penalties are removed from paid loans (they shouldn't have penalties)</li>
                                <li>Days remaining is set to 0 for completed loans</li>
                                <li>Run this after processing payments to keep data clean</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>⚠️ Important Notes:</h6>
                            <ul class="mb-0">
                                <li>Only loans with payment dates will be processed</li>
                                <li>This will overwrite existing status/progress for paid loans</li>
                                <li>Changes cannot be undone - consider backing up first</li>
                                <li>Run the days & penalty calculator after this for non-paid loans</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="start_correction" class="btn btn-correct btn-lg">
                                    <i class="fas fa-check-circle"></i> Start Data Correction
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