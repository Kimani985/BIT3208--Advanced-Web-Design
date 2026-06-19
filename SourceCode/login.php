<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
        exit;
    }

    header('Location: index.php');
    exit;
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
     * Keep login errors general. This avoids telling attackers whether an
     * email address exists in the system.
     */
    $loginError = 'Invalid email or password.';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $loginError;
    } else {
        $userStatement = $pdo->prepare(
            'SELECT id, full_name, email, password, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $userStatement->execute([
            ':email' => $email,
        ]);

        $user = $userStatement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = $loginError;
        } else {
            loginUser($user);

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            }

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Campus Event Board</title>
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
            width: min(980px, 100%);
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

        .role-paths {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }

        .role-path {
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
        }

        .role-path strong {
            display: block;
            margin-bottom: 4px;
            color: #ffffff;
        }

        .role-path span {
            color: #d1d5db;
            font-size: 0.92rem;
            line-height: 1.5;
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
                grid-template-columns: 0.95fr 1.05fr;
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
        <section class="auth-layout" aria-label="Account login">
            <div class="auth-intro">
                <h1>Campus Event Board</h1>
                <p>
                    Sign in to manage your event plans, discover featured activities,
                    or administer campus events from one organized workspace.
                </p>

                <div class="role-paths" aria-label="Available account roles">
                    <div class="role-path">
                        <strong>Students</strong>
                        <span>Browse events, RSVP, and view your upcoming campus schedule.</span>
                    </div>
                    <div class="role-path">
                        <strong>Admins</strong>
                        <span>Create events, update details, and track attendance.</span>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <h2>Welcome back</h2>
                <p class="subtext">Log in with your registered email address.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" novalidate>
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="you@example.com"
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
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button class="btn" type="submit">Log in</button>
                </form>

                <p class="auth-footer">
                    New to Campus Event Board?
                    <a href="register.php">Create an account</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>