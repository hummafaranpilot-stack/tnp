<?php
function start_session_once(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in(): bool {
    start_session_once();
    return !empty($_SESSION['tnp_authed']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
}

function require_login_api(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function is_transparent_image(string $url): bool {
    return (bool) preg_match('/\.(png|webp|svg)(\?|#|$)/i', $url);
}
