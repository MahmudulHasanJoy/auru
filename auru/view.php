<?php
/**
 * AURU — view.php
 * Role: Serve document inline for browser viewing (PDF renders natively, images display directly)
 * Security:
 *   - Only owner can view their own document
 *   - Content-Disposition: inline (browser displays instead of downloading)
 *   - File path comes from DB, never from user input directly
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
    error_log("AURU view: missing file " . $filePath);
    http_response_code(404);
    die("File not found.");
}

// Serve inline so browser renders PDFs/images instead of downloading
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filePath);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . auru_escape($doc['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN'); // allow same-origin iframe embedding

readfile($filePath);
exit;