<?php
require_once __DIR__ . '/auth/init.php';

// Already logged in → go to dashboard
if (is_logged_in()) {
    redirect('/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Incorrect email or password.';
            } elseif ($user['status'] === 'rejected') {
                $error = 'Your application was not approved. Contact us for more info.';
            } elseif ($user['status'] === 'pending') {
                // Log them in but they'll see the pending screen in dashboard
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                redirect('/dashboard.php');
            } else {
                // Approved
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                redirect('/dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="auth-page">

  <a href="/index.html" class="auth-logo"><span>K</span>mind</a>

  <div class="auth-card">
    <p class="auth-card__eyebrow">Welcome back</p>
    <h1 class="auth-card__title">Sign in</h1>
    <p class="auth-card__subtitle">Log in to access the Kmind member dashboard.</p>

    <?php if ($error): ?>
      <div class="alert alert--error">
        <span>&#9888;</span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <?php $flash = get_flash('success'); if ($flash): ?>
      <div class="alert alert--success">
        <span>&#10003;</span>
        <span><?= e($flash) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login.php" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-input"
          placeholder="arif@university.edu"
          value="<?= e($_POST['email'] ?? '') ?>"
          required
          autocomplete="email"
        />
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-input"
          placeholder="••••••••"
          required
          autocomplete="current-password"
        />
      </div>

      <button type="submit" class="btn btn--primary btn--full" style="margin-top: 0.5rem;">
        Sign In
      </button>

      <p style="text-align:center;margin-top:var(--space-4);font-size:0.85rem;">
        <a href="/forgot-password.php" style="color:var(--clr-text-muted);">Forgot your password?</a>
      </p>
    </form>
  </div>

  <p class="auth-footer">
    Don&rsquo;t have an account?
    <a href="/signup.php">Apply to join</a>
  </p>

</body>
</html>
