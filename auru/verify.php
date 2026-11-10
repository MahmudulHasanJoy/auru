<?php
/**
 * AURU — verify.php
 * Role: Public officer verification portal
 * Security:
 *   - No authentication required to look up a verification code (by design)
 *   - Only document metadata exposed, never the file itself
 *   - Every lookup logged in verification_logs with IP and timestamp
 *   - CSRF not required (public read-only lookup), but input is parameterized
 */
require 'config.php';

$doc = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['verification_code'] ?? '');
    if (preg_match('/^AURU-[0-9a-f]{12}$/', $code)) {
        $stmt = $pdo->prepare("
            SELECT d.*, u.full_name, u.student_id
            FROM documents d
            JOIN users u ON d.user_id = u.id
            WHERE d.verification_code = ?
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $doc = $stmt->fetch();

        // Log verification attempt (officer_id NULL for public lookups)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $ins = $pdo->prepare("
            INSERT INTO verification_logs (verification_code, officer_id, ip_address, user_agent)
            VALUES (?, NULL, ?, ?)
        ");
        $ins->execute([$code, $ip, $ua]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AURU — Officer Verification</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="vault.php">Vault</a> |
    <a href="login.php">Officer / Admin Login</a>
</nav>
<div class="card">
    <h2>AURU — Officer Document Verification</h2>
    <p class="hint">Enter the verification code printed on the student's document to confirm authenticity.</p>
    <form method="post" novalidate>
        <input type="text" name="verification_code" placeholder="e.g. AURU-7f2a9b1c" pattern="AURU-[0-9a-f]{12}" maxlength="18" required autocomplete="off">
        <button type="submit">Verify Document</button>
    </form>

    <?php if ($doc): ?>
    <hr>
    <h3>Verification Result</h3>
    <table class="result-table">
        <tr><th>Student</th><td><?= auru_escape($doc['full_name']) ?> (<?= auru_escape($doc['student_id'] ?? 'N/A') ?>)</td></tr>
        <tr><th>Document</th><td><?= auru_escape($doc['title']) ?></td></tr>
        <tr><th>Category</th><td><?= auru_escape($doc['category']) ?></td></tr>
        <tr><th>Verification Code</th><td><code><?= auru_escape($doc['verification_code']) ?></code></td></tr>
        <tr><th>Status</th>
            <td>
                <?php if ($doc['status'] === 'verified'): ?>
                    <span class="badge verified">✅ Verified &amp; Authentic</span>
                <?php elseif ($doc['status'] === 'rejected'): ?>
                    <span class="badge rejected">❌ Rejected</span>
                <?php else: ?>
                    <span class="badge pending">⏳ Pending</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>Uploaded</th><td><?= auru_escape($doc['uploaded_at']) ?></td></tr>
    </table>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p class="error">No document found with that verification code.</p>
    <?php endif; ?>
</div>
</body>
</html>
