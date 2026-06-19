<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$action = trim($_POST['action'] ?? '');
$userId = (int) $_SESSION['user_id'];

if (!$eventId || !in_array($action, ['going', 'cancel'], true)) {
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    /*
     * Lock the event row while checking capacity. This helps prevent two users
     * from taking the final seat at the same time.
     */
    $eventStatement = $pdo->prepare(
        'SELECT id, capacity
         FROM events
         WHERE id = :event_id
         LIMIT 1
         FOR UPDATE'
    );
    $eventStatement->execute([
        ':event_id' => $eventId,
    ]);
    $event = $eventStatement->fetch();

    if (!$event) {
        $pdo->rollBack();
        header('Location: index.php');
        exit;
    }

    $rsvpStatement = $pdo->prepare(
        'SELECT id, status
         FROM rsvps
         WHERE user_id = :user_id
            AND event_id = :event_id
         LIMIT 1'
    );
    $rsvpStatement->execute([
        ':user_id' => $userId,
        ':event_id' => $eventId,
    ]);
    $existingRsvp = $rsvpStatement->fetch();

    if ($action === 'going') {
        $countStatement = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM rsvps
             WHERE event_id = :event_id
                AND status = "going"'
        );
        $countStatement->execute([
            ':event_id' => $eventId,
        ]);
        $currentRsvpCount = (int) $countStatement->fetch()['total'];

        /*
         * If the user already has a cancelled RSVP, they can reactivate it.
         * A new duplicate row is never inserted.
         */
        $alreadyGoing = $existingRsvp && $existingRsvp['status'] === 'going';
        $hasRoom = $currentRsvpCount < (int) $event['capacity'] || $alreadyGoing;

        if ($hasRoom) {
            if ($existingRsvp) {
                $updateStatement = $pdo->prepare(
                    'UPDATE rsvps
                     SET status = "going"
                     WHERE id = :rsvp_id'
                );
                $updateStatement->execute([
                    ':rsvp_id' => (int) $existingRsvp['id'],
                ]);
            } else {
                $insertStatement = $pdo->prepare(
                    'INSERT INTO rsvps (user_id, event_id, status)
                     VALUES (:user_id, :event_id, "going")'
                );
                $insertStatement->execute([
                    ':user_id' => $userId,
                    ':event_id' => $eventId,
                ]);
            }
        }
    }

    if ($action === 'cancel' && $existingRsvp) {
        $cancelStatement = $pdo->prepare(
            'UPDATE rsvps
             SET status = "cancelled"
             WHERE id = :rsvp_id'
        );
        $cancelStatement->execute([
            ':rsvp_id' => (int) $existingRsvp['id'],
        ]);

        /*
         * If an RSVP is cancelled after attendance was marked, remove the
         * attendance record so attendance counts stay accurate.
         */
        $attendanceStatement = $pdo->prepare(
            'DELETE FROM attendance
             WHERE rsvp_id = :rsvp_id'
        );
        $attendanceStatement->execute([
            ':rsvp_id' => (int) $existingRsvp['id'],
        ]);
    }

    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('RSVP action failed: ' . $exception->getMessage());
}

header('Location: event.php?id=' . urlencode((string) $eventId));
exit;