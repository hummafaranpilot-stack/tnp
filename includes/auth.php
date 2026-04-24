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

/**
 * Resolve the chip-color type slug from a free-form advice string.
 * Older landers may have been saved with type='custom' even when the
 * advice text was "VSL" — re-derive here so the color is consistent.
 */
function advice_type_for(string $advice): string {
    return match (strtolower(trim($advice))) {
        'prelander', 'pre-lander'    => 'short',
        'direct-link', 'direct link' => 'long',
        'vsl'                        => 'vsl',
        default                      => 'custom',
    };
}

/**
 * Inline "Coming Soon" chip shown when an offer row has the coming_soon
 * flag set and the requested field is blank. Keeps the viewer table
 * non-empty while the admin still fills in real details.
 */
function coming_soon_chip(): string {
    return '<span class="coming-soon-chip">Coming Soon</span>';
}
