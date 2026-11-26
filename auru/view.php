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

// Serve inline so browser renders PDFs/images instead of downloading
header('Content-Type: ' . $doc['file_type']);
header('Content-Disposition: inline; filename="' . auru_escape($doc['original_name']) . '"');
header('Content-Length: ' . strlen($doc['file_data']));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN'); // allow same-origin iframe embedding

echo $doc['file_data'];
exit;