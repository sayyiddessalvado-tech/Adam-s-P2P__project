 <?php
// Api/deposit_funds.php

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
        'message' => 'Deposit amount was not provided.'
    ]);
    exit();
}

$raw_amount = $input['amount'];

if (!is_numeric($raw_amount)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid deposit amount.'
    ]);
    exit();
}

$amount = number_format((float)$raw_amount, 2, '.', '');

if ((float)$amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Deposit amount must be greater than zero.'
    ]);
    exit();
}

try {

    $conn->begin_transaction();

    // Lock the user's wallet while the transaction is being processed
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

    $isLender = ($_SESSION['role'] ?? '') === 'lender';

    if ($isLender) {
        $pool_stmt = $conn->prepare(
            "SELECT id
             FROM lending_pool
             WHERE pool_key = 1
             FOR UPDATE"
        );
        $pool_stmt->execute();

        if (!$pool_stmt->get_result()->fetch_assoc()) {
            throw new Exception("Lending pool is not configured.");
        }
    }

    // Add the deposited amount to the wallet
    $update_stmt = $conn->prepare(
        "UPDATE wallets 
         SET balance = balance + ? 
         WHERE user_id = ?"
    );

    $update_stmt->bind_param("si", $amount, $user_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows < 1) {
        throw new Exception("Wallet balance could not be updated.");
    }

    if ($isLender) {
        $reference = 'CON-' . date('YmdHis') . '-' . $user_id . '-' . strtoupper(bin2hex(random_bytes(4)));

        $contribution_stmt = $conn->prepare(
            "INSERT INTO lender_pool_contributions
                (lender_id, amount, available_amount, reference)
             VALUES (?, ?, ?, ?)"
        );
        $contribution_stmt->bind_param(
            "idds",
            $user_id,
            $amount,
            $amount,
            $reference
        );
        $contribution_stmt->execute();

        $pool_update = $conn->prepare(
            "UPDATE lending_pool
             SET available_balance = available_balance + ?
             WHERE pool_key = 1"
        );
        $pool_update->bind_param("s", $amount);
        $pool_update->execute();
    }

    // Generate a unique transaction reference
    $transaction_reference = 'DEP-' . date('YmdHis') . '-' . $user_id . '-' . strtoupper(bin2hex(random_bytes(4)));

    // Record the transaction using the ACTUAL database structure
    $transaction_stmt = $conn->prepare(
        "INSERT INTO transactions
        (user_id, type, amount, reference)
        VALUES (?, 'deposit', ?, ?)"
    );

    $transaction_stmt->bind_param(
        "iss",
        $user_id,
        $amount,
        $transaction_reference
    );

    $transaction_stmt->execute();

    // Get the updated balance
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
        'message' => '₦' . number_format((float)$amount, 2) . ' successfully deposited into your wallet.',
        'amount' => $amount,
        'new_balance' => $new_balance,
        'reference' => $transaction_reference
    ]);

} catch (Exception $e) {

    if ($conn->in_transaction) {
        $conn->rollback();
    }

    error_log("EduLend Deposit Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Deposit could not be completed. Please try again.'
    ]);
}
?>