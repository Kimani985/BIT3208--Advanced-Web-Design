<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

$statsStatement = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM users WHERE role = "student") AS total_students,
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM events) AS total_events,
        (SELECT COUNT(*) FROM rsvps WHERE status = "going") AS total_rsvps,
        (SELECT COUNT(*) FROM events WHERE is_featured = 1) AS featured_events,
        (SELECT COUNT(*) FROM attendance) AS total_attendance'
);
$statsStatement->execute();
$stats = $statsStatement->fetch();

$attendanceRate = 0;

if ((int) $stats['total_rsvps'] > 0) {
    $attendanceRate = round(((int) $stats['total_attendance'] / (int) $stats['total_rsvps']) * 100);
}

$featuredStatement = $pdo->prepare(
    'SELECT
        e.id,
        e.title,
        e.category,
        e.location,
        e.event_date,
        e.event_time,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count,
        COUNT(DISTINCT a.id) AS attendance_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE e.is_featured = 1
     GROUP BY e.id
     ORDER BY e.event_date ASC, e.event_time ASC
     LIMIT 5'
);
$featuredStatement->execute();
$featuredEvents = $featuredStatement->fetchAll();

$topAttendanceStatement = $pdo->prepare(
    'SELECT
        e.id,
        e.title,
        e.event_date,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count,
        COUNT(DISTINCT a.id) AS attendance_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     GROUP BY e.id
     ORDER BY attendance_count DESC, rsvp_count DESC
     LIMIT 5'
);
$topAttendanceStatement->execute();
$attendanceEvents = $topAttendanceStatement->fetchAll();

$upcomingStatement = $pdo->prepare(
    'SELECT
        e.id,
        e.title,
        e.category,
        e.event_date,
        e.event_time,
        e.capacity,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     WHERE e.event_date >= :today
     GROUP BY e.id
     ORDER BY e.event_date ASC, e.event_time ASC
     LIMIT 6'
);
$upcomingStatement->execute([
    ':today' => date('Y-m-d'),
]);
$upcomingEvents = $upcomingStatement->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-hero">
    <div>
        <span class="eyebrow">Admin workspace</span>
        <h1>Campus event operations</h1>
        <p>Monitor student engagement, featured programming, RSVPs, and attendance from one dashboard.</p>
    </div>

    <a class="btn btn-primary" href="create_event.php">Create event</a>
</section>

<section class="dashboard-grid" aria-label="Dashboard summary cards">
    <article class="dashboard-card">
        <span>Total users</span>
        <strong><?php echo htmlspecialchars((string) $stats['total_users'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars((string) $stats['total_students'], ENT_QUOTES, 'UTF-8'); ?> student accounts</small>
    </article>

    <article class="dashboard-card">
        <span>Total events</span>
        <strong><?php echo htmlspecialchars((string) $stats['total_events'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars((string) $stats['featured_events'], ENT_QUOTES, 'UTF-8'); ?> featured</small>
    </article>

    <article class="dashboard-card">
        <span>Total RSVPs</span>
        <strong><?php echo htmlspecialchars((string) $stats['total_rsvps'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <small>Active student reservations</small>
    </article>

    <article class="dashboard-card">
        <span>Attendance rate</span>
        <strong><?php echo htmlspecialchars((string) $attendanceRate, ENT_QUOTES, 'UTF-8'); ?>%</strong>
        <small><?php echo htmlspecialchars((string) $stats['total_attendance'], ENT_QUOTES, 'UTF-8'); ?> checked in</small>
    </article>
</section>

<section class="admin-layout">
    <div class="admin-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">Featured events</span>
                <h2>Current featured programming</h2>
            </div>
            <a href="create_event.php">Add new</a>
        </div>

        <?php if (empty($featuredEvents)): ?>
            <div class="empty-state compact-empty">
                <h3>No featured events</h3>
                <p>Mark an event as featured to highlight it on the homepage.</p>
            </div>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($featuredEvents as $event): ?>
                    <article class="admin-list-item">
                        <div>
                            <span class="badge"><?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3>
                                <a href="../event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h3>
                            <p>
                                <?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                at
                                <?php echo htmlspecialchars(date('g:i A', strtotime($event['event_time'])), ENT_QUOTES, 'UTF-8'); ?>
                                ·
                                <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>

                        <div class="admin-list-stats">
                            <span><?php echo htmlspecialchars((string) $event['rsvp_count'], ENT_QUOTES, 'UTF-8'); ?> RSVPs</span>
                            <span><?php echo htmlspecialchars((string) $event['attendance_count'], ENT_QUOTES, 'UTF-8'); ?> attended</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="admin-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">Attendance analytics</span>
                <h2>Top check-ins</h2>
            </div>
        </div>

        <?php if (empty($attendanceEvents)): ?>
            <div class="empty-state compact-empty">
                <h3>No attendance yet</h3>
                <p>Attendance will appear after admins check in students.</p>
            </div>
        <?php else: ?>
            <div class="analytics-list">
                <?php foreach ($attendanceEvents as $event): ?>
                    <?php
                    $eventRsvps = (int) $event['rsvp_count'];
                    $eventAttendance = (int) $event['attendance_count'];
                    $eventRate = $eventRsvps > 0 ? round(($eventAttendance / $eventRsvps) * 100) : 0;
                    ?>
                    <article class="analytics-item">
                        <div>
                            <strong><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="progress-track" aria-label="Attendance rate <?php echo htmlspecialchars((string) $eventRate, ENT_QUOTES, 'UTF-8'); ?> percent">
                            <span style="width: <?php echo htmlspecialchars((string) $eventRate, ENT_QUOTES, 'UTF-8'); ?>%"></span>
                        </div>

                        <small>
                            <?php echo htmlspecialchars((string) $eventAttendance, ENT_QUOTES, 'UTF-8'); ?>
                            of
                            <?php echo htmlspecialchars((string) $eventRsvps, ENT_QUOTES, 'UTF-8'); ?>
                            checked in
                        </small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>

<section class="admin-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Upcoming schedule</span>
            <h2>Next events</h2>
        </div>
        <a href="../index.php">View public board</a>
    </div>

    <?php if (empty($upcomingEvents)): ?>
        <div class="empty-state compact-empty">
            <h3>No upcoming events</h3>
            <p>Create an event to begin building the campus schedule.</p>
        </div>
    <?php else: ?>
        <div class="responsive-table admin-table" role="table" aria-label="Upcoming events">
            <div class="table-row table-head" role="row">
                <div role="columnheader">Event</div>
                <div role="columnheader">Date</div>
                <div role="columnheader">RSVPs</div>
                <div role="columnheader">Actions</div>
            </div>

            <?php foreach ($upcomingEvents as $event): ?>
                <article class="table-row" role="row">
                    <div role="cell" data-label="Event">
                        <strong><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div role="cell" data-label="Date">
                        <?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?>
                        at
                        <?php echo htmlspecialchars(date('g:i A', strtotime($event['event_time'])), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div role="cell" data-label="RSVPs">
                        <?php echo htmlspecialchars((string) $event['rsvp_count'], ENT_QUOTES, 'UTF-8'); ?>
                        /
                        <?php echo htmlspecialchars((string) $event['capacity'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div role="cell" data-label="Actions" class="table-actions">
                        <a class="btn btn-outline btn-small" href="attendees.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">Attendees</a>
                        <a class="btn btn-primary btn-small" href="edit_event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>