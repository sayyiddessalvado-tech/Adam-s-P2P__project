<?php
// EduLend - centralized loan review and pool funding endpoint.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

function loanReviewResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    loanReviewResponse(false, 'POST request required.');
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    loanReviewResponse(false, 'Unauthorized administrator action.');
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    loanReviewResponse(false, 'Invalid CSRF token.');
}

require_once __DIR__ . '/../Includes_dynamics/dataB.php';
require_once __DIR__ . '/../Includes_dynamics/credit_scorer.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$loanId = (int)($input['loan_id'] ?? 0);
$action = $input['action'] ?? '';
$reason = trim($input['reason'] ?? '');

if ($loanId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    loanReviewResponse(false, 'A valid loan and review action are required.');
}

try {
    $conn->begin_transaction();

    $loanStmt = $conn->prepare(
        "SELECT
            l.id,
            l.borrower_id,
            l.amount,
            l.processing_fee,
            l.repayment_amount,
            l.status,
            w.trust_tier,
            w.credit_score,
            w.successful_repayments,
            w.is_defaulted,
            w.crf_status
         FROM loans l
         INNER JOIN wallets w ON w.user_id = l.borrower_id
         INNER JOIN users u ON u.id = l.borrower_id AND u.role = 'student'
         WHERE l.id = ?
         FOR UPDATE"
    );
    $loanStmt->bind_param('i', $loanId);
    $loanStmt->execute();
    $loan = $loanStmt->get_result()->fetch_assoc();

    if (!$loan || $loan['status'] !== 'pending') {
        throw new Exception('Only pending student loan requests can be reviewed.');
    }

    if ($action === 'reject') {
        $rejectStmt = $conn->prepare(
            "UPDATE loans SET status = 'rejected' WHERE id = ? AND status = 'pending'"
        );
        $rejectStmt->bind_param('i', $loanId);
        $rejectStmt->execute();

        $title = 'Loan Request Rejected';
        $message = 'Your EduLend loan request was not approved.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }

        $notificationStmt = $conn->prepare(
            "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)"
        );
        $borrowerId = (int)$loan['borrower_id'];
        $notificationStmt->bind_param('iss', $borrowerId, $title, $message);
        $notificationStmt->execute();

        $conn->commit();
        loanReviewResponse(true, 'Loan request rejected.');
    }

    if ((int)$loan['is_defaulted'] === 1) {
        throw new Exception('Defaulted students cannot receive a new loan.');
    }

    if ((int)$loan['trust_tier'] < 2) {
        throw new Exception('Student verification tier does not permit borrowing.');
    }

    if ((int)$loan['trust_tier'] === 2) {
        $voucherStmt = $conn->prepare(
            "SELECT guarantor_id
             FROM vouchers
             INNER JOIN loans ON loans.voucher_id = vouchers.id
             WHERE loans.id = ?
               AND vouchers.requester_id = ?
               AND vouchers.status = 'approved'
             LIMIT 1"
        );
        $borrowerId = (int)$loan['borrower_id'];
        $voucherStmt->bind_param('ii', $loanId, $borrowerId);
        $voucherStmt->execute();
        $voucher = $voucherStmt->get_result()->fetch_assoc();

        if (!$voucher || !canUserVouch($conn, (int)$voucher['guarantor_id'])) {
            throw new Exception('The Tier 2 loan does not have a valid approved guarantor voucher.');
        }
    }

    if ((int)$loan['trust_tier'] === 3) {
        $cycles = (int)$loan['successful_repayments'];
        $amount = (float)$loan['amount'];

        if (($cycles === 0 && $amount > 2000)
            || ($cycles === 1 && $amount > 5000)
            || ($cycles < 3 && $amount > 10000)) {
            throw new Exception('The requested amount exceeds the student progressive loan limit.');
        }
    }

    $poolStmt = $conn->prepare(
        "SELECT id, available_balance
         FROM lending_pool
         WHERE pool_key = 1
         FOR UPDATE"
    );
    $poolStmt->execute();
    $pool = $poolStmt->get_result()->fetch_assoc();

    if (!$pool) {
        throw new Exception('Lending pool is not configured.');
    }

    $principal = (float)$loan['amount'];
    if ((float)$pool['available_balance'] < $principal) {
        throw new Exception('The lending pool does not have sufficient available capital.');
    }

    $contributionStmt = $conn->prepare(
        "SELECT id, lender_id, available_amount
         FROM lender_pool_contributions
         WHERE status = 'active' AND available_amount > 0
         ORDER BY id ASC
         FOR UPDATE"
    );
    $contributionStmt->execute();
    $contributions = $contributionStmt->get_result();

    $remainingPrincipal = $principal;
    $allocationRows = [];
    $lenderProfitTotal = round((float)$loan['processing_fee'] * 0.75, 2);
    $platformFeeTotal = round((float)$loan['processing_fee'] * 0.25, 2);

    while ($contribution = $contributions->fetch_assoc()) {
        if ($remainingPrincipal <= 0) {
            break;
        }

        $available = (float)$contribution['available_amount'];
        $allocated = min($available, $remainingPrincipal);
        $share = $principal > 0 ? $allocated / $principal : 0;

        $allocationRows[] = [
            'contribution_id' => (int)$contribution['id'],
            'lender_id' => (int)$contribution['lender_id'],
            'principal' => $allocated,
            'lender_profit' => round($lenderProfitTotal * $share, 2),
            'platform_fee' => round($platformFeeTotal * $share, 2)
        ];
        $remainingPrincipal -= $allocated;
    }

    if ($remainingPrincipal > 0.009) {
        throw new Exception('The lending pool could not allocate the requested capital.');
    }

    foreach ($allocationRows as $allocation) {
        $allocationUpdate = $conn->prepare(
            "UPDATE lender_pool_contributions
             SET available_amount = available_amount - ?,
                 deployed_amount = deployed_amount + ?
             WHERE id = ?"
        );
        $allocationUpdate->bind_param(
            'ddi',
            $allocation['principal'],
            $allocation['principal'],
            $allocation['contribution_id']
        );
        $allocationUpdate->execute();
    }

    $loanUpdate = $conn->prepare(
        "UPDATE loans
         SET status = 'active', lender_id = NULL
         WHERE id = ? AND status = 'pending'"
    );
    $loanUpdate->bind_param('i', $loanId);
    $loanUpdate->execute();

    $walletUpdate = $conn->prepare(
        "UPDATE wallets
         SET balance = balance + ?, debt = debt + ?
         WHERE user_id = ?"
    );
    $repaymentAmount = (float)$loan['repayment_amount'];
    $borrowerId = (int)$loan['borrower_id'];
    $walletUpdate->bind_param('ddi', $principal, $repaymentAmount, $borrowerId);
    $walletUpdate->execute();

    foreach ($allocationRows as $allocation) {
        $allocationInsert = $conn->prepare(
            "INSERT INTO pool_loan_allocations
                (loan_id, contribution_id, lender_id, principal_allocated, lender_profit, platform_fee)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $allocationInsert->bind_param(
            'iiiddd',
            $loanId,
            $allocation['contribution_id'],
            $allocation['lender_id'],
            $allocation['principal'],
            $allocation['lender_profit'],
            $allocation['platform_fee']
        );
        $allocationInsert->execute();
    }

    $poolUpdate = $conn->prepare(
        "UPDATE lending_pool
         SET available_balance = available_balance - ?,
             deployed_principal = deployed_principal + ?
         WHERE pool_key = 1"
    );
    $poolUpdate->bind_param('dd', $principal, $principal);
    $poolUpdate->execute();

    $reference = 'POOL-DISBURSE-' . $loanId . '-' . strtoupper(bin2hex(random_bytes(4)));
    $transactionStmt = $conn->prepare(
        "INSERT INTO transactions (user_id, loan_id, type, amount, reference)
         VALUES (?, ?, 'loan_disbursement', ?, ?)"
    );
    $transactionStmt->bind_param('iids', $borrowerId, $loanId, $principal, $reference);
    $transactionStmt->execute();

    $title = 'Loan Approved';
    $message = 'Your student loan has been approved and funded by the EduLend lending pool.';
    $notificationStmt = $conn->prepare(
        "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)"
    );
    $notificationStmt->bind_param('iss', $borrowerId, $title, $message);
    $notificationStmt->execute();

    $conn->commit();
    loanReviewResponse(true, 'Loan approved and funded from the EduLend lending pool.', [
        'loan_id' => $loanId,
        'amount_funded' => number_format($principal, 2, '.', ''),
        'allocation_count' => count($allocationRows)
    ]);
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('EduLend Loan Review Error: ' . $e->getMessage());
    loanReviewResponse(false, $e->getMessage());
}