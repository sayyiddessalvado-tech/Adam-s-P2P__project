 <?php
header("Content-Type: application/json");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../Includes_dynamics/dataB.php");

try {

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception("Unauthorized administrator action.");
    }

    $verificationId = intval($_POST["verification_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $reason = trim($_POST["reason"] ?? "");

    if ($verificationId <= 0) {
        throw new Exception("Invalid verification.");
    }

    if ($action != "approve" && $action != "reject") {
        throw new Exception("Invalid action.");
    }

    $conn->begin_transaction();

    // Get pending verification
    $verificationStmt = $conn->prepare("
        SELECT id, user_id, verification_type
        FROM verifications
        WHERE id = ? AND status = 'pending'
        FOR UPDATE
    ");
    $verificationStmt->bind_param("i", $verificationId);
    $verificationStmt->execute();
    $verification = $verificationStmt->get_result();

    if ($verification->num_rows == 0) {
        throw new Exception("No pending verification found.");
    }

    $verificationData = $verification->fetch_assoc();

    $userId = (int) $verificationData["user_id"];
    $verificationType = $verificationData["verification_type"];

    $walletStmt = $conn->prepare("SELECT trust_tier FROM wallets WHERE user_id = ? FOR UPDATE");
    $walletStmt->bind_param("i", $userId);
    $walletStmt->execute();
    $walletData = $walletStmt->get_result()->fetch_assoc();

    if (!$walletData) {
        throw new Exception("Student wallet not found.");
    }

    if ($action === "approve" && $verificationType === "course_registration_form" && (int) $walletData["trust_tier"] < 2) {
        throw new Exception("Student ID verification must be approved before approving the CRF.");
    }

    if ($action == "approve") {

        // Update verification
        $updateVerification = $conn->prepare("UPDATE verifications SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $updateVerification->bind_param("i", $verificationId);
        $updateVerification->execute();

        // Update wallet tier
        if ($verificationType == "student_id") {

            $conn->query("
                UPDATE wallets
                SET
                    trust_tier = GREATEST(trust_tier, 2),
                    crf_status = CASE WHEN crf_status = 'approved' THEN crf_status ELSE 'unsubmitted' END
                WHERE user_id=$userId
            ");

        } else {

            $conn->query("
                UPDATE wallets
                SET
                    trust_tier = GREATEST(trust_tier, 3),
                    crf_status = 'approved'
                WHERE user_id=$userId
            ");

        }

        // Notification
        $title = "Verification Approved";

        $message = "Congratulations! Your verification has been approved.";

    } else {

        // Reject verification
        $updateVerification = $conn->prepare("UPDATE verifications SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $updateVerification->bind_param("i", $verificationId);
        $updateVerification->execute();

        // Wallet status
        if ($verificationType === "course_registration_form") {
            $conn->query("UPDATE wallets SET crf_status = 'rejected' WHERE user_id = $userId");
        }

        $title = "Verification Rejected";

        $message = "Your verification was rejected.";

        if (!empty($reason)) {
            $message .= " Reason: " . $reason;
        }

    }

    // Save notification
    $stmt = $conn->prepare("
        INSERT INTO notifications
        (user_id,title,message)
        VALUES (?,?,?)
    ");

    $stmt->bind_param(
        "iss",
        $userId,
        $title,
        $message
    );

    $stmt->execute();

    // Admin Log
    $adminId = (int) $_SESSION['user_id'];

    $logAction = ucfirst($action) . " Verification";

    $description = "Admin {$action} verification for user ID {$userId}.";

    $stmt = $conn->prepare("
        INSERT INTO admin_log
        (admin_id,action,description)
        VALUES (?,?,?)
    ");

    $stmt->bind_param(
        "iss",
        $adminId,
        $logAction,
        $description
    );

    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Verification processed successfully."
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}
?>