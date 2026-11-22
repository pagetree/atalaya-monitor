<?php

function app_avatar_dir(): string
{
    return dirname(__DIR__) . '/uploads/avatars';
}

function app_avatar_file(string $userId): ?string
{
    if (preg_match('/^[0-9a-f-]{36}$/i', $userId) !== 1) {
        return null;
    }
    $matches = glob(app_avatar_dir() . '/' . $userId . '.*');
    if ($matches === false || $matches === []) {
        return null;
    }
    return $matches[0];
}

function app_avatar_url(string $userId): ?string
{
    $file = app_avatar_file($userId);
    if ($file === null) {
        return null;
    }
    return 'uploads/avatars/' . rawurlencode(basename($file));
}

function app_avatar_remove(string $userId): void
{
    $file = app_avatar_file($userId);
    if ($file !== null && is_file($file)) {
        unlink($file);
    }
}

function app_avatar_store(string $userId, array $file): ?string
{
    if (preg_match('/^[0-9a-f-]{36}$/i', $userId) !== 1) {
        return 'That picture could not be saved.';
    }
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return 'Choose a PNG, JPEG, or GIF.';
    }
    if ($error !== UPLOAD_ERR_OK) {
        return 'That picture could not be uploaded.';
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 10 * 1024 * 1024) {
        return 'The picture must be 10 MB or smaller.';
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $info = @getimagesize($tmp);
    $types = [
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_GIF => 'gif',
    ];
    $type = is_array($info) ? (int) ($info[2] ?? 0) : 0;
    if (!isset($types[$type])) {
        return 'Use a PNG, JPEG, or GIF.';
    }

    $dir = app_avatar_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return 'The picture could not be saved.';
    }

    app_avatar_remove($userId);
    $dest = $dir . '/' . $userId . '.' . $types[$type];
    if (!move_uploaded_file($tmp, $dest)) {
        return 'The picture could not be saved.';
    }
    return null;
}
