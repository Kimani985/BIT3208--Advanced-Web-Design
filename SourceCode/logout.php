<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

/*
 * Log the user out by clearing all authentication session data.
 * The helper also destroys the active session on the server.
 */
logoutUser();

/*
 * Start a fresh session only long enough to show a friendly message
 * on the login page if the project later adds flash messages.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_regenerate_id(true);

header('Location: login.php');
exit;