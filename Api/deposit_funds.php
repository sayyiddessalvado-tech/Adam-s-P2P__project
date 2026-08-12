<?php
// Api/deposit_funds.php
session_start();
header('Content-Type: application/json');

// Guard Clause: Ensure the logged-in user has the authority to deposit investment capital
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session state.']);
    exit();
}

require_once '../Includes_dynamics/dataB.php';
$user_id = $_SESSION['user_id'];

// Capture JSON payload from frontend
$input = json_decode(file_get_contents('php://input'), true);
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$loan_id = isset($input['loan_id']) ? intval($input['loan_id']) : null;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Deposit amount must be greater than zero.']);
    exit();
}

// Start database transaction
$conn->begin_transaction();

try {
    if ($loan_id) {
        // ================================================
        // CONTEXT A: DIRECTLY FUNDING A STUDENT'S LOAN (P2P MATCH)
        // ================================================
        
        // 1. Check if the loan is still pending and lock the row for processing
        $loan_stmt = $conn->prepare("SELECT amount, borrower_id FROM loans WHERE id = ? AND status = 'pending' FOR UPDATE");
        $loan_stmt->bind_param("i", $loan_id);
        $loan_stmt->execute();
        $loan = $loan_stmt->get_result()->fetch_assoc();
        
        if (!$loan) {
            throw new Exception("This loan request is either fully funded or no longer active.");
        }
        
        if (floatval($loan['amount']) != $amount) {
            throw new Exception("Transactional Error: Match capital must exactly equal the requested loan amount.");
        }
        
        $borrower_id = $loan['borrower_id'];
        
        // 2. Update the loan status to active and assign the lender_id
        $update_loan = $conn->prepare("UPDATE loans SET lender_id = ?, status = 'active' WHERE id = ?");
        $update_loan->bind_param("ii", $user_id, $loan_id);
        $update_loan->execute();
        
        // 3. Move capital into the student's wallet balance sheet
        $update_wallet = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
        $update_wallet->bind_param("di", $amount, $borrower_id);
        $update_wallet->execute();
        
        // 4. Log the transaction ledger row for the disbursement
        $log_tx = $conn->prepare("INSERT INTO transactions (wallet_id, type, amount) VALUES (?, 'loan_disbursement', ?)");
        $log_tx->bind_param("id", $borrower_id, $amount);
        $log_tx->execute();
        
        $message = "Peer-to-Peer match successful! Loan request funded and capital disbursed to student.";
        
    } else {
        // ================================================
        // CONTEXT B: GENERAL LENDER WALLET DEPOSIT
        // ================================================
        
        // Update lender balance sheet
        $update_lender = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
        $update_lender->bind_param("di", $amount, $user_id);
        $update_lender->execute();
        
        // Log transaction history line
        $log_tx = $conn->prepare("INSERT INTO transactions (wallet_id, type, amount) VALUES (?, 'deposit', ?)");
        $log_tx->bind_param("id", $user_id, $amount);
        $log_tx->execute();
        
        $message = "₦" . number_format($amount, 2) . " successfully added to your investment pool balance.";
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>