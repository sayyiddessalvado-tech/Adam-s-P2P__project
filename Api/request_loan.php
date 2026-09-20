 <?php
// Api/request_loan.php
// EduLend - Student Loan Request API

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
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

require_once __DIR__ . '/../Includes_dynamics/dataB.php';
require_once __DIR__ . '/../Includes_dynamics/credit_scorer.php';

$user_id = intval($_SESSION['user_id']);

/*
 * The dashboard sends JSON.
 */
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || !isset($input['amount'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Loan amount was not provided.'
    ]);
    exit();
}

$raw_amount = $input['amount'];

if (!is_numeric($raw_amount)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid loan amount.'
    ]);
    exit();
}

$requested_principal = round((float)$raw_amount, 2);

if ($requested_principal <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Loan amount must be greater than zero.'
    ]);
    exit();
}

/*
 * Absolute system ceiling.
 */
if ($requested_principal > 20000) {
    echo json_encode([
        'success' => false,
        'message' => 'Transaction rejected. Loans cannot exceed ₦20,000.'
    ]);
    exit();
}

/*
 * Only students can request student loans.
 */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Only student accounts can request loans.'
    ]);
    exit();
}

/*
 * Voucher is optional at input level.
 * It becomes mandatory for Tier 2 requests and must be an approved
 * voucher record belonging to the current student.
 */
$voucher_id = null;

if (isset($input['voucher_id']) && $input['voucher_id'] !== '') {
    $voucher_id = intval($input['voucher_id']);

    if ($voucher_id <= 0) {
        $voucher_id = null;
    }
}

try {

    /*
     * Get the student's current wallet/verification status.
     */
    $wallet_stmt = $conn->prepare(
        "SELECT
            trust_tier,
            crf_status,
            successful_repayments,
            is_defaulted
         FROM wallets
         WHERE user_id = ?
         FOR UPDATE"
    );

    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();

    $wallet = $wallet_stmt->get_result()->fetch_assoc();

    if (!$wallet) {
        echo json_encode([
            'success' => false,
            'message' => 'Student wallet profile was not found.'
        ]);
        exit();
    }

    /*
     * Defaulted users cannot request another loan.
     */
    if ((int)$wallet['is_defaulted'] === 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Loan access restricted. Your account is currently marked as defaulted.'
        ]);
        exit();
    }

    $tier = (int)$wallet['trust_tier'];
    $cycles = (int)$wallet['successful_repayments'];

    /*
     * TIER 1
     *
     * Tier 1 users cannot borrow.
     */
    if ($tier < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Access Restricted: Tier 1 accounts cannot borrow. Complete Tier 2 verification first.'
        ]);
        exit();
    }

    /*
     * TIER 2
     *
     * Tier 2 requires a qualifying peer voucher
     * and is limited to ₦2,000.
     */
    if ($tier === 2) {

        if ($voucher_id === null) {
            echo json_encode([
                'success' => false,
                'message' => 'Vouch Required: Tier 2 accounts require a Senior Peer Guarantor to request a loan.'
            ]);
            exit();
        }

        $voucher_stmt = $conn->prepare(
            "SELECT guarantor_id
             FROM vouchers
             WHERE id = ?
               AND requester_id = ?
               AND status = 'approved'
             LIMIT 1"
        );
        $voucher_stmt->bind_param("ii", $voucher_id, $user_id);
        $voucher_stmt->execute();
        $voucher = $voucher_stmt->get_result()->fetch_assoc();

        if (!$voucher || !canUserVouch($conn, (int)$voucher['guarantor_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Voucher Rejected: The voucher must be approved by a currently eligible Tier 3 guarantor.'
            ]);
            exit();
        }

        if ($requested_principal > 2000) {
            echo json_encode([
                'success' => false,
                'message' => 'Graduated Scale Cap: Tier 2 vouched loans are capped at ₦2,000.'
            ]);
            exit();
        }
    }

    /*
     * TIER 3 / CRF APPROVED
     *
     * Graduated loan limits are based on
     * successful repayment history.
     */
    if ($tier === 3) {

        if ($cycles === 0 && $requested_principal > 2000) {

            echo json_encode([
                'success' => false,
                'message' => 'Graduated Release Limit: First-time loans are capped at ₦2,000.'
            ]);
            exit();

        } elseif ($cycles === 1 && $requested_principal > 5000) {

            echo json_encode([
                'success' => false,
                'message' => 'Graduated Release Limit: 1 successful repayment caps your next loan at ₦5,000.'
            ]);
            exit();

        } elseif ($cycles < 3 && $requested_principal > 10000) {

            echo json_encode([
                'success' => false,
                'message' => 'Graduated Release Limit: Below 3 successful repayments caps your loan at ₦10,000.'
            ]);
            exit();
        }
    }

    /*
     * Prevent multiple outstanding loan applications.
     *
     * Your actual database enum contains:
     * pending, approved, active, paid, defaulted
     *
     * Therefore 'funded' is NOT used here.
     */
    $check_stmt = $conn->prepare(
        "SELECT id
         FROM loans
         WHERE borrower_id = ?
         AND status IN ('pending', 'approved', 'active')
         LIMIT 1"
    );

    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You already have a loan application being processed.'
        ]);
        exit();
    }

    /*
     * EduLend financial model:
     *
     * Processing/service fee = 10% of principal.
     * 75% goes to lender pool profit.
     * 25% goes to platform.
     */
    $processing_fee = round($requested_principal * 0.10, 2);

    $pool_profit = round($processing_fee * 0.75, 2);

    $platform_fee = round($processing_fee * 0.25, 2);

    $total_repayment_obligation = round(
        $requested_principal + $processing_fee,
        2
    );

    /*
     * Repayment period.
     */
    if ($requested_principal <= 2000) {
        $days = 7;
    } elseif ($requested_principal <= 5000) {
        $days = 14;
    } elseif ($requested_principal <= 10000) {
        $days = 21;
    } else {
        $days = 30;
    }

    $due_date = date(
        'Y-m-d',
        strtotime("+{$days} days")
    );

    /*
     * Create the loan request.
     *
     * It starts as PENDING.
     * It is NOT approved yet.
     */
    $stmt = $conn->prepare(
        "INSERT INTO loans (
            borrower_id,
            amount,
            processing_fee,
            repayment_amount,
            platform_fee,
            due_date,
            voucher_id,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->bind_param(
        "iddddsi",
        $user_id,
        $requested_principal,
        $processing_fee,
        $total_repayment_obligation,
        $platform_fee,
        $due_date,
        $voucher_id
    );

    $stmt->execute();

    $loan_id = $conn->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'Loan request submitted successfully. It is now awaiting EduLend system review and pool funding.',
        'loan_id' => $loan_id,
        'amount' => number_format($requested_principal, 2, '.', ''),
        'processing_fee' => number_format($processing_fee, 2, '.', ''),
        'repayment_amount' => number_format($total_repayment_obligation, 2, '.', ''),
        'due_date' => $due_date,
        'status' => 'pending'
    ]);

} catch (Exception $e) {

    error_log(
        "EduLend Loan Request Error: " .
        $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit loan request at this time.'
    ]);
}
?>