<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Events';
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$today = date('Y-m-d');

/*
 * Categories are loaded from the database so the filter stays accurate
 * when admins create new event types.
 */
$categoryStatement = $pdo->prepare(
    'SELECT DISTINCT category
     FROM events
     WHERE event_date >= :today
     ORDER BY category ASC'
);
$categoryStatement->execute([
    ':today' => $today,
]);
$categories = $categoryStatement->fetchAll();

$featuredStatement = $pdo->prepare(
    'SELECT
        e.*,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count,
        COUNT(DISTINCT a.id) AS attendance_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE e.is_featured = 1
        AND e.event_date >= :today
     GROUP BY e.id
     ORDER BY e.event_date ASC, e.event_time ASC
     LIMIT 3'
);
$featuredStatement->execute([
    ':today' => $today,
]);
$featuredEvents = $featuredStatement->fetchAll();

$whereClauses = ['e.event_date >= :today'];
$queryParams = [
    ':today' => $today,
];

if ($search !== '') {
    $whereClauses[] = '(e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)';
    $queryParams[':search'] = '%' . $search . '%';
}

if ($category !== '') {
    $whereClauses[] = 'e.category = :category';
    $queryParams[':category'] = $category;
}

$upcomingSql = sprintf(
    'SELECT
        e.*,
        COUNT(DISTINCT CASE WHEN r.status = "going" THEN r.id END) AS rsvp_count,
        COUNT(DISTINCT a.id) AS attendance_count
     FROM events e
     LEFT JOIN rsvps r ON r.event_id = e.id
     LEFT JOIN attendance a ON a.rsvp_id = r.id
     WHERE %s
     GROUP BY e.id
     ORDER BY e.event_date ASC, e.event_time ASC',
    implode(' AND ', $whereClauses)
);

$upcomingStatement = $pdo->prepare($upcomingSql);
$upcomingStatement->execute($queryParams);
$upcomingEvents = $upcomingStatement->fetchAll();

/*
 * Rendering event cards from one helper keeps featured and upcoming event
 * sections visually consistent.
 */
function renderEventCard(array $event, string $variant = ''): void
{
    $imagePath = $event['image'] ? 'assets/uploads/' . $event['image'] : '';
    $eventDate = date('M j, Y', strtotime($event['event_date']));
    $eventTime = date('g:i A', strtotime($event['event_time']));
    $capacity = (int) $event['capacity'];
    $rsvpCount = (int) $event['rsvp_count'];
    $attendanceCount = (int) $event['attendance_count'];
    $spotsLeft = max(0, $capacity - $rsvpCount);
    ?>
    <article class="event-card <?php echo htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?>">
        <a class="event-image" href="event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($imagePath !== ''): ?>
                <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
                <div class="event-image-fallback">
                    <span><?php echo htmlspecialchars(substr($event['category'], 0, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
        </a>

        <div class="event-card-body">
            <div class="event-meta-row">
                <span class="badge"><?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ((int) $event['is_featured'] === 1): ?>
                    <span class="badge badge-featured">Featured</span>
                <?php endif; ?>
            </div>

            <h3>
                <a href="event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </h3>

            <p class="event-description">
                <?php echo htmlspecialchars(mb_strimwidth($event['description'], 0, 140, '...'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <dl class="event-details">
                <div>
                    <dt>Date</dt>
                    <dd><?php echo htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Time</dt>
                    <dd><?php echo htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd><?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>

            <div class="event-stats" aria-label="Event attendance information">
                <span><?php echo htmlspecialchars((string) $rsvpCount, ENT_QUOTES, 'UTF-8'); ?> RSVPs</span>
                <span><?php echo htmlspecialchars((string) $attendanceCount, ENT_QUOTES, 'UTF-8'); ?> attended</span>
                <span><?php echo htmlspecialchars((string) $spotsLeft, ENT_QUOTES, 'UTF-8'); ?> spots left</span>
            </div>

            <a class="btn btn-primary event-card-action" href="event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
                View event
            </a>
        </div>
    </article>
    <?php
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="hero-content">
        <span class="eyebrow">Campus life starts here</span>
        <h1>Find events, save your spot, and stay connected.</h1>
        <p>
            Browse upcoming university events, RSVP in seconds, and track the
            activities shaping student life across campus.
        </p>

        <form class="hero-search" action="index.php" method="GET" role="search">
            <div class="search-field">
                <label for="search">Search events</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Search by title, topic, or venue"
                >
            </div>

            <div class="search-field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $categoryOption): ?>
                        <option
                            value="<?php echo htmlspecialchars($categoryOption['category'], ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $category === $categoryOption['category'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($categoryOption['category'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Search</button>
            <?php if ($search !== '' || $category !== ''): ?>
                <a class="btn btn-outline" href="index.php">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="hero-panel" aria-label="Event board summary">
        <div>
            <strong><?php echo htmlspecialchars((string) count($upcomingEvents), ENT_QUOTES, 'UTF-8'); ?></strong>
            <span>Upcoming events</span>
        </div>
        <div>
            <strong><?php echo htmlspecialchars((string) count($featuredEvents), ENT_QUOTES, 'UTF-8'); ?></strong>
            <span>Featured picks</span>
        </div>
        <div>
            <strong>24/7</strong>
            <span>Campus access</span>
        </div>
    </div>
</section>

<?php if (!empty($featuredEvents)): ?>
    <section class="content-section">
        <div class="section-heading">
            <span class="eyebrow">Featured</span>
            <h2>Highlighted campus events</h2>
            <p>Important events selected by campus administrators.</p>
        </div>

        <div class="event-grid featured-grid">
            <?php foreach ($featuredEvents as $event): ?>
                <?php renderEventCard($event, 'event-card-featured'); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="content-section">
    <div class="section-heading section-heading-row">
        <div>
            <span class="eyebrow">Upcoming</span>
            <h2>All upcoming events</h2>
            <p>Search, filter, and choose what belongs on your campus calendar.</p>
        </div>

        <span class="result-count">
            <?php echo htmlspecialchars((string) count($upcomingEvents), ENT_QUOTES, 'UTF-8'); ?> results
        </span>
    </div>

    <?php if (empty($upcomingEvents)): ?>
        <div class="empty-state">
            <h3>No events found</h3>
            <p>Try a different search term or clear your filters.</p>
            <a class="btn btn-primary" href="index.php">View all events</a>
        </div>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($upcomingEvents as $event): ?>
                <?php renderEventCard($event); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>