<?php
/**
 * AURU — vault.php
 * Role: Student dashboard — list own documents with status and secure download links
 */
require 'config.php';
auru_require_login();

$stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$docs = $stmt->fetchAll();

$upload_msg = isset($_GET['uploaded']) ? "Document uploaded successfully." : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AURU — My Vault</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="vault.php">Vault</a> |
    <a href="upload.php">Upload</a> |
    <a href="verify.php">Verify Document</a> |
    <a href="logout.php">Log out</a>
    <span><?= auru_escape($_SESSION['full_name']) ?> &bull; <?= ucfirst(auru_escape($_SESSION['role'])) ?></span>
</nav>
<div class="card">
    <h2>My Vault</h2>
    <?php if ($upload_msg): ?><p class="success"><?= auru_escape($upload_msg) ?></p><?php endif; ?>
    <a class="btn" href="upload.php">+ Upload New Document</a>
    <?php if (empty($docs)): ?>
        <p>No documents uploaded yet.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Verification Code</th>
                <th>Download</th>
                <th>Delete</th>
            </tr>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td><?= auru_escape($d['title']) ?></td>
                <td><?= auru_escape($d['category']) ?></td>
                <td><?= auru_escape($d['status']) ?></td>
                <td><code><?= auru_escape($d['verification_code']) ?></code></td>
                <td><a class="btn" href="download.php?id=<?= (int)$d['id'] ?>">Download</a></td>
                <td>
                    <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this document permanently?')">
                        <?= auru_csrf_field() ?>
                        <input type="hidden" name="doc_id" value="<?= (int)$d['id'] ?>">
                        <button type="submit" class="btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
