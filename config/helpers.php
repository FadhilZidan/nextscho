<?php
require_once __DIR__ . '/constants.php';

/**
 * Upload foto profil siswa/guru.
 * Mengembalikan ['success'=>bool, 'filename'=>string, 'error'=>string].
 */
function handlePhotoUpload(array $file, string $oldPhoto = 'default.png', string $prefix = 'photo_'): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $oldPhoto];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => $oldPhoto, 'error' => 'Upload gagal (kode ' . $file['error'] . ')'];
    }

    if ($file['size'] > MAX_UPLOAD) {
        return ['success' => false, 'filename' => $oldPhoto, 'error' => 'Ukuran file maksimal 2 MB'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        return ['success' => false, 'filename' => $oldPhoto, 'error' => 'Format harus JPG, PNG, GIF, atau WebP'];
    }

    $ext      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
    $filename = $prefix . uniqid('', true) . '.' . $ext;
    $dest     = UPLOAD_PATH . $filename;

    if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'filename' => $oldPhoto, 'error' => 'Gagal menyimpan file ke server'];
    }

    // Hapus foto lama
    if ($oldPhoto !== 'default.png' && file_exists(UPLOAD_PATH . $oldPhoto)) {
        @unlink(UPLOAD_PATH . $oldPhoto);
    }

    return ['success' => true, 'filename' => $filename];
}

/** Escape HTML singkat */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
