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

function platform_class(string $p): string {
    return match ($p) {
        'BuyGoods'    => 'badge badge-blue',
        'ClickBank'   => 'badge badge-gray',
        'Digistore24' => 'badge badge-slate',
        'MaxWeb'      => 'badge badge-slate',
        default       => 'badge badge-slate',
    };
}

function category_class(string $c): string {
    return match ($c) {
        'Weight Loss'      => 'pill pill-green',
        'Male Enhancement' => 'pill pill-maroon',
        'Blood Sugar'      => 'pill pill-orange',
        'Brain Health'     => 'pill pill-purple',
        'Joint Pain'       => 'pill pill-yellow',
        default            => 'pill pill-slate',
    };
}

/**
 * Description shown in the hover tooltip for a manually-chosen
 * capsule value (prelander / direct-link / VSL).
 */
function advice_description(string $advice): string {
    return match (strtolower(trim($advice))) {
        'prelander', 'pre-lander'       => 'Prelander — best for warming up cold traffic before landing on this page.',
        'direct-link', 'direct link'    => 'Direct link — the long copy already does the persuasion itself, no prelander needed.',
        'vsl'                           => 'VSL (Video Sales Letter) — best for viewers who prefer to watch videos over reading.',
        default                         => '',
    };
}
