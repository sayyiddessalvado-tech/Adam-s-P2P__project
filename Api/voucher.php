<?php
// Api/voucher.php - peer voucher lifecycle API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Student authentication is required.']);
    exit();
}

require_once __DIR__ . '/../Includes_dynamics/dataB.php';

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'my_status';

function voucherResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

function requireVoucherCsrf(): void
{
    $client_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $client_token)) {
        voucherResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
    }
}

function voucherInput(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? $json : $_POST;
}

function eligibleGuarantor(mysqli $conn, int $user_id, int $guarantor_id): bool
{
    $stmt = $conn->prepare(
        "SELECT u.id
         FROM users u
         INNER JOIN wallets w ON w.user_id = u.id
         WHERE u.id = ?
           AND u.role = 'student'
           AND u.id <> ?
           AND w.trust_tier >= 3
           AND w.credit_score >= 95
           AND w.successful_repayments >= 4
           AND w.is_defaulted = 0
         LIMIT 1"
    );
    $stmt->bind_param('ii', $guarantor_id, $user_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows === 1;
}

try {
    if ($action === 'my_status') {
        $stmt = $conn->prepare(
            "SELECT v.id, v.status, v.created_at, v.responded_at,
                    u.fullname AS guarantor_name
             FROM vouchers v
             INNER JOIN users u ON u.id = v.guarantor_id
             WHERE v.requester_id = ?
             ORDER BY v.id DESC
             LIMIT 1"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $voucher = $stmt->get_result()->fetch_assoc();

        $tier_stmt = $conn->prepare("SELECT trust_tier FROM wallets WHERE user_id = ? LIMIT 1");
        $tier_stmt->bind_param('i', $user_id);
        $tier_stmt->execute();
        $tier_row = $tier_stmt->get_result()->fetch_assoc();

        voucherResponse([
            'success' => true,
            'trust_tier' => (int)($tier_row['trust_tier'] ?? 1),
            'voucher_id' => $voucher ? (int)$voucher['id'] : null,
            'voucher_status' => $voucher['status'] ?? null,
            'guarantor_name' => $voucher['guarantor_name'] ?? null,
            'created_at' => $voucher['created_at'] ?? null,
            'responded_at' => $voucher['responded_at'] ?? null
        ]);
    }

    if ($action === 'eligible_guarantors') {
        $stmt = $conn->prepare(
            "SELECT u.id, u.fullname, w.credit_score,
                    w.successful_repayments, w.trust_tier
             FROM users u
             INNER JOIN wallets w ON w.user_id = u.id
             WHERE u.id <> ?
               AND u.role = 'student'
               AND w.trust_tier >= 3
               AND w.credit_score >= 95
               AND w.successful_repayments >= 4
               AND w.is_defaulted = 0
             ORDER BY w.credit_score DESC, w.successful_repayments DESC, u.fullname ASC"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();

        $guarantors = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $guarantors[] = [
                'id' => (int)$row['id'],
                'fullname' => $row['fullname'],
                'credit_score' => (float)$row['credit_score'],
                'successful_repayments' => (int)$row['successful_repayments'],
                'trust_tier' => (int)$row['trust_tier']
            ];
        }

        voucherResponse(['success' => true, 'guarantors' => $guarantors]);
    }

    if ($action === 'incoming') {
        $stmt = $conn->prepare(
            "SELECT v.id, v.status, v.created_at,
                    u.id AS requester_id, u.fullname AS requester_name
             FROM vouchers v
             INNER JOIN users u ON u.id = v.requester_id
             WHERE v.guarantor_id = ?
               AND v.status = 'pending'
             ORDER BY v.id DESC"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();

        $requests = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $requests[] = [
                'id' => (int)$row['id'],
                'requester_id' => (int)$row['requester_id'],
                'requester_name' => $row['requester_name'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }

        voucherResponse(['success' => true, 'requests' => $requests]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        voucherResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    requireVoucherCsrf();
    $input = voucherInput();

    if ($action === 'request') {
        $guarantor_id = (int)($input['guarantor_id'] ?? 0);

        if ($guarantor_id <= 0 || !eligibleGuarantor($conn, $user_id, $guarantor_id)) {
            voucherResponse(['success' => false, 'message' => 'That student is not an eligible guarantor.'], 422);
        }

        $tier_stmt = $conn->prepare("SELECT trust_tier FROM wallets WHERE user_id = ? LIMIT 1");
        $tier_stmt->bind_param('i', $user_id);
        $tier_stmt->execute();
        $tier = (int)($tier_stmt->get_result()->fetch_assoc()['trust_tier'] ?? 1);

        if ($tier !== 2) {
            voucherResponse(['success' => false, 'message' => 'Only Tier 2 students need a peer voucher.'], 422);
        }

        $duplicate_stmt = $conn->prepare(
            "SELECT id FROM vouchers
             WHERE requester_id = ? AND status IN ('pending', 'approved')
             LIMIT 1"
        );
        $duplicate_stmt->bind_param('i', $user_id);
        $duplicate_stmt->execute();

        if ($duplicate_stmt->get_result()->num_rows > 0) {
            voucherResponse(['success' => false, 'message' => 'You already have a pending or approved voucher.'], 409);
        }

        $stmt = $conn->prepare(
            "INSERT INTO vouchers (requester_id, guarantor_id, status)
             VALUES (?, ?, 'pending')"
        );
        $stmt->bind_param('ii', $user_id, $guarantor_id);
        $stmt->execute();

        voucherResponse([
            'success' => true,
            'message' => 'Voucher request sent successfully.',
            'voucher_id' => $conn->insert_id
        ]);
    }

    if ($action === 'respond') {
        $voucher_id = (int)($input['voucher_id'] ?? 0);
        $decision = $input['decision'] ?? '';

        if ($voucher_id <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
            voucherResponse(['success' => false, 'message' => 'A valid voucher decision is required.'], 422);
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "SELECT requester_id, status
             FROM vouchers
             WHERE id = ? AND guarantor_id = ?
             FOR UPDATE"
        );
        $stmt->bind_param('ii', $voucher_id, $user_id);
        $stmt->execute();
        $voucher = $stmt->get_result()->fetch_assoc();

        if (!$voucher || $voucher['status'] !== 'pending') {
            $conn->rollback();
            voucherResponse(['success' => false, 'message' => 'This voucher request is no longer available.'], 409);
        }

        if ($decision === 'approve' && !eligibleGuarantor($conn, (int)$voucher['requester_id'], $user_id)) {
            $conn->rollback();
            voucherResponse(['success' => false, 'message' => 'You no longer meet the guarantor requirements.'], 422);
        }

        $new_status = $decision === 'approve' ? 'approved' : 'rejected';
        $update = $conn->prepare(
            "UPDATE vouchers
             SET status = ?, responded_at = CURRENT_TIMESTAMP
             WHERE id = ? AND guarantor_id = ? AND status = 'pending'"
        );
        $update->bind_param('sii', $new_status, $voucher_id, $user_id);
        $update->execute();
        $conn->commit();

        voucherResponse([
            'success' => true,
            'message' => $decision === 'approve' ? 'Voucher request approved.' : 'Voucher request rejected.'
        ]);
    }

    voucherResponse(['success' => false, 'message' => 'Unknown voucher action.'], 404);
} catch (Throwable $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }

    error_log('EduLend Voucher Error: ' . $e->getMessage());

    $message = stripos($e->getMessage(), "doesn't exist") !== false
        ? 'Voucher setup is incomplete. Import vouchers.sql into the edulend_db database first.'
        : 'Voucher service is temporarily unavailable.';

    voucherResponse(['success' => false, 'message' => $message], 500);
}
