<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Campus Event Board';
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminSection = str_contains($currentPath, '/admin/');
$basePath = $isAdminSection ? '../' : '';

/*
 * This helper marks the current menu item so users can see where they are.
 */
function isActiveNavItem(string $targetPath, string $currentPath): string
{
    return str_ends_with($currentPath, $targetPath) ? ' is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | Campus Event Board</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="navbar" aria-label="Main navigation">
            <a class="brand" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>index.php">
                <span class="brand-mark" aria-hidden="true">CE</span>
                <span class="brand-text">
                    <strong>Campus Event Board</strong>
                    <small>University Events Portal</small>
                </span>
            </a>

            <button
                class="nav-toggle"
                type="button"
                aria-label="Open navigation menu"
                aria-controls="primary-navigation"
                aria-expanded="false"
                data-nav-toggle
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-menu" id="primary-navigation" data-nav-menu>
                <ul class="nav-links">
                    <li>
                        <a class="nav-link<?php echo isActiveNavItem('/index.php', $currentPath); ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>index.php">
                            Events
                        </a>
                    </li>

                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <li>
                            <a class="nav-link<?php echo isActiveNavItem('/my_rsvps.php', $currentPath); ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>my_rsvps.php">
                                My RSVPs
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <li>
                            <a class="nav-link<?php echo isActiveNavItem('/admin/dashboard.php', $currentPath); ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>admin/dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="nav-link<?php echo isActiveNavItem('/admin/create_event.php', $currentPath); ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>admin/create_event.php">
                                Create Event
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="nav-actions">
                    <?php if (isLoggedIn()): ?>
                        <span class="user-pill">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
                            <small><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'student'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </span>
                        <a class="btn btn-outline" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>logout.php">
                            Logout
                        </a>
                    <?php else: ?>
                        <a class="nav-link<?php echo isActiveNavItem('/login.php', $currentPath); ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>login.php">
                            Login
                        </a>
                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>register.php">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="page-shell">