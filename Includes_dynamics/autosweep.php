<?php
header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) session_start();

 // file directories
require_once __DIR__ . '/credit_scorer.php';
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/dataB.php';

function jsonResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

try {
    if (!isset($_SESSION["user_id"])) {
        jsonResponse(false, "Please log in first.");
    }

    $userId = (int) $_SESSION["user_id"];
    $action = $_GET["action"] ?? $_POST["action"] ?? "status";

    if (!in_array($action, ["status", "toggle", "execute"], true)) {
        jsonResponse(false, "Invalid Auto Sweep action.");
    }

    if (in_array($action, ["toggle", "execute"], true)) {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            jsonResponse(false, "POST request required.");
        }

        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        $csrfToken = $_POST["csrf_token"]
            ?? $_SERVER["HTTP_X_CSRF_TOKEN"]
            ?? "";

        if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
            jsonResponse(false, "Invalid security token.");
        }
    }

    if ($action === "status") {
        $stmt = $conn->prepare("
            SELECT balance, debt, auto_sweep_enabled
            FROM wallets
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $wallet = $stmt->get_result()->fetch_assoc();

        if (!$wallet) jsonResponse(false, "Wallet not found.");

        jsonResponse(true, "Auto Sweep status loaded.", [
            "auto_sweep_enabled" => (bool) $wallet["auto_sweep_enabled"],
            "balance" => (float) $wallet["balance"],
            "debt" => (float) $wallet["debt"]
        ]);
    }

    if ($action === "toggle") {
        $enabled = filter_var(
            $_POST["enabled"] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($enabled === null) {
            jsonResponse(false, "Invalid Auto Sweep setting.");
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare("
            UPDATE wallets
            SET auto_sweep_enabled = ?
            WHERE user_id = ?
        ");
        $enabledInt = $enabled ? 1 : 0;
        $stmt->bind_param("ii", $enabledInt, $userId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare("SELECT id FROM wallets WHERE user_id = ? LIMIT 1");
            $check->bind_param("i", $userId);
            $check->execute();

            if (!$check->get_result()->fetch_assoc()) {
                throw new Exception("Wallet not found.");
            }
        }

        $conn->commit();

        if (!$enabled) {
            jsonResponse(true, "Auto Sweep disabled.", [
                "auto_sweep_enabled" => false
            ]);
        }

        // Enabling Auto Sweep immediately checks the existing wallet balance.
        $action = "execute";
    }

    if ($action === "execute") {
        $conn->begin_transaction();

        $stmt = $conn->prepare("
            SELECT id, balance, debt, auto_sweep_enabled
            FROM wallets
            WHERE user_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $wallet = $stmt->get_result()->fetch_assoc();

        if (!$wallet) throw new Exception("Wallet not found.");
        if (!(int)$wallet["auto_sweep_enabled"]) {
            throw new Exception("Auto Sweep is disabled.");
        }

        $remainingBalance = (float)$wallet["balance"];
        $totalSwept = 0.0;
        $fullyPaidLoans = 0;
        $partialPayments = 0;

        $loanStmt = $conn->prepare("
            SELECT
                l.id,
                l.repayment_amount,
                COALESCE(r.total_paid, 0) AS total_paid
            FROM loans l
            LEFT JOIN (
                SELECT loan_id, SUM(amount_paid) AS total_paid
                FROM loan_repayments
                GROUP BY loan_id
            ) r ON r.loan_id = l.id
            WHERE l.borrower_id = ?
              AND l.status = 'active'
            ORDER BY l.due_date ASC, l.id ASC
            FOR UPDATE
        ");
        $loanStmt->bind_param("i", $userId);
        $loanStmt->execute();
        $loans = $loanStmt->get_result();

        while ($loan = $loans->fetch_assoc()) {
            if ($remainingBalance <= 0) break;

            $loanId = (int)$loan["id"];
            $outstanding = max(
                (float)$loan["repayment_amount"] - (float)$loan["total_paid"],
                0
            );

            if ($outstanding <= 0) continue;

            $payment = min($remainingBalance, $outstanding);
            $reference = "SWP-" . strtoupper(bin2hex(random_bytes(6)));

            $repayStmt = $conn->prepare("
                INSERT INTO loan_repayments
                    (loan_id, amount_paid, payment_method, payment_date)
                VALUES (?, ?, 'auto_sweep', NOW())
            ");
            $repayStmt->bind_param("id", $loanId, $payment);
            $repayStmt->execute();

            // Return the payment through the centralized pool allocation records.
            $allocationStmt = $conn->prepare(
                "SELECT id, contribution_id, lender_id, principal_allocated,
                        principal_returned, lender_profit, lender_profit_returned,
                        platform_fee, platform_fee_returned
                 FROM pool_loan_allocations
                 WHERE loan_id = ?
                 ORDER BY id ASC
                 FOR UPDATE"
            );
            $allocationStmt->bind_param("i", $loanId);
            $allocationStmt->execute();
            $allocationResult = $allocationStmt->get_result();
            $allocationRows = $allocationResult->fetch_all(MYSQLI_ASSOC);
            $paymentRemaining = $payment;
            $principalReturnedForPayment = 0.00;
            $lenderProfitForPayment = 0.00;
            $platformFeeForPayment = 0.00;

            foreach ($allocationRows as $allocation) {
                if ($paymentRemaining <= 0) {
                    break;
                }

                $principalOutstanding = max(
                    (float)$allocation['principal_allocated'] - (float)$allocation['principal_returned'],
                    0
                );
                $principalPaid = min($paymentRemaining, $principalOutstanding);

                if ($principalPaid > 0) {
                    $allocationId = (int)$allocation['id'];
                    $contributionId = (int)$allocation['contribution_id'];
                    $allocationUpdate = $conn->prepare(
                        "UPDATE pool_loan_allocations
                         SET principal_returned = principal_returned + ?,
                             status = 'partially_repaid'
                         WHERE id = ?"
                    );
                    $allocationUpdate->bind_param("di", $principalPaid, $allocationId);
                    $allocationUpdate->execute();

                    $contributionUpdate = $conn->prepare(
                        "UPDATE lender_pool_contributions
                         SET available_amount = available_amount + ?,
                             deployed_amount = GREATEST(deployed_amount - ?, 0),
                             returned_amount = returned_amount + ?
                         WHERE id = ?"
                    );
                    $contributionUpdate->bind_param(
                        "dddi",
                        $principalPaid,
                        $principalPaid,
                        $principalPaid,
                        $contributionId
                    );
                    $contributionUpdate->execute();

                    $paymentRemaining -= $principalPaid;
                    $principalReturnedForPayment += $principalPaid;
                }
            }

            if ($paymentRemaining > 0) {
                foreach ($allocationRows as $allocation) {
                    if ($paymentRemaining <= 0) {
                        break;
                    }

                    $profitOutstanding = max(
                        (float)$allocation['lender_profit'] - (float)$allocation['lender_profit_returned'],
                        0
                    );
                    $platformOutstanding = max(
                        (float)$allocation['platform_fee'] - (float)$allocation['platform_fee_returned'],
                        0
                    );
                    $feeOutstanding = $profitOutstanding + $platformOutstanding;
                    $feePaid = min($paymentRemaining, $feeOutstanding);
                    if ($feePaid <= 0) {
                        continue;
                    }

                    $allocationId = (int)$allocation['id'];
                    $contributionId = (int)$allocation['contribution_id'];
                    $lenderId = (int)$allocation['lender_id'];

                    $lenderProfitPaid = $feeOutstanding > 0
                        ? round($feePaid * ($profitOutstanding / $feeOutstanding), 2)
                        : 0.00;
                    $platformFeePaid = $feePaid - $lenderProfitPaid;

                    $allocationUpdate = $conn->prepare(
                        "UPDATE pool_loan_allocations
                         SET lender_profit_returned = lender_profit_returned + ?,
                             platform_fee_returned = platform_fee_returned + ?
                         WHERE id = ?"
                    );
                    $allocationUpdate->bind_param(
                        "ddi",
                        $lenderProfitPaid,
                        $platformFeePaid,
                        $allocationId
                    );
                    $allocationUpdate->execute();

                    $contributionUpdate = $conn->prepare(
                        "UPDATE lender_pool_contributions
                         SET available_amount = available_amount + ?,
                             profit_amount = profit_amount + ?
                         WHERE id = ?"
                    );
                    $contributionUpdate->bind_param(
                        "ddi",
                        $lenderProfitPaid,
                        $lenderProfitPaid,
                        $contributionId
                    );
                    $contributionUpdate->execute();

                    $lenderProfitForPayment += $lenderProfitPaid;
                    $platformFeeForPayment += $platformFeePaid;

                    if ($lenderProfitPaid > 0) {
                        $returnReference = "RET-" . strtoupper(bin2hex(random_bytes(6)));
                        $returnStmt = $conn->prepare(
                            "INSERT INTO transactions
                                (user_id, loan_id, type, amount, reference)
                             VALUES (?, ?, 'lender_return', ?, ?)"
                        );
                        $returnAmount = $lenderProfitPaid;
                        $returnStmt->bind_param(
                            "iids",
                            $lenderId,
                            $loanId,
                            $returnAmount,
                            $returnReference
                        );
                        $returnStmt->execute();
                    }

                    $paymentRemaining -= $feePaid;
                }
            }

            $poolReturnStmt = $conn->prepare(
                "UPDATE lending_pool
                 SET available_balance = available_balance + ? + ?,
                     returned_principal = returned_principal + ?,
                     lender_profit = lender_profit + ?,
                     platform_profit = platform_profit + ?
                 WHERE pool_key = 1"
            );
            $poolReturnStmt->bind_param(
                "ddddd",
                $principalReturnedForPayment,
                $lenderProfitForPayment,
                $principalReturnedForPayment,
                $lenderProfitForPayment,
                $platformFeeForPayment
            );
            $poolReturnStmt->execute();

            $allocationCompleteStmt = $conn->prepare(
                "UPDATE pool_loan_allocations
                 SET status = CASE
                         WHEN principal_returned >= principal_allocated
                          AND lender_profit_returned >= lender_profit
                          AND platform_fee_returned >= platform_fee
                         THEN 'completed'
                         ELSE 'partially_repaid'
                     END,
                     completed_at = CASE
                         WHEN principal_returned >= principal_allocated
                          AND lender_profit_returned >= lender_profit
                          AND platform_fee_returned >= platform_fee
                         THEN CURRENT_TIMESTAMP
                         ELSE completed_at
                     END
                 WHERE loan_id = ?"
            );
            $allocationCompleteStmt->bind_param("i", $loanId);
            $allocationCompleteStmt->execute();

            $transactionStmt = $conn->prepare("
                INSERT INTO transactions
                    (user_id, loan_id, type, amount, reference)
                VALUES (?, ?, 'debt_sweep', ?, ?)
            ");
            $transactionStmt->bind_param(
                "iids",
                $userId,
                $loanId,
                $payment,
                $reference
            );
            $transactionStmt->execute();

            $remainingBalance -= $payment;
            $totalSwept += $payment;

            if ($payment >= $outstanding) {
                $paidStmt = $conn->prepare("
                    UPDATE loans
                    SET status = 'paid', paid_date = NOW()
                    WHERE id = ? AND status = 'active'
                ");
                $paidStmt->bind_param("i", $loanId);
                $paidStmt->execute();
                $fullyPaidLoans++;
            } else {
                $partialPayments++;
            }
        }

        // Recalculate actual outstanding debt from active loans.
        $debtStmt = $conn->prepare("
            SELECT COALESCE(
                SUM(
                    GREATEST(
                        l.repayment_amount - COALESCE(r.total_paid, 0),
                        0
                    )
                ), 0
            ) AS total_debt
            FROM loans l
            LEFT JOIN (
                SELECT loan_id, SUM(amount_paid) AS total_paid
                FROM loan_repayments
                GROUP BY loan_id
            ) r ON r.loan_id = l.id
            WHERE l.borrower_id = ?
              AND l.status = 'active'
        ");
        $debtStmt->bind_param("i", $userId);
        $debtStmt->execute();
        $remainingDebt = (float)(
            $debtStmt->get_result()->fetch_assoc()["total_debt"] ?? 0
        );

        $walletUpdate = $conn->prepare("
            UPDATE wallets
            SET balance = ?, debt = ?
            WHERE id = ?
        ");
        $walletUpdate->bind_param(
            "ddi",
            $remainingBalance,
            $remainingDebt,
            $wallet["id"]
        );
        $walletUpdate->execute();

        $conn->commit();

        if ($totalSwept > 0) {
            calculateUserTrustScore($conn, $userId);
        }

        jsonResponse(true,
            $totalSwept > 0
                ? "Auto Sweep completed successfully."
                : "No active loan debt required a sweep.",
            [
                "amount_swept" => round($totalSwept, 2),
                "remaining_balance" => round($remainingBalance, 2),
                "remaining_debt" => round($remainingDebt, 2),
                "fully_paid_loans" => $fullyPaidLoans,
                "partial_payments" => $partialPayments,
                "auto_sweep_enabled" => true
            ]
        );
    }

} catch (Throwable $e) {
    error_log("Auto Sweep Error: " . $e->getMessage());
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    jsonResponse(false, "Auto Sweep could not be completed.");
}
?>