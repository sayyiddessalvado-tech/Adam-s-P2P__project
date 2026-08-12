 <?php
// Api/process_transaction.php
session_start();
require_once __DIR__ . '/../Includes_dynamics/dataB.php';
 
header('Content-Type: application/json');

// Guard clause: Ensure user is completely authenticated before executing financial mutations
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access vector.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount'] ?? 0);
$type = $_POST['type'] ?? ''; // 'deposit' or 'withdrawal'

if ($_SESSION['role'] != 'lender') {
    echo json_encode([
        "success"=>false,
        "message"=>"Only lenders can perform this operation."
    ]);
    exit();
}

if ($amount <= 0 || !in_array($type, ['deposit', 'withdrawal'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction parameters.']);
    exit();
}

// Open explicit SQL Transaction context to enforce strict database consistency
$conn->begin_transaction();

try {
    // Implement Pessimistic Row-Level Locking to entirely eliminate race conditions
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();

    if (!$wallet) {
        throw new Exception("Target wallet profile could not be localized.");
    }

    $current_balance = floatval($wallet['balance']);
     
    if ($type === 'withdrawal') {
        if ($current_balance < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient liquidity reserves.']);
            $conn->rollback();
            exit();
        }
        $new_balance = $current_balance - $amount;
    } else {
        // The type is a deposit
        $new_balance = $current_balance + $amount;
    }

    // Update the baseline wallet balance
    $update_stmt = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
    $update_stmt->bind_param("di", $new_balance, $user_id);
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows < 1) {
    throw new Exception("Wallet balance update failed.");
}

    // Log the event securely into the immutable transaction ledger
    $log_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, created_at) VALUES (?, ?, ?, 'completed', NOW())");
    
    if (!$log_stmt) {
    throw new Exception("Unable to create transaction log.");
}
    $log_stmt->bind_param("isd", $user_id, $type, $amount);
    $log_stmt->execute();


    $conn->commit();

    // Prepare JSON response data package
    $response = [
        'success' => true,
        'message' => ($type === 'deposit') ? 'Funds successfully credited.' : 'Funds successfully disbursed.',
        'new_balance' => $new_balance
    ];

     
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Critical Transaction Execution Failure: ' . $e->getMessage()]);
}
?>