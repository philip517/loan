<?php
require 'auth_admin.php';
require '../db_connect.php';

// Get admin ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../index.php");
    exit;
}

// Fetch comprehensive financial statistics (same as before)
$financial_stats_query = "
    SELECT 
        COUNT(*) as total_loans,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_loans,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_loans,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_loans,
        
        SUM(amount) as total_loan_amount_applied,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_principal_loaned,
        SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending_amount,
        
        SUM(CASE WHEN status = 'approved' THEN interest ELSE 0 END) as total_interest_earned,
        SUM(CASE WHEN status = 'approved' THEN penalty_fee ELSE 0 END) as total_penalties_earned,
        SUM(CASE WHEN status = 'approved' THEN interest + penalty_fee ELSE 0 END) as total_revenue_earned,
        
        SUM(CASE WHEN status = 'approved' THEN amount + interest + penalty_fee ELSE 0 END) as total_amount_due,
        
        SUM(CASE WHEN status = 'approved' AND progress = 'current' THEN amount ELSE 0 END) as current_loans_principal,
        SUM(CASE WHEN status = 'approved' AND progress = 'current' THEN interest ELSE 0 END) as current_loans_interest,
        SUM(CASE WHEN status = 'approved' AND progress = 'current' THEN amount + interest ELSE 0 END) as current_loans_total,
        
        SUM(CASE WHEN status = 'approved' AND progress = 'overdue' THEN amount ELSE 0 END) as overdue_loans_principal,
        SUM(CASE WHEN status = 'approved' AND progress = 'overdue' THEN interest ELSE 0 END) as overdue_loans_interest,
        SUM(CASE WHEN status = 'approved' AND progress = 'overdue' THEN penalty_fee ELSE 0 END) as overdue_loans_penalties,
        SUM(CASE WHEN status = 'approved' AND progress = 'overdue' THEN amount + interest + penalty_fee ELSE 0 END) as overdue_loans_total,
        
        SUM(CASE WHEN status = 'approved' AND progress = 'paid' THEN interest + penalty_fee ELSE 0 END) as paid_loans_revenue,
        
        SUM(CASE WHEN status = 'approved' AND YEAR(loan_start_date) = YEAR(CURDATE()) THEN amount ELSE 0 END) as ytd_principal,
        SUM(CASE WHEN status = 'approved' AND YEAR(loan_start_date) = YEAR(CURDATE()) THEN interest ELSE 0 END) as ytd_interest
        
    FROM loan
";

$financial_stats_stmt = $pdo->query($financial_stats_query);
$financial_stats = $financial_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate additional metrics (same as before)
$total_principal_loaned = $financial_stats['total_principal_loaned'] ?? 0;
$total_interest_earned = $financial_stats['total_interest_earned'] ?? 0;
$total_penalties_earned = $financial_stats['total_penalties_earned'] ?? 0;
$total_revenue_earned = $financial_stats['total_revenue_earned'] ?? 0;
$total_amount_due = $financial_stats['total_amount_due'] ?? 0;

$overdue_loans_total = $financial_stats['overdue_loans_total'] ?? 0;
$overdue_loans_principal = $financial_stats['overdue_loans_principal'] ?? 0;
$overdue_loans_interest = $financial_stats['overdue_loans_interest'] ?? 0;
$overdue_loans_penalties = $financial_stats['overdue_loans_penalties'] ?? 0;

$current_loans_total = $financial_stats['current_loans_total'] ?? 0;
$current_loans_principal = $financial_stats['current_loans_principal'] ?? 0;
$current_loans_interest = $financial_stats['current_loans_interest'] ?? 0;

$paid_loans_revenue = $financial_stats['paid_loans_revenue'] ?? 0;

// Calculate percentages
$interest_to_principal_ratio = ($total_principal_loaned > 0) ? ($total_interest_earned / $total_principal_loaned) * 100 : 0;
$penalties_to_revenue_ratio = ($total_revenue_earned > 0) ? ($total_penalties_earned / $total_revenue_earned) * 100 : 0;
$overdue_to_total_ratio = ($total_amount_due > 0) ? ($overdue_loans_total / $total_amount_due) * 100 : 0;

// Get monthly breakdown for chart - SIMPLIFIED
$monthly_finance_query = "
    SELECT 
        MONTH(loan_start_date) as month,
        SUM(amount + interest + penalty_fee) as total_amount
    FROM loan 
    WHERE status = 'approved'
    AND YEAR(loan_start_date) = YEAR(CURDATE())
    AND loan_start_date IS NOT NULL
    GROUP BY MONTH(loan_start_date)
    ORDER BY month
";

