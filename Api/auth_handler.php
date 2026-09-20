<?php
// api/auth_handler.php

// 1. START SECURE SESSION ENVIRONMENT
session_start();

// 2. IMPORT DATABASE CONNECTION CONFIG
require_once '../Includes_dynamics/dataB.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function auth_response(array $payload, int $status = 200): void
{
    global $is_ajax;

    if ($is_ajax) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }

    if (!$payload['success']) {
        die($payload['message']);
    }
}

// 3. CAPTURE & SANITIZE POST REQUEST INCOMING VARIABLES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // ================================================
    // HANDLER LOGIC: REGISTRATION
    // ================================================
    if ($action === 'register') {
        $fullname = trim($_POST['fullname']);
        $email    = trim($_POST['email']);
        $role     = trim($_POST['role']);
        $password = $_POST['password'];

         // Guard Clause
        if (empty($fullname) || empty($email) || empty($role) || empty($password)) {
            auth_response(['success' => false, 'message' => 'All fields are required.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            auth_response(['success' => false, 'message' => 'Invalid email address.'], 422);
        }

        if (strlen($password) < 8) {
            auth_response(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
        }

        $allowed_roles = ['student', 'lender'];

        if (!in_array($role, $allowed_roles)) {
            auth_response(['success' => false, 'message' => 'Invalid account type.'], 422);
        }
    

        // Securely hash password using modern standard algorithm
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Begin Transaction to write both User Profile and Default Wallet securely
        $conn->begin_transaction();

        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email address is already registered on EduLend.");
            }

            // Insert into 'users' table
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $password_hash, $role);
            $stmt->execute();
            
            // user's wallet
              $user_id = $stmt->insert_id;

            $stmt = $conn->prepare("
            INSERT INTO wallets
            (user_id,balance,debt,credit_score,trust_tier,successful_repayments,is_defaulted,crf_status)
            VALUES (?,0.00,0.00,80,1,0,0,'unsubmitted')
            ");

            $stmt->bind_param("i",$user_id);
            $stmt->execute();
                    
            // Commit changes if everything works perfectly
            $conn->commit();

            auth_response([
                'success' => true,
                'title' => 'Account created',
                'message' => 'Your EduLend account is ready. You can now log in.'
            ]);

        } catch (Exception $e) {
            $conn->rollback(); // Revert changes on database failure
            auth_response(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 422);
        }
    }

    // ================================================
    // HANDLER LOGIC: LOGIN
    // ================================================
    if ($action === 'login') {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            auth_response(['success' => false, 'message' => 'Email and password are required.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            auth_response(['success' => false, 'message' => 'Invalid email address.'], 422);
        }

        if (strlen($password) < 8) {
            auth_response(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
        }
            
        // Query profile from database
        $stmt = $conn->prepare("SELECT id, fullname, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify submitted plain text password against database hash string
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                
                // Set Global Server Sessions
             $_SESSION['user_id'] = $user['id'];
             $_SESSION['fullname'] = $user['fullname'];
             $_SESSION['role'] = $user['role'];
             
             switch ($user['role']) {

    case 'student':
        $redirect = '/EduLend/dashboard.php';
        break;

    case 'lender':
        $redirect = '/EduLend/lender_dashboard.php';
        break;

    case 'admin':
        $redirect = '/EduLend/admin_dashboard.html';
        break;

    default:
        auth_response(['success' => false, 'message' => 'Unknown account type.'], 422);
}

if ($is_ajax) {
    auth_response([
        'success' => true,
        'title' => 'Welcome back',
        'message' => 'Login successful. Opening your dashboard now.',
        'redirect' => $redirect
    ]);
}

header("Location: $redirect");
exit();
         }  
        }

        // Catch-all response for failed authorizations
        if ($is_ajax) {
            auth_response(['success' => false, 'message' => 'Invalid email address or secure account password.'], 401);
        }

        echo "<script>alert('Invalid email address or secure account password.'); window.location.href='../p2p.html';</script>";
        exit();
    }
}
 
?>