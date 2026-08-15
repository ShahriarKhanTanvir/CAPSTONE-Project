<?php
/**
 * upload.php
 * NFR17: Secure file upload validation
 *
 * Route:
 *   POST /api/utils/upload.php — Securely upload product/profile images
 */

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_check.php';

requireAuth(['admin', 'manager', 'barista', 'cashier']);
requireMethod('POST');

if (empty($_FILES['image'])) {
    sendResponse(false, 'No file uploaded. Please provide an image file with key "image".', null, 422);
}

$file = $_FILES['image'];

// 1. Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, 'File upload error code: ' . $file['error'], null, 400);
}

// 2. Strict file size check (Max 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxFileSize) {
    sendResponse(false, 'File size exceeds maximum permitted limit of 5MB.', null, 422);
}

// 3. MIME-Type and Extension Whitelist
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!array_key_exists($mimeType, $allowedMimes)) {
    sendResponse(false, 'Invalid file type. Only JPEG, PNG, WEBP, and GIF images are permitted.', [
        'detected_mime' => $mimeType
    ], 422);
}

// 4. Verify genuine image contents
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    sendResponse(false, 'File is corrupt or is not a valid image.', null, 422);
}

// 5. Generate safe, randomized SHA-256 filename (prevents directory traversal and overwrites)
$extension = $allowedMimes[$mimeType];
$safeFilename = 'img_' . hash_file('sha256', $file['tmp_name']) . '.' . $extension;

$uploadDir = __DIR__ . '/../../brand_recources/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $safeFilename;
$publicUrl = 'brand_recources/uploads/' . $safeFilename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendResponse(false, 'Failed to save uploaded file to destination directory.', null, 500);
}

sendResponse(true, 'File uploaded and validated securely.', [
    'filename'     => $safeFilename,
    'mime_type'    => $mimeType,
    'size_bytes'   => $file['size'],
    'image_width'  => $imageInfo[0],
    'image_height' => $imageInfo[1],
    'relative_url' => $publicUrl
], 201);
