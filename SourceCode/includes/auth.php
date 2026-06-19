<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authentication Helpers
|--------------------------------------------------------------------------
| This file contains reusable session and access-control functions.
| Include it on pages that need to know whether a user is logged in.
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Check if a student or admin is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

/*
 * Check if the logged-in user is an admin.
 */
function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/*
 * Redirect users who are not logged in.
 * Use this at the top of protected pages such as my_rsvps.php.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/*
 * Redirect users who are not admins.
 * Use this at the top of admin pages such as admin/dashboard.php.
 */
function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

/*
 * Store user data in the session after a successful login.
 * Only authentication data is stored in the session.
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
}

/*
 * Clear the session when the user logs out.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}