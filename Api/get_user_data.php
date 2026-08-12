<?php
// Api/get_user_data.php
session_start();
header('Content-Type: application/json');

// Guard Clause: Secure session verification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User session expired or invalid.']);
    exit();
}

require_once '../Includes_dynamics/dataB.php';
$user_id = $_SESSION['user_id'];

try {
    // Query both the user profile and wallet metadata simultaneously
    $stmt = $conn->prepare("
        SELECT u.fullname, u.email, u.role, w.balance, w.debt, w.is_defaulted, w.credit_score 
        FROM users u 
        LEFT JOIN wallets w ON u.id = w.user_id 
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();

    if (!$userData) {
        throw new Exception("Account profile record could not be fetched.");
    }

    // Return the clean structural data array to the frontend
    echo json_encode([
        'success' => true,
        'data' => [
            'name'         => $userData['fullname'],
            'email'        => $userData['email'],
            'role'         => $userData['role'],
            'balance'      => floatval($userData['balance']),
            'debt'         => floatval($userData['debt']),
            'is_defaulted' => intval($userData['is_defaulted']),
            'credit_score' => intval($userData['credit_score'] ?? 80)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>