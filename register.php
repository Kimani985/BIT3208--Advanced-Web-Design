<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$fullName = '';
$email = '';
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
     * Server-side validation protects the database even if someone bypasses
     * the browser's built-in form validation.
     */
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
        $errors[] = 'Full name must be between 2 and 120 characters.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 160) {
        $errors[] = 'Email address must be 160 characters or fewer.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $checkEmailStatement = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email LIMIT 1'
        );
        $checkEmailStatement->execute([
            ':email' => $email,
        ]);

        if ($checkEmailStatement->fetch()) {
            $errors[] = 'An account with this email address already exists.';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $createUserStatement = $pdo->prepare(
            'INSERT INTO users (full_name, email, password, role)
             VALUES (:full_name, :email, :password, :role)'
        );

        $createUserStatement->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => 'student',
        ]);

        $fullName = '';
        $email = '';
        $successMessage = 'Registration successful. You can now log in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Campus Event Board</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --page-bg: #f6f8fb;
            --panel-bg: #ffffff;
            --text-main: #172033;
            --text-muted: #667085;
            --brand: #2563eb;
            --brand-dark: #1d4ed8;
            --border: #d8e0ea;
            --danger-bg: #fff1f2;
            --danger-text: #be123c;
            --success-bg: #ecfdf3;
            --success-text: #027a48;
            --shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text-main);
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.10), transparent 32%),
                linear-gradient(315deg, rgba(20, 184, 166, 0.10), transparent 30%),
                var(--page-bg);
        }

        .auth-page {
            width: 100%;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .auth-layout {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: 1fr;
            background: var(--panel-bg);
            border: 1px solid rgba(216, 224, 234, 0.75);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .auth-intro {
            padding: 32px;
            background: #111827;
            color: #ffffff;
        }

        .auth-intro h1 {
            margin: 0 0 16px;
            font-size: clamp(2rem, 6vw, 3.4rem);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .auth-intro p {
            max-width: 560px;
            margin: 0;
            color: #d1d5db;
            font-size: 1rem;
            line-height: 1.7;
        }

        .auth-card {
            padding: 32px;
        }

        .auth-card h2 {
            margin: 0 0 8px;
            font-size: 1.75rem;
            letter-spacing: 0;
        }

        .auth-card .subtext {
            margin: 0 0 24px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.95rem;
        }

        input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-main);
            font: inherit;
            background: #ffffff;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
        }

        .btn {
            width: 100%;
            min-height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            color: #ffffff;
            background: var(--brand);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 160ms ease, transform 160ms ease;
        }

        .btn:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            line-height: 1.5;
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .alert-danger {
            color: var(--danger-text);
            background: var(--danger-bg);
            border: 1px solid #fecdd3;
        }

        .alert-success {
            color: var(--success-text);
            background: var(--success-bg);
            border: 1px solid #abefc6;
        }

        .auth-footer {
            margin-top: 20px;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.6;
        }

        .auth-footer a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (min-width: 800px) {
            .auth-layout {
                grid-template-columns: 0.92fr 1.08fr;
            }

            .auth-intro,
            .auth-card {
                padding: 48px;
            }

            .auth-intro {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <section class="auth-layout" aria-label="Student registration">
            <div class="auth-intro">
                <h1>Campus Event Board</h1>
                <p>
                    Create your student account to discover campus activities,
                    save your spot at events, and track the events you plan to attend.
                </p>
            </div>

            <div class="auth-card">
                <h2>Create account</h2>
                <p class="subtext">Use your active email address and a secure password.</p>

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

                <form action="register.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="full_name">Full name</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Jane Student"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="jane@example.com"
                            maxlength="160"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            minlength="8"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Repeat your password"
                            minlength="8"
                            required
                        >
                    </div>

                    <button class="btn" type="submit">Create student account</button>
                </form>

                <p class="auth-footer">
                    Already have an account?
                    <a href="login.php">Log in</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>