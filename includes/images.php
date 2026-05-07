<?php
/**
 * Image compression / resize helpers (uses GD, available by default on
 * shared hosts like Hostinger). On upload we resize anything bigger than
 * MAX_DIM on its longest side to MAX_DIM, and re-encode at QUALITY%.
 * That keeps thumbnails snappy on slow networks where the original
 * 3-4 MB product photo would time out.
 */

const TNP_IMG_MAX_DIM   = 800;   // longest-side cap in pixels
const TNP_IMG_QUALITY   = 82;    // jpeg/webp quality
const TNP_IMG_PNG_LEVEL = 6;     // 0 (none) – 9 (max)

/**
 * Resize and re-encode the file at $path in place. Returns
 *   ['ok'=>true,  'before'=>bytes, 'after'=>bytes, 'reason'=>'…']
 *   ['ok'=>false, 'error'=>'…']
 *
 * If the file is already smaller than MIN_TO_TOUCH and within the dim
 * cap, we leave it alone and report 'skipped'.
 */
function compress_image(string $path, int $min_to_touch_bytes = 80 * 1024): array {
    if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
        return ['ok' => false, 'error' => 'not accessible: ' . basename($path)];
    }
    $before = (int)filesize($path);

    $info = @getimagesize($path);
    if (!$info) return ['ok' => false, 'error' => 'not an image: ' . basename($path)];
    [$w, $h] = $info;
    $mime = $info['mime'] ?? '';

    $needs_resize = max($w, $h) > TNP_IMG_MAX_DIM;
    if (!$needs_resize && $before <= $min_to_touch_bytes) {
        return ['ok' => true, 'before' => $before, 'after' => $before, 'reason' => 'already small'];
    }

    // Decode based on the real mime type (filename extension is just a hint).
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        'image/gif'  => @imagecreatefromgif($path),
        default      => false,
    };
    if (!$src) return ['ok' => false, 'error' => "decode failed ({$mime}): " . basename($path)];

    if ($needs_resize) {
        $ratio = TNP_IMG_MAX_DIM / max($w, $h);
        $nw = max(1, (int)round($w * $ratio));
        $nh = max(1, (int)round($h * $ratio));
        $dst = imagecreatetruecolor($nw, $nh);
        // Keep alpha for PNG/WEBP/GIF; matters for transparent product shots.
        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }

    // Re-encode in place.
    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($src, $path, TNP_IMG_QUALITY),
        'image/png'  => imagepng($src, $path, TNP_IMG_PNG_LEVEL),
        'image/webp' => function_exists('imagewebp') ? imagewebp($src, $path, TNP_IMG_QUALITY) : false,
        'image/gif'  => imagegif($src, $path),
        default      => false,
    };
    imagedestroy($src);

    if (!$ok) return ['ok' => false, 'error' => 'encode failed: ' . basename($path)];
    clearstatcache(true, $path);
    $after = (int)filesize($path);
    return [
        'ok' => true,
        'before' => $before,
        'after'  => $after,
        'reason' => $needs_resize ? "resized ({$w}x{$h} → cap {$nw}x{$nh})" : 're-encoded',
    ];
}

/** Walk /uploads and compress every oversized image. Returns a summary. */
function compress_all_uploads(string $uploads_dir): array {
    if (!is_dir($uploads_dir)) {
        return ['ok' => false, 'error' => 'uploads dir missing', 'processed' => 0, 'saved_bytes' => 0, 'errors' => []];
    }
    $processed  = 0;
    $optimized  = 0;
    $skipped    = 0;
    $saved      = 0;
    $errors     = [];
    foreach (glob($uploads_dir . '/*') as $path) {
        if (!is_file($path)) continue;
        if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)) continue;
        $processed++;
        $r = compress_image($path);
        if (!$r['ok']) {
            $errors[] = $r['error'];
            continue;
        }
        if (($r['reason'] ?? '') === 'already small') {
            $skipped++;
            continue;
        }
        $optimized++;
        $saved += max(0, $r['before'] - $r['after']);
    }
    return [
        'ok' => true,
        'processed'   => $processed,
        'optimized'   => $optimized,
        'skipped'     => $skipped,
        'saved_bytes' => $saved,
        'errors'      => $errors,
    ];
}
