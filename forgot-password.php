<?php
require_once __DIR__ . '/auth/init.php';

if (is_logged_in()) redirect('/dashboard.php');

$error   = '';
$token   = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Invalidate old tokens for this user
                $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

                $token     = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $token, $expiresAt]);
            }
            // Always show success (don't leak whether email exists)
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="auth-page">

  <a href="/index.html" class="auth-logo"><span>K</span>mind</a>

  <div class="auth-card">
    <p class="auth-card__eyebrow">Account recovery</p>
    <h1 class="auth-card__title">Forgot password?</h1>
    <p class="auth-card__subtitle">Enter your email and we&rsquo;ll give you a reset link.</p>

    <?php if ($error): ?>
      <div class="alert alert--error">
        <span>&#9888;</span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert--success">
        <span>&#10003;</span>
        <div>
          <?php if ($token): ?>
            <strong>Reset link generated.</strong>
            <?php /* In production, this would be emailed. Showing directly for now. */ ?>
            <p style="margin-top:0.5rem;font-size:0.88rem;line-height:1.6;">
              Copy this link to reset your password
              (in production this would be sent to your email):
            </p>
            <div style="
              background:rgba(255,255,255,0.05);
              border:1px solid rgba(255,255,255,0.1);
              border-radius:6px;
              padding:0.6rem 0.8rem;
              font-size:0.78rem;
              word-break:break-all;
              margin-top:0.4rem;
            ">
              <a href="/reset-password.php?token=<?= urlencode($token) ?>"
                 style="color:var(--clr-accent);">
                /reset-password.php?token=<?= e($token) ?>
              </a>
            </div>
          <?php else: ?>
            If that email is registered, a reset link has been generated.
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
    <form method="POST" action="/forgot-password.php" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input
          type="email" id="email" name="email" class="form-input"
          placeholder="arif@university.edu"
          value="<?= e($_POST['email'] ?? '') ?>"
          required autocomplete="email"
        />
      </div>

      <button type="submit" class="btn btn--primary btn--full" style="margin-top:0.5rem;">
        Send Reset Link
      </button>
    </form>
    <?php endif; ?>
  </div>

  <p class="auth-footer">
    Remembered it?
    <a href="/login.php">Back to sign in</a>
  </p>

</body>
</html>
