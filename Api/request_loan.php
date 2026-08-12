 <?php
// Api/request_loan.php
session_start();
require_once __DIR__ . '/../Includes_dynamics/dataB.php';
require_once __DIR__ . '/../Includes_dynamics/credit_scorer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session vector.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$requested_principal = floatval($_POST['amount'] ?? 0);
$voucher_id = isset($_POST['voucher_id']) ? intval($_POST['voucher_id']) : null;
 
if ($requested_principal <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid loan amount."
    ]);
    exit();
}

// Rule: Absolute ceiling limit across the entire system
if ($requested_principal > 20000) {
    echo json_encode(['success' => false, 'message' => 'Transaction rejected. Loans cannot exceed ₦20,000.']);
    exit();
}
 

// EduLend Financial Model
// Borrower pays 10% service charge.
 $processing_fee = $requested_principal * 0.10;

$pool_profit = $processing_fee * 0.75;

$platform_fee = $processing_fee * 0.25;

$total_repayment_obligation = $requested_principal + $processing_fee;
 

// Fetch current user wallet standing metrics
$w_stmt = $conn->prepare("SELECT trust_tier, crf_status, successful_repayments FROM wallets WHERE user_id = ?");
$w_stmt->bind_param("i", $user_id);
$w_stmt->execute();
$wallet = $w_stmt->get_result()->fetch_assoc();

$tier = $wallet['trust_tier'] ?? 1;
$crf = $wallet['crf_status'] ?? 'unsubmitted';
$cycles = $wallet['successful_repayments'] ?? 0;

// Rule: Tier 1 cannot request any loans
if ($tier < 2) {
    echo json_encode(['success' => false, 'message' => 'Access Restricted: Tier 1 accounts cannot borrow. Complete Tier 2 verification first.']);
    exit();
}

// Rule: Tier 2 requires a validated Senior Peer Voucher and is strictly capped at ₦2,000
if ($tier == 2 && $crf !== 'approved') {
    if (is_null($voucher_id)) {
        echo json_encode(['success' => false, 'message' => 'Vouch Required: New Tier 2 accounts require a Senior Peer Guarantor to request loans.']);
        exit();
    }
    
    // Check if guarantor qualifies (Score >= 95% + 4 clean cycles handled by credit_scorer.php)
    if (!canUserVouch($conn, $voucher_id)) {
        echo json_encode(['success' => false, 'message' => 'Guarantor Rejected: Peer voucher requires a trust score >= 95% with 4 clean historical cycles.']);
        exit();
    }
    
    if ($requested_principal > 2000) {
        echo json_encode(['success' => false, 'message' => 'Graduated Scale Cap: Vouched Tier 2 entry contracts are capped at ₦2,000 max.']);
        exit();
    }
}

// Rule: Tier 3 Enforces self-reliant Graduated Release scales based on repayment frequencies
if ($tier == 3 || $crf === 'approved') {
    if ($cycles == 0 && $requested_principal > 2000) {
        $msg = "Graduated Release Limit: First-time loans are capped at ₦2,000.";
    } elseif ($cycles == 1 && $requested_principal > 5000) {
        $msg = "Graduated Release Limit: 1 successful repayment caps your next loan at ₦5,000.";
    } elseif ($cycles < 3 && $requested_principal > 10000) {
        $msg = "Graduated Release Limit: Below 3 successful repayments caps your loan at ₦10,000.";
    }

    if (isset($msg)) {
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
}

// Check if borrower already has an active loan
$check = $conn->prepare("
SELECT id FROM loans WHERE borrower_id = ? AND status IN ('pending','approved','funded','active')
");

$check->bind_param("i", $user_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "You already have a loan application being processed."
    ]);

    exit();
}

// If all requirements clear, proceed with posting the verified contract...
 // Loan due date (30 days from today)
 // Dynamic repayment period
if ($requested_principal <= 2000) {
    $days = 7;
} elseif ($requested_principal <= 5000) {
    $days = 14;
} elseif ($requested_principal <= 10000) {
    $days = 21;
} else {
    $days = 30;
}

$due_date = date('Y-m-d', strtotime("+$days days"));

  $stmt = $conn->prepare("
INSERT INTO loans(
borrower_id,
amount,
processing_fee,
repayment_amount,
platform_fee,
due_date,
status
)
VALUES (?, ?, ?, ?, ?, ?, 'pending')
");

  $stmt->bind_param(
    "idddds",
    $user_id,
    $requested_principal,
    $processing_fee,
    $total_repayment_obligation,
    $platform_fee,
    $due_date
);

if($stmt->execute()){

    echo json_encode([
        "success"=>true,
        "message"=> "Loan approved successfully. Funds will be processed from the EduLend Investment Pool."
]);

}else{

    echo json_encode([
        "success"=>false,
        "message"=>"Unable to submit loan request."
    ]);

}