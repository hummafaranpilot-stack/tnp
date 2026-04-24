<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/auth.php';

start_session_once();

if (is_logged_in()) {
    header('Location: /admin');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if (hash_equals(ADMIN_PASSWORD, $pw)) {
        $_SESSION['tnp_authed'] = true;
        session_regenerate_id(true);
        header('Location: /admin');
        exit;
    }
    $error = 'Incorrect password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNP Admin — Login</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h1>TNP Admin</h1>
        <p class="subtitle">Enter your admin password to continue</p>
        <form method="POST" action="/login">
            <input type="password" name="password" placeholder="Admin password" autofocus required>
            <?php if ($error): ?>
                <p class="error"><?= h($error) ?></p>
            <?php endif; ?>
            <button type="submit">Login</button>
        </form>
        <p class="back-link"><a href="/">← Back to public page</a></p>
    </div>
</body>
</html>
