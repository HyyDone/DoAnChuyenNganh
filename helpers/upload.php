<?php
function safe_filename($name)
{
    $name = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $name);
    return $name;
}
function upload_avatar($file, $destDir = __DIR__ . '/../public/uploads/avatars/')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'No file or upload error'];
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['error' => 'Invalid image file'];
    }
    
    $mime = $imageInfo['mime'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    
    if (!array_key_exists($mime, $allowed)) return ['error' => 'Unsupported file type'];
    if ($file['size'] > 2 * 1024 * 1024) return ['error' => 'File too large (max 2MB)'];
    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(8)) . "." . $ext;
    $target = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) return ['error' => 'Move failed'];
    return ['path' => 'uploads/avatars/' . $filename];
}
