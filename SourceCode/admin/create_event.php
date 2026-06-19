<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Create Event';
$errors = [];
$successMessage = '';

$title = '';
$description = '';
$category = '';
$location = '';
$eventDate = '';
$eventTime = '';
$capacity = '';
$isFeatured = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $isFeatured = isset($_POST['is_featured']);
    $imageName = null;

    if ($title === '' || mb_strlen($title) > 180) {
        $errors[] = 'Event title is required and must be 180 characters or fewer.';
    }

    if ($description === '') {
        $errors[] = 'Event description is required.';
    }

    if ($category === '' || mb_strlen($category) > 80) {
        $errors[] = 'Category is required and must be 80 characters or fewer.';
    }

    if ($location === '' || mb_strlen($location) > 160) {
        $errors[] = 'Location is required and must be 160 characters or fewer.';
    }

    if ($eventDate === '') {
        $errors[] = 'Event date is required.';
    } elseif (strtotime($eventDate) === false) {
        $errors[] = 'Please enter a valid event date.';
    }

    if ($eventTime === '') {
        $errors[] = 'Event time is required.';
    } elseif (strtotime($eventTime) === false) {
        $errors[] = 'Please enter a valid event time.';
    }

    if ($capacity === '' || filter_var($capacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors[] = 'Capacity must be a whole number greater than zero.';
    }

    /*
     * Image upload is optional. When supplied, validate size and MIME type
     * before moving the file into the uploads folder.
     */
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image = $_FILES['image'];
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if ($image['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The event image could not be uploaded. Please try again.';
        } elseif ($image['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Event image must be 2MB or smaller.';
        } else {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $image['tmp_name']);
            finfo_close($fileInfo);

            if (!array_key_exists($mimeType, $allowedTypes)) {
                $errors[] = 'Event image must be a JPG, PNG, or WEBP file.';
            } else {
                $uploadDirectory = __DIR__ . '/../assets/uploads';

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $imageName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                $destination = $uploadDirectory . '/' . $imageName;

                if (!move_uploaded_file($image['tmp_name'], $destination)) {
                    $errors[] = 'The event image could not be saved.';
                    $imageName = null;
                }
            }
        }
    }

    if (empty($errors)) {
        $createStatement = $pdo->prepare(
            'INSERT INTO events (
                title,
                description,
                category,
                location,
                event_date,
                event_time,
                capacity,
                image,
                is_featured,
                created_by
             )
             VALUES (
                :title,
                :description,
                :category,
                :location,
                :event_date,
                :event_time,
                :capacity,
                :image,
                :is_featured,
                :created_by
             )'
        );

        $createStatement->execute([
            ':title' => $title,
            ':description' => $description,
            ':category' => $category,
            ':location' => $location,
            ':event_date' => $eventDate,
            ':event_time' => $eventTime,
            ':capacity' => (int) $capacity,
            ':image' => $imageName,
            ':is_featured' => $isFeatured ? 1 : 0,
            ':created_by' => (int) $_SESSION['user_id'],
        ]);

        $title = '';
        $description = '';
        $category = '';
        $location = '';
        $eventDate = '';
        $eventTime = '';
        $capacity = '';
        $isFeatured = false;
        $successMessage = 'Event created successfully.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">Admin</span>
        <h1>Create event</h1>
        <p>Add a new campus event with details, capacity, image, and featured placement.</p>
    </div>

    <a class="btn btn-outline" href="dashboard.php">Back to dashboard</a>
</section>

<section class="form-section">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success" role="status">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form class="event-form" action="create_event.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="title">Event title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="180"
                    required
                >
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="7" required><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input
                    type="text"
                    id="category"
                    name="category"
                    value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="80"
                    placeholder="Workshop, Social, Sports"
                    required
                >
            </div>

            <div class="form-group">
                <label for="location">Venue</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    value="<?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="160"
                    required
                >
            </div>

            <div class="form-group">
                <label for="event_date">Date</label>
                <input
                    type="date"
                    id="event_date"
                    name="event_date"
                    value="<?php echo htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="event_time">Time</label>
                <input
                    type="time"
                    id="event_time"
                    name="event_time"
                    value="<?php echo htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="capacity">Capacity</label>
                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    value="<?php echo htmlspecialchars($capacity, ENT_QUOTES, 'UTF-8'); ?>"
                    min="1"
                    required
                >
            </div>

            <div class="form-group">
                <label for="image">Event image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <small>Optional. JPG, PNG, or WEBP. Maximum 2MB.</small>
            </div>

            <label class="checkbox-field full-width" for="is_featured">
                <input
                    type="checkbox"
                    id="is_featured"
                    name="is_featured"
                    value="1"
                    <?php echo $isFeatured ? 'checked' : ''; ?>
                >
                <span>Feature this event on the homepage</span>
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create event</button>
            <a class="btn btn-outline" href="dashboard.php">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>