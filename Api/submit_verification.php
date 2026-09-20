<?php
// Api/submit_verification.php
// EduLend - Student Verification Submission API

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

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

require_once __DIR__ . '/../Includes_dynamics/dataB.php';

$user_id = (int) $_SESSION['user_id'];

try {

    /*
     * ---------------------------------------------------------
     * ACCEPT VERIFICATION TYPE
     * ---------------------------------------------------------
     *
     * student_id
     * course_registration_form
     */

    $verification_type = trim($_POST['verification_type'] ?? '');

    $allowed_types = [
        'student_id',
        'course_registration_form'
    ];

    if (!in_array($verification_type, $allowed_types, true)) {
        throw new Exception('Invalid verification type.');
    }

    /*
     * ---------------------------------------------------------
     * CHECK USER WALLET
     * ---------------------------------------------------------
     */

    $wallet_stmt = $conn->prepare("
        SELECT
            trust_tier,
            crf_status
        FROM wallets
        WHERE user_id = ?
        LIMIT 1
    ");

    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();

    $wallet = $wallet_stmt->get_result()->fetch_assoc();

    if (!$wallet) {
        throw new Exception('Student wallet record not found.');
    }

    $trust_tier = (int) $wallet['trust_tier'];
    $crf_status = $wallet['crf_status'];

    /*
     * ---------------------------------------------------------
     * ENFORCE VERIFICATION ORDER
     * ---------------------------------------------------------
     *
     * Tier 1 -> Student ID -> Tier 2
     * Tier 2 -> CRF -> Tier 3
     */

    if ($verification_type === 'student_id') {

        if ($trust_tier >= 2) {
            throw new Exception(
                'Student ID verification has already been completed.'
            );
        }

    } elseif ($verification_type === 'course_registration_form') {

        if ($trust_tier < 2) {
            throw new Exception(
                'Complete Student ID verification before submitting your CRF.'
            );
        }

        if ($trust_tier >= 3) {
            throw new Exception(
                'CRF verification has already been completed.'
            );
        }
    }

    /*
     * ---------------------------------------------------------
     * CHECK UPLOADED FILE
     * ---------------------------------------------------------
     */

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a verification document.');
    }

    $file = $_FILES['document'];

    /*
     * Maximum file size: 5 MB
     */

    $max_size = 5 * 1024 * 1024;

    if ($file['size'] <= 0 || $file['size'] > $max_size) {
        throw new Exception(
            'The verification document must be between 1 byte and 5 MB.'
        );
    }

    /*
     * ---------------------------------------------------------
     * VALIDATE FILE TYPE
     * ---------------------------------------------------------
     */

    $allowed_extensions = [
        'jpg',
        'jpeg',
        'png',
        'pdf'
    ];

    $extension = strtolower(
        pathinfo($file['name'], PATHINFO_EXTENSION)
    );

    if (!in_array($extension, $allowed_extensions, true)) {
        throw new Exception(
            'Invalid file type. Upload JPG, JPEG, PNG, or PDF.'
        );
    }

    /*
     * Validate actual MIME type instead of trusting extension.
     */

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'application/pdf'
    ];

    if (!in_array($mime, $allowed_mimes, true)) {
        throw new Exception('Invalid document format.');
    }

    /*
     * ---------------------------------------------------------
     * CHECK FOR EXISTING PENDING SUBMISSION
     * ---------------------------------------------------------
     */

    $pending_stmt = $conn->prepare("
        SELECT id
        FROM verifications
        WHERE user_id = ?
        AND verification_type = ?
        AND status = 'pending'
        LIMIT 1
    ");

    $pending_stmt->bind_param(
        "is",
        $user_id,
        $verification_type
    );

    $pending_stmt->execute();

    if ($pending_stmt->get_result()->num_rows > 0) {
        throw new Exception(
            'You already have a pending verification of this type.'
        );
    }

    /*
     * ---------------------------------------------------------
     * CREATE UPLOAD DIRECTORY
     * ---------------------------------------------------------
     */

    $upload_dir = __DIR__ . '/../uploads/verifications/';

    if (!is_dir($upload_dir)) {

        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception(
                'Unable to create verification upload directory.'
            );
        }
    }

    /*
     * ---------------------------------------------------------
     * GENERATE SAFE FILE NAME
     * ---------------------------------------------------------
     */

    $safe_name = bin2hex(random_bytes(16)) . '.' . $extension;

    $target_path = $upload_dir . $safe_name;

    /*
     * ---------------------------------------------------------
     * MOVE UPLOADED FILE
     * ---------------------------------------------------------
     */

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception(
            'Unable to save the verification document.'
        );
    }

    /*
     * Database path.
     */

    $document_path =
        'uploads/verifications/' . $safe_name;

    /*
     * ---------------------------------------------------------
     * UPDATE WALLET STATUS
     * ---------------------------------------------------------
     */

    if ($verification_type === 'student_id') {

        $new_crf_status = 'id_pending';

    } else {

        $new_crf_status = 'crf_pending';
    }

    /*
     * ---------------------------------------------------------
     * DATABASE TRANSACTION
     * ---------------------------------------------------------
     */

    $conn->begin_transaction();

    $insert = $conn->prepare("
        INSERT INTO verifications
        (
            user_id,
            verification_type,
            document_path,
            status
        )
        VALUES (?, ?, ?, 'pending')
    ");

    $insert->bind_param(
        "iss",
        $user_id,
        $verification_type,
        $document_path
    );

    $insert->execute();

    /*
     * Update wallet status.
     */

    $wallet_update = $conn->prepare("
        UPDATE wallets
        SET crf_status = ?
        WHERE user_id = ?
    ");

    $wallet_update->bind_param(
        "si",
        $new_crf_status,
        $user_id
    );

    $wallet_update->execute();

    /*
     * ---------------------------------------------------------
     * NOTIFICATION
     * ---------------------------------------------------------
     */

    $title = "Verification Submitted";

    if ($verification_type === 'student_id') {
        $message =
            "Your Student ID verification has been submitted and is awaiting admin review.";
    } else {
        $message =
            "Your Course Registration Form verification has been submitted and is awaiting admin review.";
    }

    $notification = $conn->prepare("
        INSERT INTO notifications
        (
            user_id,
            title,
            message
        )
        VALUES (?, ?, ?)
    ");

    $notification->bind_param(
        "iss",
        $user_id,
        $title,
        $message
    );

    $notification->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {

    if ($conn->errno === 0) {
        // No action required.
    }

    if ($conn->connect_errno === 0) {
        try {
            $conn->rollback();
        } catch (Exception $rollback_error) {
            // Ignore rollback errors.
        }
    }

    error_log(
        "EduLend Verification Submission Error: " .
        $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>