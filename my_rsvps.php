<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$pageTitle = 'My RSVPs';
$userId = (int) $_SESSION['user_id'];

$rsvpStatement = $pdo->prepare(
    'SELECT
        r.id AS rsvp_id,
        r.status,
        r.created_at AS rsvp_created_at,
        e.id AS event_id,
        e.title,
        e.description,
        e.category,
        e.location,
        e.event_date,
        e.event_time,
        e.capacity,
        e.image,
        a.id AS attendance_id,
        a.checked_in_at
     FROM rsvps r
     INNER JOIN events e ON e.id = r.event_id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE r.user_id = :user_id
        AND r.status = "going"
     ORDER BY e.event_date ASC, e.event_time ASC'
);
$rsvpStatement->execute([
    ':user_id' => $userId,
]);
$rsvps = $rsvpStatement->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">Student dashboard</span>
        <h1>My RSVPs</h1>
        <p>Review the campus events you have reserved and cancel any RSVP you no longer need.</p>
    </div>

    <a class="btn btn-primary" href="index.php">Browse events</a>
</section>

<section class="content-section">
    <?php if (empty($rsvps)): ?>
        <div class="empty-state">
            <h2>No active RSVPs yet</h2>
            <p>Find an upcoming event and reserve your spot.</p>
            <a class="btn btn-primary" href="index.php">Explore events</a>
        </div>
    <?php else: ?>
        <div class="responsive-table rsvp-table" role="table" aria-label="My event RSVPs">
            <div class="table-row table-head" role="row">
                <div role="columnheader">Event</div>
                <div role="columnheader">Date</div>
                <div role="columnheader">Venue</div>
                <div role="columnheader">Status</div>
                <div role="columnheader">Action</div>
            </div>

            <?php foreach ($rsvps as $rsvp): ?>
                <?php
                $imagePath = $rsvp['image'] ? 'assets/uploads/' . $rsvp['image'] : '';
                $eventDate = date('M j, Y', strtotime($rsvp['event_date']));
                $eventTime = date('g:i A', strtotime($rsvp['event_time']));
                $isCheckedIn = $rsvp['attendance_id'] !== null;
                ?>
                <article class="table-row rsvp-row" role="row">
                    <div class="rsvp-event-cell" role="cell" data-label="Event">
                        <a class="rsvp-thumb" href="event.php?id=<?php echo htmlspecialchars((string) $rsvp['event_id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($imagePath !== ''): ?>
                                <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($rsvp['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars(substr($rsvp['category'], 0, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>

                        <div>
                            <span class="badge"><?php echo htmlspecialchars($rsvp['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h2>
                                <a href="event.php?id=<?php echo htmlspecialchars((string) $rsvp['event_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($rsvp['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h2>
                            <p><?php echo htmlspecialchars(mb_strimwidth($rsvp['description'], 0, 100, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>

                    <div role="cell" data-label="Date">
                        <strong><?php echo htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div role="cell" data-label="Venue">
                        <?php echo htmlspecialchars($rsvp['location'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div role="cell" data-label="Status">
                        <?php if ($isCheckedIn): ?>
                            <span class="status-badge status-success">Checked in</span>
                        <?php else: ?>
                            <span class="status-badge">Going</span>
                        <?php endif; ?>
                    </div>

                    <div role="cell" data-label="Action">
                        <?php if ($isCheckedIn): ?>
                            <span class="status-note">Attendance recorded</span>
                        <?php else: ?>
                            <form action="rsvp.php" method="POST">
                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string) $rsvp['event_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button class="btn btn-outline btn-small" type="submit">Cancel RSVP</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>