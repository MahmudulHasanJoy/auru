<?php
/**
 * AURU — download.php
 * Role: Authenticated, ownership-checked file download
 * Security:
 *   - Only owner can download their own document
 *   - File path is fetched from DB, never from user input directly
 *   - Content-Disposition forces download, prevents inline execution
 *   - No directory traversal possible (path comes from DB)
 */
require 'config.php';
auru_require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    die("Invalid document ID.");
}

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['user_id']]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(403);
    die("Access denied.");
}

$filePath = $doc['file_path'];
if (!is_file($filePath) || !is_readable($filePath)) {
    error_log("AURU download: missing file " . $filePath);
    http_response_code(404);
    die("File not found.");
}

// Security headers before streaming
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . auru_escape($doc['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

readfile($filePath);
exit;