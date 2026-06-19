<?php
declare(strict_types=1);

$currentYear = (int) date('Y');
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminSection = str_contains($currentPath, '/admin/');
$basePath = $isAdminSection ? '../' : '';
?>
    </main>

    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="brand-mark" aria-hidden="true">CE</span>
                <div>
                    <strong>Campus Event Board</strong>
                    <p>
                        A modern university portal for discovering events,
                        managing RSVPs, and tracking attendance.
                    </p>
                </div>
            </div>

            <div class="footer-column">
                <h2>Explore</h2>
                <ul>
                    <li><a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>index.php">Events</a></li>
                    <li><a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>my_rsvps.php">My RSVPs</a></li>
                    <li><a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>login.php">Student Login</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h2>Connect</h2>
                <ul class="social-links">
                    <li><a href="https://www.facebook.com" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                    <li><a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                    <li><a href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
                    <li><a href="https://www.youtube.com" target="_blank" rel="noopener noreferrer">YouTube</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                &copy; <?php echo htmlspecialchars((string) $currentYear, ENT_QUOTES, 'UTF-8'); ?>
                Campus Event Board. All rights reserved.
            </p>
            <p>Built for secure campus event management.</p>
        </div>
    </footer>

    <script src="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>assets/js/app.js"></script>
</body>
</html>