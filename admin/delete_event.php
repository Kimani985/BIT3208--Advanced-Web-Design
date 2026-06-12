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
    'SELECT id, title, category, location, event_date, event_time, image
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmDelete = $_POST['confirm_delete'] ?? '';

    if ($confirmDelete === 'yes') {
        $deleteStatement = $pdo->prepare(
            'DELETE FROM events
             WHERE id = :event_id
             LIMIT 1'
        );
        $deleteStatement->execute([
            ':event_id' => $eventId,
        ]);

        /*
         * Related RSVPs and attendance rows are deleted automatically by
         * database foreign keys. Remove the uploaded image after the event row.
         */
        if ($event['image']) {
            $imagePath = __DIR__ . '/../assets/uploads/' . $event['image'];

            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Delete Event';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">Admin</span>
        <h1>Delete event</h1>
        <p>Review the event below before permanently removing it from the board.</p>
    </div>

    <a class="btn btn-outline" href="dashboard.php">Back to dashboard</a>
</section>

<section class="delete-panel">
    <div class="alert alert-danger" role="alert">
        <strong>This action cannot be undone.</strong>
        <p>
            Deleting this event will also remove related RSVPs and attendance records.
        </p>
    </div>

    <article class="delete-summary">
        <span class="badge"><?php echo htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8'); ?></span>
        <h2><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>
            <?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?>
            at
            <?php echo htmlspecialchars(date('g:i A', strtotime($event['event_time'])), ENT_QUOTES, 'UTF-8'); ?>
            ·
            <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </article>

    <form class="delete-actions" action="delete_event.php" method="POST">
        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="confirm_delete" value="yes">
        <button class="btn btn-danger" type="submit">Delete event</button>
        <a class="btn btn-outline" href="edit_event.php?id=<?php echo htmlspecialchars((string) $event['id'], ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>