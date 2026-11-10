<?php
/**
 * AURU — login.php
 * Role: Role-based login with CSRF, generic failure messages, session regeneration
 */
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auru_verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die("Invalid request token.");
    }

    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Generic failure message regardless of whether email exists (prevents user enumeration)
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['full_name']= $user['full_name'];
        $_SESSION['email']    = $user['email'];
        auru_regenerate_session();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($user['role'] === 'admin') {
            header('Location: admin_verify.php');
        } else {
            header('Location: vault.php');
        }
        exit;
    } else {
        error_log("AURU failed login attempt for: " . auru_escape($email));
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AURU — Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="card">
    <h2>AURU — Login</h2>
    <?php if ($error): ?><p class="error"><?= auru_escape($error) ?></p><?php endif; ?>
    <form method="post" novalidate>
        <?= auru_csrf_field() ?>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Log in</button>
    </form>
    <p>New user? <a href="register.php">Register</a></p>
</div>
</body>
</html>