$monthly_finance_stmt = $pdo->query($monthly_finance_query);
$monthly_finance = $monthly_finance_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data - SIMPLIFIED
$chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$total_amount_data = array_fill(0, 12, 0);

foreach ($monthly_finance as $month_data) {
    $month_index = $month_data['month'] - 1;
    if ($month_index >= 0 && $month_index < 12) {
        $total_amount_data[$month_index] = $month_data['total_amount'] ?? 0;
    }
}

// Calculate max value for Y-axis in increments of 10,000
$max_chart_value = max($total_amount_data);
$y_axis_max = ceil($max_chart_value / 10000) * 10000;
if ($y_axis_max < 10000) $y_axis_max = 10000; // Minimum 10,000
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Financial Overview - Admin</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        .finance-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        .finance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        .metric-details {
            font-size: 0.8rem;
            color: #868e96;
            margin-top: 0.5rem;
        }
        .revenue-breakdown {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .principal-card {
            border-left: 4px solid #4e73df;
        }
        .interest-card {
            border-left: 4px solid #1cc88a;
        }
        .penalty-card {
            border-left: 4px solid #f6c23e;
        }
        .overdue-card {
            border-left: 4px solid #e74a3b;
        }
        .total-card {
            border-left: 4px solid #36b9cc;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .minimal-chart {
            height: 300px;
            position: relative;
        }
        .stat-row {
            margin-bottom: 1.5rem;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dee2e6;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #7b8a8b;
        }
        .summary-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        /* Minimal chart styling */
        .simple-bar {
            fill: #4e73df;
            transition: all 0.3s ease;
        }
        .simple-bar:hover {
            fill: #2e59d9;
        }
        .chart-grid line {
            stroke: #e3e6f0;
            stroke-width: 1;
        }
        .chart-axis text {
            fill: #858796;
            font-size: 11px;
            font-family: 'Nunito', sans-serif;
        }
        .chart-title {
            fill: #5a5c69;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Nunito', sans-serif;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php require 'navbar.php'; ?>
        <div id="content" style="background: #f8f9fa;">
            <div class="container-fluid" style="margin-top: 100px;">
                
                <!-- Header Section -->
                <div class="d-sm-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="text-dark mb-0"><strong>FINANCIAL OVERVIEW</strong></h3>
                        <p class="text-muted mb-0">Comprehensive financial metrics from loan portfolio</p>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-6 p-2">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?php echo date('F Y'); ?>
                        </span>
                    </div>
                </div>

                <!-- Main Financial Metrics Cards (same as before) -->
                <div class="row mb-4">
                    <!-- Total Principal Loaned -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow finance-card principal-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">TOTAL PRINCIPAL LOANED</div>
                                        <div class="metric-value text-primary">
                                            K<?php echo number_format($total_principal_loaned, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-hand-holding-usd me-1"></i>
                                            <?php echo $financial_stats['approved_loans'] ?? 0; ?> approved loans
                                        </div>
                                    </div>
                                    <div class="card-icon text-primary">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Interest Earned -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow finance-card interest-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">TOTAL INTEREST EARNED</div>
                                        <div class="metric-value text-success">
                                            K<?php echo number_format($total_interest_earned, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-percentage me-1"></i>
                                            <?php echo number_format($interest_to_principal_ratio, 1); ?>% of principal
                                        </div>
                                    </div>
                                    <div class="card-icon text-success">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Revenue (Interest + Penalties) -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow finance-card revenue-breakdown">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label" style="color: rgba(255,255,255,0.9);">TOTAL REVENUE</div>
                                        <div class="metric-value" style="color: white;">
                                            K<?php echo number_format($total_revenue_earned, 2); ?>
                                        </div>
                                        <div class="metric-details" style="color: rgba(255,255,255,0.8);">
                                            <i class="fas fa-coins me-1"></i>
                                            Interest: K<?php echo number_format($total_interest_earned, 2); ?>
                                        </div>
                                        <div class="metric-details" style="color: rgba(255,255,255,0.8);">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Penalties: K<?php echo number_format($total_penalties_earned, 2); ?>
                                        </div>
                                    </div>
                                    <div class="card-icon" style="color: rgba(255,255,255,0.8);">
                                        <i class="fas fa-cash-register"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount Due (Principal + Interest + Penalties) -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card shadow finance-card total-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">TOTAL AMOUNT DUE</div>
                                        <div class="metric-value text-info">
                                            K<?php echo number_format($total_amount_due, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-calculator me-1"></i>
                                            Principal + Interest + Penalties
                                        </div>
                                    </div>
                                    <div class="card-icon text-info">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row: Breakdown Cards -->
                <div class="row mb-4">
                    <!-- Overdue Loans Total -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow finance-card overdue-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">OVERDUE LOANS TOTAL</div>
                                        <div class="metric-value text-danger">
                                            K<?php echo number_format($overdue_loans_total, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                <?php echo number_format($overdue_to_total_ratio, 1); ?>% of total due
                                            </span>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Principal: K<?php echo number_format($overdue_loans_principal, 2); ?>
                                        </div>
                                    </div>
                                    <div class="card-icon text-danger">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Loans Total -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow finance-card" style="border-left: 4px solid #1cc88a;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">CURRENT LOANS TOTAL</div>
                                        <div class="metric-value text-success">
                                            K<?php echo number_format($current_loans_total, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-check-circle me-1 text-success"></i>
                                            On-time payments
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Principal: K<?php echo number_format($current_loans_principal, 2); ?>
                                        </div>
                                    </div>
                                    <div class="card-icon text-success">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Penalties Breakdown -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow finance-card penalty-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="metric-label">PENALTIES BREAKDOWN</div>
                                        <div class="metric-value text-warning">
                                            K<?php echo number_format($total_penalties_earned, 2); ?>
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-chart-pie me-1"></i>
                                            <?php echo number_format($penalties_to_revenue_ratio, 1); ?>% of total revenue
                                        </div>
                                        <div class="metric-details">
                                            <i class="fas fa-clock me-1 text-danger"></i>
                                            From overdue loans only
                                        </div>
                                    </div>
                                    <div class="card-icon text-warning">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimal Chart Section -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="chart-container">
                            <h5 class="mb-3">Monthly Loan Value (<?php echo date('Y'); ?>)</h5>
                            <div class="minimal-chart">
                                <svg id="minimalChart" width="100%" height="100%"></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics (simplified) -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="summary-box">
                            <h5 class="mb-3">Loan Status Summary</h5>
                            <div class="row stat-row">
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Pending Applications</div>
                                        <div class="stat-value text-warning">
                                            K<?php echo number_format($financial_stats['total_pending_amount'] ?? 0, 2); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $financial_stats['pending_loans'] ?? 0; ?> loans</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Rejected Applications</div>
                                        <div class="stat-value text-danger">
                                            K<?php echo number_format($financial_stats['total_rejected_amount'] ?? 0, 2); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $financial_stats['rejected_loans'] ?? 0; ?> loans</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row stat-row">
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Paid Revenue</div>
                                        <div class="stat-value text-success">
                                            K<?php echo number_format($paid_loans_revenue, 2); ?>
                                        </div>
                                        <small class="text-muted">Collected earnings</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Total Applied</div>
                                        <div class="stat-value text-primary">
                                            K<?php echo number_format($financial_stats['total_loan_amount_applied'] ?? 0, 2); ?>
                                        </div>
                                        <small class="text-muted">All applications</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="summary-box">
                            <h5 class="mb-3">Financial Ratios</h5>
                            <div class="row stat-row">
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Interest Ratio</div>
                                        <div class="stat-value">
                                            <?php echo number_format($interest_to_principal_ratio, 1); ?>%
                                        </div>
                                        <small class="text-muted">Interest to principal</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Penalty Ratio</div>
                                        <div class="stat-value">
                                            <?php echo number_format($penalties_to_revenue_ratio, 1); ?>%
                                        </div>
                                        <small class="text-muted">Penalties to revenue</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row stat-row">
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Overdue Ratio</div>
                                        <div class="stat-value">
                                            <?php echo number_format($overdue_to_total_ratio, 1); ?>%
                                        </div>
                                        <small class="text-muted">Overdue to total</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-item">
                                        <div class="stat-label">Approval Rate</div>
                                        <div class="stat-value">
                                            <?php 
                                            $approval_rate = 0;
                                            if ($financial_stats['total_loans'] > 0) {
                                                $approval_rate = ($financial_stats['approved_loans'] / $financial_stats['total_loans']) * 100;
                                            }
                                            echo number_format($approval_rate, 1); ?>%
                                        </div>
                                        <small class="text-muted">Loan approval rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="bg-white sticky-footer">
            <div class="container my-auto">
                <div class="text-center my-auto copyright">
                    <span>Copyright © SEFA SATTY 2025</span>
                </div>
            </div>
        </footer>
    </div>
    
    <a class="border rounded d-inline scroll-to-top" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/script.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Minimal Chart using SVG
            const chartData = <?php echo json_encode($total_amount_data); ?>;
            const labels = <?php echo json_encode($chart_labels); ?>;
            const yAxisMax = <?php echo $y_axis_max; ?>;
            const yAxisSteps = 5; // Show 5 grid lines (including 0)
            const stepValue = yAxisMax / (yAxisSteps - 1);
            
            const svg = document.getElementById('minimalChart');
            const width = svg.clientWidth;
            const height = svg.clientHeight;
            const padding = { top: 40, right: 40, bottom: 40, left: 60 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            // Clear any existing content
            svg.innerHTML = '';
            
            // Create main group
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.setAttribute('transform', `translate(${padding.left}, ${padding.top})`);
            
            // Add title
            const title = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            title.setAttribute('class', 'chart-title');
            title.setAttribute('x', chartWidth / 2);
            title.setAttribute('y', -15);
            title.setAttribute('text-anchor', 'middle');
            title.textContent = 'Total Loan Value by Month (K)';
            g.appendChild(title);
            
            // Add Y-axis grid lines and labels
            for (let i = 0; i < yAxisSteps; i++) {
                const yValue = i * stepValue;
                const yPosition = chartHeight - (yValue / yAxisMax) * chartHeight;
                
                // Grid line
                const gridLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                gridLine.setAttribute('class', 'chart-grid');
                gridLine.setAttribute('x1', 0);
                gridLine.setAttribute('y1', yPosition);
                gridLine.setAttribute('x2', chartWidth);
                gridLine.setAttribute('y2', yPosition);
                g.appendChild(gridLine);
                
                // Y-axis label
                const yLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                yLabel.setAttribute('class', 'chart-axis');
                yLabel.setAttribute('x', -10);
                yLabel.setAttribute('y', yPosition + 4);
                yLabel.setAttribute('text-anchor', 'end');
                yLabel.textContent = (yValue / 1000).toFixed(0) + 'k';
                g.appendChild(yLabel);
            }
            
            // Add X-axis grid line (baseline)
            const baseline = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            baseline.setAttribute('class', 'chart-grid');
            baseline.setAttribute('x1', 0);
            baseline.setAttribute('y1', chartHeight);
            baseline.setAttribute('x2', chartWidth);
            baseline.setAttribute('y2', chartHeight);
            g.appendChild(baseline);
            
            // Calculate bar dimensions
            const barCount = chartData.length;
            const barWidth = (chartWidth / barCount) * 0.6;
            const barSpacing = (chartWidth / barCount) * 0.4;
            
            // Add bars
            chartData.forEach((value, index) => {
                const x = index * (barWidth + barSpacing);
                const barHeight = (value / yAxisMax) * chartHeight;
                const y = chartHeight - barHeight;
                
                // Bar
                const bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                bar.setAttribute('class', 'simple-bar');
                bar.setAttribute('x', x);
                bar.setAttribute('y', y);
                bar.setAttribute('width', barWidth);
                bar.setAttribute('height', barHeight);
                bar.setAttribute('data-value', `K${value.toLocaleString()}`);
                
                // Add hover effect
                bar.addEventListener('mouseover', function() {
                    bar.style.fill = '#2e59d9';
                    
                    // Show tooltip
                    const tooltip = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    tooltip.setAttribute('class', 'chart-axis');
                    tooltip.setAttribute('x', x + barWidth / 2);
                    tooltip.setAttribute('y', y - 10);
                    tooltip.setAttribute('text-anchor', 'middle');
                    tooltip.setAttribute('fill', '#2c3e50');
                    tooltip.setAttribute('font-weight', '600');
                    tooltip.textContent = `K${Math.round(value).toLocaleString()}`;
                    tooltip.setAttribute('id', `tooltip-${index}`);
                    g.appendChild(tooltip);
                });
                
                bar.addEventListener('mouseout', function() {
                    bar.style.fill = '#4e73df';
                    
                    // Remove tooltip
                    const tooltip = document.getElementById(`tooltip-${index}`);
                    if (tooltip) {
                        g.removeChild(tooltip);
                    }
                });
                
                g.appendChild(bar);
                
                // X-axis label
                const xLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                xLabel.setAttribute('class', 'chart-axis');
                xLabel.setAttribute('x', x + barWidth / 2);
                xLabel.setAttribute('y', chartHeight + 20);
                xLabel.setAttribute('text-anchor', 'middle');
                xLabel.textContent = labels[index];
                g.appendChild(xLabel);
            });
            
            // Add to SVG
            svg.appendChild(g);
            
            // Add Y-axis label
            const yAxisLabel = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            yAxisLabel.setAttribute('class', 'chart-axis');
            yAxisLabel.setAttribute('transform', `translate(${padding.left - 40}, ${padding.top + chartHeight / 2}) rotate(-90)`);
            yAxisLabel.setAttribute('text-anchor', 'middle');
            yAxisLabel.textContent = 'Amount (K)';
            svg.appendChild(yAxisLabel);
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    location.reload(); // Simple refresh on resize for demo
                }, 250);
            });
        });
    </script>
</body>
</html>