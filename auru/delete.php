<?php
/**
 * AURU — delete.php
 * Role: Owner-only document deletion with CSRF and POST-only enforcement
 */
require 'config.php';
auru_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

if (!auru_verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    die("Invalid request token.");
}

$id = filter_input(INPUT_POST, 'doc_id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    die("Invalid document ID.");
}

$stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['user_id']]);
$doc = $stmt->fetch();

if ($doc) {
    // Delete file from disk if it exists
    if (is_file($doc['file_path'])) {
        @unlink($doc['file_path']);
    }
    $pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$id]);
    error_log("AURU document deleted: id=$id user_id=" . $_SESSION['user_id']);
}

header('Location: vault.php');
exit;