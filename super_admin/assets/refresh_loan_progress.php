<?php
require '../auth_admin.php';
require '../../db_connect.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Refresh Loan Progress</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <style>
        .log-entry {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .log-success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        .log-warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .log-error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .stats-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require '../navbar.php'; ?>
        <div class="d-flex flex-column" id="content-wrapper">
            <div id="content">
                <div class="container-fluid" style="margin-top: 100px;">
                    <div class="row">
                        <div class="col-12">
                            <h3 class="text-dark mb-4">Refresh Loan Progress Status</h3>
                            
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <p class="text-primary m-0 fw-bold">Progress Update Operations</p>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        This page will update the progress status of all approved loans based on their end dates.
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <div class="stats-box text-center">
                                                <h4>Current Loans</h4>
                                                <h2 id="current-count">0</h2>
                                                <small>End date not reached</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stats-box text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                <h4>Overdue Loans</h4>
                                                <h2 id="overdue-count">0</h2>
                                                <small>End date elapsed</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stats-box text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                                <h4>Total Processed</h4>
                                                <h2 id="total-count">0</h2>
                                                <small>Approved loans</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center mb-4">
                                        <button id="refreshBtn" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sync-alt me-2"></i>Refresh Loan Progress
                                        </button>
                                        <a href="loan.php" class="btn btn-secondary btn-lg ms-2">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Loans
                                        </a>
                                    </div>
                                    
                                    <div id="progressLog" class="mt-4" style="max-height: 400px; overflow-y: auto;">
                                        <div class="log-entry">
                                            <i class="fas fa-info-circle me-2 text-primary"></i>
                                            Ready to refresh loan progress. Click the button above to start.
                                        </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshBtn = document.getElementById('refreshBtn');
            const progressLog = document.getElementById('progressLog');
            const currentCount = document.getElementById('current-count');
            const overdueCount = document.getElementById('overdue-count');
            const totalCount = document.getElementById('total-count');
            
            function addLog(message, type = 'info') {
                const logEntry = document.createElement('div');
                logEntry.className = `log-entry log-${type}`;
                
                let icon = 'info-circle';
                let color = 'primary';
                
                switch(type) {
                    case 'success':
                        icon = 'check-circle';
                        color = 'success';
                        break;
                    case 'warning':
                        icon = 'exclamation-triangle';
                        color = 'warning';
                        break;
                    case 'error':
                        icon = 'times-circle';
                        color = 'danger';
                        break;
                }
                
                logEntry.innerHTML = `
                    <i class="fas fa-${icon} me-2 text-${color}"></i>
                    ${message}
                    <small class="text-muted float-end">${new Date().toLocaleTimeString()}</small>
                `;
                
                progressLog.appendChild(logEntry);
                progressLog.scrollTop = progressLog.scrollHeight;
            }
            
            function updateStats(stats) {
                currentCount.textContent = stats.current || 0;
                overdueCount.textContent = stats.overdue || 0;
                totalCount.textContent = stats.total || 0;
            }
            
            refreshBtn.addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                
                // Disable button and show loading
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                
                // Clear previous logs
                progressLog.innerHTML = '';
                
                addLog('Starting loan progress refresh...', 'info');
                
                // Make AJAX request to refresh progress
                fetch('refresh_loan_progress_ajax.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addLog('Loan progress refresh completed successfully!', 'success');
                            addLog(`Updated ${data.stats.current} loans to "current" status`, 'success');
                            addLog(`Updated ${data.stats.overdue} loans to "overdue" status`, 'warning');
                            addLog(`Total processed: ${data.stats.total} approved loans`, 'info');
                            
                            updateStats(data.stats);
                        } else {
                            addLog('Error: ' + (data.message || 'Unknown error occurred'), 'error');
                        }
                    })
                    .catch(error => {
                        addLog('Network error: ' + error.message, 'error');
                    })
                    .finally(() => {
                        // Re-enable button
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            });
            
            // Load initial stats
            fetch('refresh_loan_progress_ajax.php?action=stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
        });
    </script>
</body>
</html>