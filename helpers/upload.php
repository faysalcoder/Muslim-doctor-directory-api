<?php
declare(strict_types=1);

function ensure_upload_dir(string $dir): void
{
    if (!is_dir($dir)) mkdir($dir, 0775, true);
}

function sanitize_filename(string $filename): string
{
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
    return trim((string)$filename, '_');
}

function upload_single_image(array $file, string $targetDir, array $allowedMime = ['image/jpeg', 'image/png', 'image/webp']): string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . ($file['error'] ?? -1));
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('File too large (max 5MB)');
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Invalid file type');
    }

    ensure_upload_dir($targetDir);

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'bin',
    };

    $original = sanitize_filename(pathinfo($file['name'] ?? 'upload', PATHINFO_FILENAME));
    $filename = $original . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to move uploaded file');
    }

    return $filename;
}

function upload_multiple_images(array $files, string $targetDir): array
{
    $saved = [];
    if (!isset($files['name']) || !is_array($files['name'])) return $saved;

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $single = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $saved[] = upload_single_image($single, $targetDir);
    }
    return $saved;
}
