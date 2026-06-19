<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId) {
    header('Location: index.php');
    exit;
}

$eventStatement = $pdo->prepare(
    'SELECT
        e.*,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count,
        COUNT(DISTINCT a.id) AS attendance_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE e.id = :event_id
     GROUP BY e.id
     LIMIT 1'
);
$eventStatement->execute([
    ':event_id' => $eventId,
]);
$event = $eventStatement->fetch();

if (!$event) {
    header('Location: index.php');
    exit;
}

$userRsvp = null;

if (isLoggedIn()) {
    /*
     * Find the logged-in user's RSVP so the button can show the correct action.
     */
    $rsvpStatement = $pdo->prepare(
        'SELECT id, status
         FROM rsvps
         WHERE user_id = :user_id
            AND event_id = :event_id
         LIMIT 1'
    );
    $rsvpStatement->execute([
        ':user_id' => (int) $_SESSION['user_id'],
        ':event_id' => $eventId,
    ]);
    $userRsvp = $rsvpStatement->fetch();
}

$pageTitle = $event['title'];
$eventDate = date('F j, Y', strtotime($event['event_date']));
$eventTime = date('g:i A', strtotime($event['event_time']));
$capacity = (int) $event['capacity'];
$rsvpCount = (int) $event['rsvp_count'];
$attendanceCount = (int) $event['attendance_count'];
$spotsLeft = max(0, $capacity - $rsvpCount);
$isFull = $spotsLeft === 0;
$imagePath = $event['image'] ? 'assets/uploads/' . $event['image'] : '';
$isGoing = $userRsvp && $userRsvp['status'] === 'going';

require_once __DIR__ . '/includes/header.php';
?>

<section class="event-detail-hero">
    <div class="event-detail-media">
        <?php if ($imagePath !== ''): ?>
            <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php else: ?>
            <div class="event-image-fallback event-detail-fallback">
                <span><?php echo htmlspecialchars(substr($event['category'], 0, 2), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="event-detail-summary">
        <div class="event-meta-row">
            <span class="badge"><?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ((int) $event['is_featured'] === 1): ?>
                <span class="badge badge-featured">Featured</span>
            <?php endif; ?>
        </div>

        <h1><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <p class="event-detail-description">
            <?php echo nl2br(htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8')); ?>
        </p>

        <div class="event-action-panel">
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-primary" href="login.php">Log in to RSVP</a>
            <?php elseif (isAdmin()): ?>
                <a class="btn btn-primary" href="admin/edit_event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">Edit event</a>
            <?php elseif ($isGoing): ?>
                <form action="rsvp.php" method="POST">
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn-outline" type="submit">Cancel RSVP</button>
                </form>
            <?php elseif ($isFull): ?>
                <button class="btn btn-disabled" type="button" disabled>Event full</button>
            <?php else: ?>
                <form action="rsvp.php" method="POST">
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="going">
                    <button class="btn btn-primary" type="submit">RSVP now</button>
                </form>
            <?php endif; ?>

            <?php if ($isGoing): ?>
                <span class="status-note">You are going to this event.</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="content-section event-info-section">
    <div class="event-info-grid">
        <article class="info-card">
            <span class="info-label">Venue</span>
            <strong><?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>

        <article class="info-card">
            <span class="info-label">Date</span>
            <strong><?php echo htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>

        <article class="info-card">
            <span class="info-label">Time</span>
            <strong><?php echo htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>

        <article class="info-card">
            <span class="info-label">Capacity</span>
            <strong><?php echo htmlspecialchars((string) $capacity, ENT_QUOTES, 'UTF-8'); ?> seats</strong>
        </article>
    </div>

    <div class="event-metrics-grid">
        <article class="metric-card">
            <span>RSVPs</span>
            <strong><?php echo htmlspecialchars((string) $rsvpCount, ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>

        <article class="metric-card">
            <span>Attendance</span>
            <strong><?php echo htmlspecialchars((string) $attendanceCount, ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>

        <article class="metric-card">
            <span>Spots left</span>
            <strong><?php echo htmlspecialchars((string) $spotsLeft, ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>