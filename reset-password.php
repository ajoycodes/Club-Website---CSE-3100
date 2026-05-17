<?php
require_once __DIR__ . '/auth/init.php';

if (is_logged_in()) redirect('/dashboard.php');

$db      = getDB();
$token   = trim($_GET['token'] ?? '');
$errors  = [];
$success = false;

// Validate token
$resetRow = null;
if ($token) {
    $stmt = $db->prepare('
        SELECT pr.*, u.email FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > CURRENT_TIMESTAMP
    ');
    $stmt->execute([$token]);
    $resetRow = $stmt->fetch();
}

if (!$token || !$resetRow) {
    $invalid = true;
} else {
    $invalid = false;
}

if (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $password        = $_POST['password']         ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $db->prepare('UPDATE users SET password = ? WHERE id = ?')
               ->execute([$hash, $resetRow['user_id']]);

            $db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
               ->execute([$resetRow['id']]);

            flash('success', 'Password updated successfully. Please sign in.');
            redirect('/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="auth-page">

  <a href="/index.html" class="auth-logo"><span>K</span>mind</a>

  <div class="auth-card">
    <p class="auth-card__eyebrow">Account recovery</p>
    <h1 class="auth-card__title">Set new password</h1>

    <?php if ($invalid): ?>
      <div class="alert alert--error">
        <span>&#9888;</span>
        <div>
          <strong>Invalid or expired link.</strong>
          <p style="margin-top:0.3rem;font-size:0.88rem;">
            This reset link has expired or already been used.
            <a href="/forgot-password.php" style="color:inherit;text-decoration:underline;">Request a new one &rarr;</a>
          </p>
        </div>
      </div>
    <?php else: ?>

      <p class="auth-card__subtitle">Resetting password for <strong><?= e($resetRow['email']) ?></strong></p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
          <span>&#9888;</span>
          <div>
            <?php if (count($errors) === 1): ?>
              <?= e($errors[0]) ?>
            <?php else: ?>
              <ul style="margin-left:1rem;">
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" action="/reset-password.php?token=<?= urlencode($token) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="password" class="form-label">New Password</label>
          <input
            type="password" id="password" name="password" class="form-input"
            placeholder="Min. 8 characters" required autocomplete="new-password"
          />
        </div>

        <div class="form-group">
          <label for="password_confirm" class="form-label">Confirm New Password</label>
          <input
            type="password" id="password_confirm" name="password_confirm" class="form-input"
            placeholder="Repeat password" required autocomplete="new-password"
          />
        </div>

        <button type="submit" class="btn btn--primary btn--full" style="margin-top:0.5rem;">
          Update Password
        </button>
      </form>
    <?php endif; ?>
  </div>

  <p class="auth-footer">
    <a href="/login.php">Back to sign in</a>
  </p>

</body>
</html>
