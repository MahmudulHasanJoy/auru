<?php
/**
 * AURU — upload.php
 * Role: Document upload with strict file validation, CSRF, and secure storage
 * Security:
 *   - Extension whitelist (pdf, jpg, jpeg, png)
 *   - MIME type check via finfo
 *   - File size limit (5 MB)
 *   - Stored filename is uniqid + extension (original name never used on disk)
 *   - uploads/ directory blocked by .htaccess from direct URL access
 */
require 'config.php';
auru_require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auru_verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die("Invalid request token.");
    }

    $title    = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $file     = $_FILES['document'] ?? null;

    if (strlen($title) < 1 || strlen($title) > 200) {
        $error = "Title must be 1–200 characters.";
    } elseif (!in_array($category, ['transcript','certificate','nid','driving_license','other'], true)) {
        $error = "Invalid category.";
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = "No file received or upload error.";
    } else {
        // Extension whitelist
        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['pdf','jpg','jpeg','png'];
        if (!in_array($ext, $allowed, true)) {
            $error = "Only PDF, JPG, and PNG files are allowed.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "File too large. Maximum 5 MB.";
        } else {
            // MIME verification using finfo (magic bytes)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'pdf'  => 'application/pdf',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
            ];
            if ($mime !== $allowedMimes[$ext]) {
                $error = "File type does not match extension.";
            } else {
                $stored = uniqid('doc_', true) . '.' . $ext;
                $path   = '/var/www/html/auru/uploads/' . $stored;

                if (move_uploaded_file($file['tmp_name'], $path)) {
                    // Make file unreadable by web server directly (handled by .htaccess too)
                    chmod($path, 0640);

                    $code = 'AURU-' . bin2hex(random_bytes(6));
                    $stmt = $pdo->prepare("INSERT INTO documents
                        (user_id, title, category, original_name, stored_name, file_path, file_type, file_size, verification_code)
                        VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $title,
                        $category,
                        auru_escape($origName),
                        $stored,
                        $path,
                        $mime,
                        (int)$file['size'],
                        $code
                    ]);

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: vault.php?uploaded=1');
                    exit;
                } else {
                    error_log("AURU upload: move_uploaded_file failed for " . auru_escape($origName));
                    $error = "Upload failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AURU — Upload Document</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="vault.php">Back to Vault</a> |
    <a href="logout.php">Log out</a>
</nav>
<div class="card">
    <h2>Upload Document</h2>
    <?php if ($error): ?><p class="error"><?= auru_escape($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" novalidate>
        <?= auru_csrf_field() ?>
        <input type="text" name="title" placeholder="Document Title" maxlength="200" required>
        <select name="category" required>
            <option value="">Select Category</option>
            <option value="transcript">Transcript</option>
            <option value="certificate">Certificate</option>
            <option value="nid">National ID (NID)</option>
            <option value="driving_license">Driving License</option>
            <option value="other">Other</option>
        </select>
        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
        <small>Allowed: PDF, JPG, PNG &bull; Max 5 MB</small>
        <button type="submit">Upload to Vault</button>
    </form>
</div>
</body>
</html>
