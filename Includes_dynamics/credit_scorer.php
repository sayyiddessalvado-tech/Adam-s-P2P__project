 <?php
// Includes_dynamics/credit_scorer.php

/**
 * Computes the dynamic mathematical trust score based on loan history,
 * tier status, and repayment speed.
 */
function calculateUserTrustScore($conn, $user_id) {
    // Rule: Base score for just signing up is 80%
    $base_score = 80.00; 
    
    // Check for active default block
    $w_stmt = $conn->prepare("SELECT is_defaulted, trust_tier FROM wallets WHERE user_id = ?");
    $w_stmt->bind_param("i", $user_id);
    $w_stmt->execute();
    $wallet = $w_stmt->get_result()->fetch_assoc();
    
     // Proposal rule: Drops to a 20% floor upon default
    if ($wallet && $wallet['is_defaulted'] == 1) {

    $score = 20.00;

    $update = $conn->prepare("
        UPDATE wallets
        SET credit_score = ?
        WHERE user_id = ?
    ");

    $update->bind_param("di", $score, $user_id);
    $update->execute();

    return $score;
}

    // Process repayment performance metrics
    $l_stmt = $conn->prepare("SELECT due_date, paid_date FROM loans WHERE borrower_id = ? AND status = 'paid' ORDER BY paid_date ASC");
    $l_stmt->bind_param("i", $user_id);
    $l_stmt->execute();
    $repayments = $l_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $consecutive_clean_repayments = 0;
    $score_modifier = 0.00;

    foreach ($repayments as $loan) {
        $due = strtotime($loan['due_date']);
        $paid = strtotime($loan['paid_date']);
        
        if ($paid <= $due) {
            $consecutive_clean_repayments++;
            // Reward early payment: more score increase if paid well ahead of deadline
            $time_differential = $due - $paid;
            if ($time_differential > 86400) { // Paid more than 24 hours early
                $score_modifier += 4.00; 
            } else {
                $score_modifier += 2.50;
            }
        } else {
            // Broken chain resets consecutive count
            $consecutive_clean_repayments = 0;
        }
    }

    $final_score = $base_score + $score_modifier;
    
    // Safety clamp at 100% maximum representation
     $final_score = ($final_score > 100.00) ? 100.00 : $final_score;

        $update = $conn->prepare("
        UPDATE wallets
        SET credit_score = ?
        WHERE user_id = ?
        ");

        $update->bind_param("di", $final_score, $user_id);
        $update->execute();

        return $final_score;
}

/**
 * Validates if a user satisfies senior peer voucher conditions (95% score + 4 clean cycles)
 */
function canUserVouch($conn, $user_id) {
    $current_score = calculateUserTrustScore($conn, $user_id);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as clean_count FROM loans WHERE borrower_id = ? AND status = 'paid' AND paid_date <= due_date");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    // Rule: Must have >= 95% trust score AND at least 4 successful historical payouts
$wallet_stmt = $conn->prepare("SELECT trust_tier FROM wallets WHERE user_id = ?");
$wallet_stmt->bind_param("i", $user_id);
$wallet_stmt->execute();
$wallet = $wallet_stmt->get_result()->fetch_assoc();

if (
    $current_score >= 95.00 &&
    $res['clean_count'] >= 4 &&
    $wallet['trust_tier'] == 3
) {
    return true;
}

 
return false;
}
?>