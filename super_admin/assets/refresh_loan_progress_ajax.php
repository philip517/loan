<?php
require '../auth_admin.php';
require '../../db_connect.php';

header('Content-Type: application/json');

// Function to update loan progress based on end dates
function updateLoanProgress($pdo) {
    $stats = [
        'current' => 0,
        'overdue' => 0,
        'total' => 0
    ];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get all approved loans that need progress updates
        $loans_stmt = $pdo->prepare("
            SELECT loan_id, loan_end_date, progress 
            FROM loan 
            WHERE status = 'approved' 
            AND loan_end_date IS NOT NULL
        ");
        $loans_stmt->execute();
        $loans = $loans_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['total'] = count($loans);
        
        foreach ($loans as $loan) {
            $loan_id = $loan['loan_id'];
            $end_date = $loan['loan_end_date'];
            $current_progress = $loan['progress'];
            
            // Determine the correct progress status
            $current_date = new DateTime();
            $loan_end_date = new DateTime($end_date);
            
            if ($current_date <= $loan_end_date) {
                // Loan is current (end date not reached)
                $new_progress = 'current';
                if ($current_progress !== $new_progress) {
                    $update_stmt = $pdo->prepare("UPDATE loan SET progress = ? WHERE loan_id = ?");
                    $update_stmt->execute([$new_progress, $loan_id]);
                    $stats['current']++;
                }
            } else {
                // Loan is overdue (end date elapsed)
                $new_progress = 'overdue';
                if ($current_progress !== $new_progress) {
                    $update_stmt = $pdo->prepare("UPDATE loan SET progress = ? WHERE loan_id = ?");
                    $update_stmt->execute([$new_progress, $loan_id]);
                    $stats['overdue']++;
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'stats' => $stats,
            'message' => 'Loan progress updated successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Function to get current progress statistics
function getProgressStats($pdo) {
    $stats = [
        'current' => 0,
        'overdue' => 0,
        'total' => 0
    ];
    
    try {
        // Count current loans (end date not reached)
        $current_stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM loan 
            WHERE status = 'approved' 
            AND progress = 'current'
            AND loan_end_date IS NOT NULL
            AND loan_end_date >= CURDATE()
        ");
        $current_stmt->execute();
        $stats['current'] = $current_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count overdue loans (end date elapsed)
        $overdue_stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM loan 
            WHERE status = 'approved' 
            AND progress = 'overdue'
            AND loan_end_date IS NOT NULL
            AND loan_end_date < CURDATE()
        ");
        $overdue_stmt->execute();
        $stats['overdue'] = $overdue_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count total approved loans with end dates
        $total_stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM loan 
            WHERE status = 'approved' 
            AND loan_end_date IS NOT NULL
        ");
        $total_stmt->execute();
        $stats['total'] = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error fetching stats: ' . $e->getMessage()
        ];
    }
}

// Handle different actions
$action = $_GET['action'] ?? 'refresh';

if ($action === 'stats') {
    // Return current statistics
    echo json_encode(getProgressStats($pdo));
} else {
    // Perform the progress update
    echo json_encode(updateLoanProgress($pdo));
}
?>