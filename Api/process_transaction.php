 <?php
// Api/process_transaction.php
// EduLend - Transaction History API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$user_id = intval($_SESSION['user_id']);

try {

    /*
     * Retrieve transactions belonging ONLY to
     * the currently authenticated user.
     */
    $stmt = $conn->prepare(
        "SELECT
            id,
            type,
            amount,
            reference,
            created_at
         FROM transactions
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $transactions = [];

    while ($row = $result->fetch_assoc()) {

        /*
         * The database does not contain a status column.
         * Therefore transaction records successfully stored
         * in the ledger are treated as completed.
         */
        $transactions[] = [
            'id' => intval($row['id']),
            'type' => $row['type'],
            'amount' => $row['amount'],
            'reference' => $row['reference'] ?? 'N/A',
            'status' => 'completed',
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);

} catch (Exception $e) {

    error_log(
        "EduLend Transaction History Error: " .
        $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve transaction history.'
    ]);
}
?>