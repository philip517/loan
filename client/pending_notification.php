<?php
require '../db_connect.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    exit;
}

// Fetch recent messages from admin to this user
$message_sql = "SELECT m.*, l.loan_id, l.amount, u.first_name, u.last_name 
               FROM message m 
               LEFT JOIN loan l ON m.loan_id = l.loan_id 
               LEFT JOIN user_table u ON u.user_id = l.user_id
               WHERE l.user_id = ? AND m.type = 'admin_to_user' 
               ORDER BY m.created_at DESC 
               LIMIT 5";
$message_stmt = $pdo->prepare($message_sql);
$message_stmt->execute([$user_id]);
$recent_messages = $message_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread messages
$unread_sql = "SELECT COUNT(*) as unread_count 
               FROM message m 
               LEFT JOIN loan l ON m.loan_id = l.loan_id 
               WHERE l.user_id = ? AND m.type = 'admin_to_user' AND m.status = 'sent'";
$unread_stmt = $pdo->prepare($unread_sql);
$unread_stmt->execute([$user_id]);
$unread_result = $unread_stmt->fetch(PDO::FETCH_ASSOC);
$unread_count = $unread_result['unread_count'] ?? 0;

// Return data as array
return [
    'recent_messages' => $recent_messages,
    'unread_count' => $unread_count
];
?>