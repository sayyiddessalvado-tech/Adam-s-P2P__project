<?php
// api/auth_handler.php

// 1. START SECURE SESSION ENVIRONMENT
session_start();

// 2. IMPORT DATABASE CONNECTION CONFIG
require_once '../Includes_dynamics/dataB.php';

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
            die("All fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email address.");
        }

        if (strlen($password) < 8) {
            die("Password must be at least 8 characters.");
        }

        $allowed_roles = ['student', 'lender'];

        if (!in_array($role, $allowed_roles)) {
            die("Invalid account type.");
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
            
            echo "<script>alert('Account created successfully! Please log in.'); window.location.href='../p2p.html';</script>";
            exit();

        } catch (Exception $e) {
            $conn->rollback(); // Revert changes on database failure
            die("Registration Failed: " . $e->getMessage());
        }
    }

    // ================================================
    // HANDLER LOGIC: LOGIN
    // ================================================
    if ($action === 'login') {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            die("Email and password are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Invalid email address.");
        }

        if (strlen($password) < 8) {
            die("Password must be at least 8 characters.");
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
        header("Location: ../dashboard.php");
        break;

    case 'lender':
        header("Location: ../lender_dashboard.php");
        break;

    case 'admin':
        header("Location: ../admin_dashboard.php");
        break;

    default:
        die("Unknown account type.");
}

exit();
         }  
        }

        // Catch-all response for failed authorizations
        echo "<script>alert('Invalid email address or secure account password.'); window.location.href='../p2p.html';</script>";
        exit();
    }
}
 
?>