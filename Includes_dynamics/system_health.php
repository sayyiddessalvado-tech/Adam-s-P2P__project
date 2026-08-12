<?php
// Includes_dynamics/system_health.php

/**
 * Appends runtime telemetry or system errors to a localized audit trail.
 */
function logSystemEvent($event_type, $message) {
    $log_directory = __DIR__ . '/../logs/';
    if (!is_dir($log_directory)) {
        mkdir($log_directory, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$event_type]: $message" . PHP_ROOT . PHP_EOL;
    
    // Append securely to an internal text database log file
    file_put_contents($log_directory . 'system_audit.log', $formatted_message, FILE_APPEND);
}
?>