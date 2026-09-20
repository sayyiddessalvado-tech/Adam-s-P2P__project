<?php
// cron/check_defaults.php
require_once __DIR__ . '/../Includes_dynamics/dataB.php';

echo "Initializing EduLend Autonomous Debt Enforcement Scan...\n";

// Start transaction scope
$conn->begin_transaction();

try {
    $current_date = date('Y-m-d H:i:s');
    
    // 1. Find all active loans that have passed their due_date and are still unpaid
    $scan_stmt = $conn->prepare("SELECT id, borrower_id FROM loans WHERE status = 'active' AND due_date < ?");
    $scan_stmt->bind_param("s", $current_date);
    $scan_stmt->execute();
    $overdue_loans = $scan_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $default_count = 0;
    foreach ($overdue_loans as $loan) {
        $loan_id = $loan['id'];
        $borrower_id = $loan['borrower_id'];
        
        // Flag the loan contract as officially defaulted
        $update_loan = $conn->prepare("UPDATE loans SET status = 'defaulted' WHERE id = ?");
        $update_loan->bind_param("i", $loan_id);
        $update_loan->execute();

                // Preserve pool exposure when a funded loan defaults.
                $update_allocations = $conn->prepare(
                        "UPDATE pool_loan_allocations
                         SET status = 'defaulted'
                         WHERE loan_id = ?
                             AND status IN ('allocated', 'partially_repaid')"
                );
                $update_allocations->bind_param("i", $loan_id);
                $update_allocations->execute();
        
        // Flag the user's wallet state as defaulted (This instantly triggers the 80% score drop)
        $update_wallet = $conn->prepare("UPDATE wallets SET is_defaulted = 1 WHERE user_id = ?");
        $update_wallet->bind_param("i", $borrower_id);
        $update_wallet->execute();
        
        $default_count++;
    }
    
    $conn->commit();
    echo "Scan complete. Processing successful. Total accounts flagged for default: $default_count\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "Critical Error during automated background execution loop: " . $e->getMessage() . "\n";
}
?>