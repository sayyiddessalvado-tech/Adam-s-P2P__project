<?php
// Api/withdraw.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized session state.'
    ]);
    exit();
}

// CSRF protection: accept token in X-CSRF-Token header
$client_csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $client_csrf)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token.'
    ]);
    exit();
}

require_once __DIR__ . '/../Includes_dynamics/dataB.php';

$user_id = intval($_SESSION['user_id']);

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || !isset($input['amount'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Withdrawal amount was not provided.'
    ]);
    exit();
}

$raw_amount = $input['amount'];

if (!is_numeric($raw_amount)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid withdrawal amount.'
    ]);
    exit();
}

$amount = number_format((float)$raw_amount, 2, '.', '');

if ((float)$amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Withdrawal amount must be greater than zero.'
    ]);
    exit();
}

try {

    $conn->begin_transaction();

    // Lock wallet during transaction
    $wallet_stmt = $conn->prepare(
        "SELECT id, balance
         FROM wallets
         WHERE user_id = ?
         FOR UPDATE"
    );

    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();

    $wallet = $wallet_stmt->get_result()->fetch_assoc();

    if (!$wallet) {
        throw new Exception("Wallet not found.");
    }

    $current_balance = (float)$wallet['balance'];
    $withdrawal_amount = (float)$amount;

    $isLender = ($_SESSION['role'] ?? '') === 'lender';
    $contributions = [];
    $availableCapital = $current_balance;

    if ($isLender) {
        $contribution_stmt = $conn->prepare(
            "SELECT id, available_amount
             FROM lender_pool_contributions
             WHERE lender_id = ?
               AND status = 'active'
               AND available_amount > 0
             ORDER BY id ASC
             FOR UPDATE"
        );
        $contribution_stmt->bind_param("i", $user_id);
        $contribution_stmt->execute();
        $contribution_result = $contribution_stmt->get_result();
        $availableCapital = 0.00;

        while ($contribution = $contribution_result->fetch_assoc()) {
            $contribution['available_amount'] = (float)$contribution['available_amount'];
            $contributions[] = $contribution;
            $availableCapital += $contribution['available_amount'];
        }
    }

    // Prevent withdrawal above available balance
    if ($withdrawal_amount > ($isLender ? $availableCapital : $current_balance)) {
        throw new Exception(
            'Insufficient wallet balance. Available balance is ₦' .
            number_format($isLender ? $availableCapital : $current_balance, 2)
        );
    }

    // Deduct money from wallet
    $update_stmt = $conn->prepare(
        "UPDATE wallets
         SET balance = balance - ?
         WHERE user_id = ?"
    );

    $update_stmt->bind_param("si", $amount, $user_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows < 1) {
        throw new Exception("Wallet balance could not be updated.");
    }

    if ($isLender) {
        $remainingWithdrawal = $withdrawal_amount;

        foreach ($contributions as $contribution) {
            if ($remainingWithdrawal <= 0) {
                break;
            }

            $deduction = min($remainingWithdrawal, $contribution['available_amount']);
            $contribution_update = $conn->prepare(
                "UPDATE lender_pool_contributions
                 SET available_amount = available_amount - ?
                 WHERE id = ?"
            );
            $deductionValue = number_format($deduction, 2, '.', '');
            $contributionId = (int)$contribution['id'];
            $contribution_update->bind_param("di", $deductionValue, $contributionId);
            $contribution_update->execute();
            $remainingWithdrawal -= $deduction;
        }

        if ($remainingWithdrawal > 0.009) {
            throw new Exception("Lender contribution balance could not be updated.");
        }

        $pool_update = $conn->prepare(
            "UPDATE lending_pool
             SET available_balance = available_balance - ?
             WHERE pool_key = 1
               AND available_balance >= ?"
        );
        $pool_update->bind_param("ss", $amount, $amount);
        $pool_update->execute();

        if ($pool_update->affected_rows < 1) {
            throw new Exception("Lending pool balance could not be updated.");
        }
    }

    // Generate transaction reference
    $reference = 'WDR-' . date('YmdHis') . '-' .
        $user_id . '-' . strtoupper(bin2hex(random_bytes(4)));

    // Record withdrawal
    $transaction_stmt = $conn->prepare(
        "INSERT INTO transactions
        (user_id, type, amount, reference)
        VALUES (?, 'withdrawal', ?, ?)"
    );

    $transaction_stmt->bind_param(
        "iss",
        $user_id,
        $amount,
        $reference
    );

    $transaction_stmt->execute();

    // Get new balance
    $balance_stmt = $conn->prepare(
        "SELECT balance
         FROM wallets
         WHERE user_id = ?"
    );

    $balance_stmt->bind_param("i", $user_id);
    $balance_stmt->execute();

    $updated_wallet = $balance_stmt->get_result()->fetch_assoc();

    $new_balance = $updated_wallet['balance'];

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => '₦' . number_format($withdrawal_amount, 2) .
                     ' successfully withdrawn from your wallet.',
        'amount' => $amount,
        'new_balance' => $new_balance,
        'reference' => $reference
    ]);

} catch (Exception $e) {

    if ($conn->in_transaction) {
        $conn->rollback();
    }

    error_log("EduLend Withdrawal Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>