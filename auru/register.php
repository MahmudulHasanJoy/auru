<?php
/**
 * AURU — register.php
 * Role: Student self-registration with password hashing and CSRF protection
 */
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!auru_verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die("Invalid request token.");
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password  = $_POST['password'] ?? '';
    $student_id= trim($_POST['student_id'] ?? '');

    // Server-side validation
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $error = "Full name must be 2–100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one uppercase letter and one number.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, student_id, role) VALUES (?,?,?,?,'student')");
        try {
            $stmt->execute([$full_name, $email, $hash, $student_id ?: null]);
            $success = "Account created. You can now log in.";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate CSRF after state change
        } catch (\PDOException $e) {
            error_log("AURU registration error: " . $e->getMessage());
            $error = "Email already registered or database error.";
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
    <title>AURU — Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="card">
    <h2>AURU — Register</h2>
    <?php if ($error): ?><p class="error"><?= auru_escape($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= auru_escape($success) ?></p><?php endif; ?>
    <form method="post" novalidate>
        <?= auru_csrf_field() ?>
        <input type="text" name="full_name" placeholder="Full Name" value="<?= auru_escape($_POST['full_name'] ?? '') ?>" required minlength="2" maxlength="100">
        <input type="email" name="email" placeholder="Email" value="<?= auru_escape($_POST['email'] ?? '') ?>" required>
        <input type="text" name="student_id" placeholder="Student ID (optional)" value="<?= auru_escape($_POST['student_id'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password (min 8 chars, 1 uppercase, 1 number)" required minlength="8">
        <button type="submit">Create Account</button>
    </form>
    <p>Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
