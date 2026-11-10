<?php
/**
 * AURU — admin_verify.php
 * Role: Admin queue to approve or reject pending documents
 * Security:
 *   - Role-restricted (admin only)
 *   - CSRF on approve/reject actions
 *   - POST-only for state changes
 */
require 'config.php';
auru_require_role('admin');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auru_verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die("Invalid request token.");
    }

    $docId  = filter_input(INPUT_POST, 'doc_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    $newStatus = ($action === 'verify') ? 'verified' : 'rejected';

    if ($docId && in_array($action, ['verify','reject'], true)) {
        $stmt = $pdo->prepare("UPDATE documents SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->execute([$newStatus, $_SESSION['user_id'], $docId]);
        $msg = "Document marked as " . auru_escape($newStatus) . ".";
        error_log("AURU admin action: doc_id=$docId status=$newStatus admin_id=" . $_SESSION['user_id']);
    } else {
        $msg = "Invalid action.";
    }
}

$stmt = $pdo->prepare("
    SELECT d.*, u.full_name, u.student_id
    FROM documents d
    JOIN users u ON d.user_id = u.id
    WHERE d.status = 'pending'
    ORDER BY d.uploaded_at DESC
");
$stmt->execute();
$pending = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AURU — Admin Verification Queue</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="vault.php">Vault</a> |
    <a href="logout.php">Log out</a>
    <span>Admin: <?= auru_escape($_SESSION['full_name']) ?></span>
</nav>
<div class="card">
    <h2>Admin — Document Verification Queue</h2>
    <?php if ($msg): ?><p class="success"><?= auru_escape($msg) ?></p><?php endif; ?>

    <?php if (empty($pending)): ?>
        <p>No pending documents.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Student</th>
                <th>Document</th>
                <th>Category</th>
                <th>Code</th>
                <th>Action</th>
            </tr>
            <?php foreach ($pending as $d): ?>
            <tr>
                <td><?= auru_escape($d['full_name']) ?></td>
                <td><?= auru_escape($d['title']) ?></td>
                <td><?= auru_escape($d['category']) ?></td>
                <td><code><?= auru_escape($d['verification_code']) ?></code></td>
                <td>
                    <form method="post" style="display:inline" onsubmit="return confirm('Are you sure?')">
                        <?= auru_csrf_field() ?>
                        <input type="hidden" name="doc_id" value="<?= (int)$d['id'] ?>">
                        <button type="submit" name="action" value="verify" class="btn-verify">Verify</button>
                        <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
