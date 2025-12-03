<?php
require '../db_connect.php';
require 'auth_admin.php';

echo "<h2>Database Verification</h2>";
echo "<pre>";

// Check if updates are visible
$query = "SELECT loan_id, loan_number, amount, duration, interest, 
          (amount * 0.10 * duration) as calculated_interest,
          (interest - (amount * 0.10 * duration)) as difference
          FROM loan 
          ORDER BY ABS(interest - (amount * 0.10 * duration)) DESC";

$stmt = $pdo->query($query);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total loans: " . count($loans) . "\n\n";
echo "Loans where calculated interest doesn't match stored interest:\n";
echo "-----------------------------------------------------------------\n";

foreach ($loans as $loan) {
    if (abs($loan['difference']) > 0.01) { // More than 1 cent difference
        echo "Loan ID: {$loan['loan_id']}\n";
        echo "Number: {$loan['loan_number']}\n";
        echo "Amount: K{$loan['amount']}\n";
        echo "Duration: {$loan['duration']} weeks\n";
        echo "Stored Interest: K{$loan['interest']}\n";
        echo "Calculated Interest: K{$loan['calculated_interest']}\n";
        echo "Difference: K{$loan['difference']}\n";
        echo "-----------------------------------------------------------------\n";
    }
}

echo "\n\nDirect SQL to update a single loan (test this in phpMyAdmin):\n";
if (!empty($loans)) {
    $sample = $loans[0];
    $calc = round($sample['amount'] * 0.10 * $sample['duration'], 2);
    echo "UPDATE loan SET interest = $calc WHERE loan_id = {$sample['loan_id']};";
}

echo "</pre>";
echo '<a href="javascript:history.back()">Go Back</a>';
?>