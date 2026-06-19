<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
}

if (!$eventId) {
    header('Location: dashboard.php');
    exit;
}

$eventStatement = $pdo->prepare(
    'SELECT id, title, category, location, event_date, event_time, capacity
     FROM events
     WHERE id = :event_id
     LIMIT 1'
);
$eventStatement->execute([
    ':event_id' => $eventId,
]);
$event = $eventStatement->fetch();

if (!$event) {
    header('Location: dashboard.php');
    exit;
}

/*
 * Attendance is recorded from this page. INSERT IGNORE prevents duplicate
 * check-ins because attendance.rsvp_id is unique in the database.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rsvpId = filter_input(INPUT_POST, 'rsvp_id', FILTER_VALIDATE_INT);
    $action = trim($_POST['action'] ?? '');

    if ($rsvpId && $action === 'check_in') {
        $verifyStatement = $pdo->prepare(
            'SELECT id
             FROM rsvps
             WHERE id = :rsvp_id
                AND event_id = :event_id
                AND status = "going"
             LIMIT 1'
        );
        $verifyStatement->execute([
            ':rsvp_id' => $rsvpId,
            ':event_id' => $eventId,
        ]);

        if ($verifyStatement->fetch()) {
            $checkInStatement = $pdo->prepare(
                'INSERT IGNORE INTO attendance (rsvp_id, checked_in_by)
                 VALUES (:rsvp_id, :checked_in_by)'
            );
            $checkInStatement->execute([
                ':rsvp_id' => $rsvpId,
                ':checked_in_by' => (int) $_SESSION['user_id'],
            ]);
        }
    }

    header('Location: attendees.php?id=' . urlencode((string) $eventId));
    exit;
}

$search = trim($_GET['search'] ?? '');
$whereClauses = [
    'r.event_id = :event_id',
    'r.status = "going"',
];
$queryParams = [
    ':event_id' => $eventId,
];

if ($search !== '') {
    $whereClauses[] = '(u.full_name LIKE :search OR u.email LIKE :search)';
    $queryParams[':search'] = '%' . $search . '%';
}

$attendeeSql = sprintf(
    'SELECT
        r.id AS rsvp_id,
        r.created_at AS rsvp_created_at,
        u.full_name,
        u.email,
        a.id AS attendance_id,
        a.checked_in_at,
        admin.full_name AS checked_in_by_name
     FROM rsvps r
     INNER JOIN users u ON u.id = r.user_id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     LEFT JOIN users admin ON admin.id = a.checked_in_by
     WHERE %s
     ORDER BY a.checked_in_at DESC, u.full_name ASC',
    implode(' AND ', $whereClauses)
);

$attendeeStatement = $pdo->prepare($attendeeSql);
$attendeeStatement->execute($queryParams);
$attendees = $attendeeStatement->fetchAll();

$statsStatement = $pdo->prepare(
    'SELECT
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS total_rsvps,
        COUNT(DISTINCT CASE WHEN r.status = "cancelled" THEN r.id END) AS cancelled_rsvps,
        COUNT(DISTINCT a.id) AS total_attendance
     FROM rsvps r
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE r.event_id = :event_id'
);
$statsStatement->execute([
    ':event_id' => $eventId,
]);
$stats = $statsStatement->fetch();

$totalRsvps = (int) $stats['total_rsvps'];
$totalAttendance = (int) $stats['total_attendance'];
$cancelledRsvps = (int) $stats['cancelled_rsvps'];
$capacity = (int) $event['capacity'];
$spotsLeft = max(0, $capacity - $totalRsvps);
$attendanceRate = $totalRsvps > 0 ? round(($totalAttendance / $totalRsvps) * 100) : 0;

$pageTitle = 'Event Attendees';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">Attendance tracking</span>
        <h1><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>
            <?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?>
            at
            <?php echo htmlspecialchars(date('g:i A', strtotime($event['event_time'])), ENT_QUOTES, 'UTF-8'); ?>
            ·
            <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>

    <div class="heading-actions">
        <a class="btn btn-outline" href="edit_event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">Edit event</a>
        <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
    </div>
</section>

<section class="dashboard-grid" aria-label="RSVP statistics">
    <article class="dashboard-card">
        <span>Active RSVPs</span>
        <strong><?php echo htmlspecialchars((string) $totalRsvps, ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars((string) $spotsLeft, ENT_QUOTES, 'UTF-8'); ?> spots left</small>
    </article>

    <article class="dashboard-card">
        <span>Checked in</span>
        <strong><?php echo htmlspecialchars((string) $totalAttendance, ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars((string) $attendanceRate, ENT_QUOTES, 'UTF-8'); ?>% attendance rate</small>
    </article>

    <article class="dashboard-card">
        <span>Capacity</span>
        <strong><?php echo htmlspecialchars((string) $capacity, ENT_QUOTES, 'UTF-8'); ?></strong>
        <small>Total available seats</small>
    </article>

    <article class="dashboard-card">
        <span>Cancelled</span>
        <strong><?php echo htmlspecialchars((string) $cancelledRsvps, ENT_QUOTES, 'UTF-8'); ?></strong>
        <small>Cancelled reservations</small>
    </article>
</section>

<section class="admin-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Attendees</span>
            <h2>RSVP list</h2>
        </div>

        <form class="compact-search" action="attendees.php" method="GET" role="search">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $eventId, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="search">Search attendees</label>
            <input
                type="search"
                id="search"
                name="search"
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Name or email"
            >
            <button class="btn btn-primary btn-small" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a class="btn btn-outline btn-small" href="attendees.php?id=<?php echo htmlspecialchars((string) $eventId, ENT_QUOTES, 'UTF-8'); ?>">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($attendees)): ?>
        <div class="empty-state compact-empty">
            <h3>No attendees found</h3>
            <p>There are no active RSVPs matching your search.</p>
        </div>
    <?php else: ?>
        <div class="responsive-table admin-table attendees-table" role="table" aria-label="Event attendees">
            <div class="table-row table-head" role="row">
                <div role="columnheader">Student</div>
                <div role="columnheader">RSVP date</div>
                <div role="columnheader">Attendance</div>
                <div role="columnheader">Action</div>
            </div>

            <?php foreach ($attendees as $attendee): ?>
                <?php $isCheckedIn = $attendee['attendance_id'] !== null; ?>
                <article class="table-row" role="row">
                    <div role="cell" data-label="Student">
                        <strong><?php echo htmlspecialchars($attendee['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo htmlspecialchars($attendee['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div role="cell" data-label="RSVP date">
                        <?php echo htmlspecialchars(date('M j, Y', strtotime($attendee['rsvp_created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div role="cell" data-label="Attendance">
                        <?php if ($isCheckedIn): ?>
                            <span class="status-badge status-success">Checked in</span>
                            <small>
                                <?php echo htmlspecialchars(date('M j, g:i A', strtotime($attendee['checked_in_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                by
                                <?php echo htmlspecialchars($attendee['checked_in_by_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                            </small>
                        <?php else: ?>
                            <span class="status-badge">Not checked in</span>
                        <?php endif; ?>
                    </div>

                    <div role="cell" data-label="Action">
                        <?php if ($isCheckedIn): ?>
                            <span class="status-note">Attendance recorded</span>
                        <?php else: ?>
                            <form action="attendees.php" method="POST">
                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string) $eventId, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="rsvp_id" value="<?php echo htmlspecialchars((string) $attendee['rsvp_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="check_in">
                                <button class="btn btn-primary btn-small" type="submit">Check in</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>