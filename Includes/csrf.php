<?php
// Includes/csrf.php - ensure a session CSRF token and output meta tag
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Output meta tag for client-side use
echo '<meta name="csrf-token" content="' . htmlspecialchars($_SESSION['csrf_token']) . '">';

?>
