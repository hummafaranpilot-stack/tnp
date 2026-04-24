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
    <title>TNP Admin — Sign In</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: time() ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-logo">TNP</div>
        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to manage your affiliate offers</p>
        <form method="POST" action="/login">
            <label for="pw">Admin Password</label>
            <input id="pw" type="password" name="password" placeholder="••••••••" autofocus required>
            <?php if ($error): ?>
                <p class="error"><?= h($error) ?></p>
            <?php endif; ?>
            <button type="submit">Sign In</button>
        </form>
        <p class="back-link"><a href="/">← Back to public site</a></p>
    </div>
</body>
</html>
