 <?php
header("Content-Type: application/json");

require_once("../Includes_dynamics/dataB.php");

try {

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method.");
    }

    $userId = intval($_POST["user_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $reason = trim($_POST["reason"] ?? "");

    if ($userId <= 0) {
        throw new Exception("Invalid user.");
    }

    if ($action != "approve" && $action != "reject") {
        throw new Exception("Invalid action.");
    }

    $conn->begin_transaction();

    // Get pending verification
    $verification = $conn->query("
        SELECT *
        FROM verifications
        WHERE user_id=$userId
        AND status='pending'
        ORDER BY submitted_at DESC
        LIMIT 1
    ");

    if ($verification->num_rows == 0) {
        throw new Exception("No pending verification found.");
    }

    $verificationData = $verification->fetch_assoc();

    $verificationId = $verificationData["id"];
    $verificationType = $verificationData["verification_type"];

    if ($action == "approve") {

        // Update verification
        $conn->query("
            UPDATE verifications
            SET status='approved'
            WHERE id=$verificationId
        ");

        // Update wallet tier
        if ($verificationType == "student_id") {

            $conn->query("
                UPDATE wallets
                SET
                    trust_tier=2,
                    crf_status='approved'
                WHERE user_id=$userId
            ");

        } else {

            $conn->query("
                UPDATE wallets
                SET
                    trust_tier=3,
                    crf_status='approved'
                WHERE user_id=$userId
            ");

        }

        // Notification
        $title = "Verification Approved";

        $message = "Congratulations! Your verification has been approved.";

    } else {

        // Reject verification
        $conn->query("
            UPDATE verifications
            SET status='rejected'
            WHERE id=$verificationId
        ");

        // Wallet status
        $conn->query("
            UPDATE wallets
            SET crf_status='rejected'
            WHERE user_id=$userId
        ");

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
    $adminId = 1;

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