<?php
require_once __DIR__ . '/auth/init.php';

if (is_logged_in()) {
    redirect('/dashboard.php');
}

$errors  = [];
$success = false;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $old = [
            'first_name'  => trim($_POST['first_name']  ?? ''),
            'last_name'   => trim($_POST['last_name']   ?? ''),
            'email'       => trim($_POST['email']       ?? ''),
            'department'  => trim($_POST['department']  ?? ''),
            'background'  => $_POST['background'] ?? '',
            'interest'    => $_POST['interest']   ?? '',
        ];
        $password        = $_POST['password']         ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        if (!$old['first_name']) $errors[] = 'First name is required.';
        if (!$old['last_name'])  $errors[] = 'Last name is required.';

        if (!$old['email']) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$old['background']) $errors[] = 'Please select your experience level.';

        if (empty($errors)) {
            $db = getDB();

            // Check email uniqueness
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with that email already exists.';
            }
        }

        if (empty($errors)) {
            $db = getDB();

            // First ever user → admin + approved
            $count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $role   = $count === 0 ? 'admin'    : 'member';
            $status = $count === 0 ? 'approved' : 'pending';

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare('
                INSERT INTO users
                    (first_name, last_name, email, password, department, background, interest, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $old['first_name'],
                $old['last_name'],
                $old['email'],
                $hash,
                $old['department'] ?: null,
                $old['background'] ?: null,
                $old['interest']   ?: null,
                $role,
                $status,
            ]);

            $success = true;
            $isAdmin = ($role === 'admin');
            $old = []; // clear form
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="auth-page" style="justify-content: flex-start; padding-top: 3rem; padding-bottom: 3rem;">

  <a href="/index.html" class="auth-logo"><span>K</span>mind</a>

  <div class="auth-card" style="max-width: 520px;">
    <p class="auth-card__eyebrow">Join the club</p>
    <h1 class="auth-card__title">Create account</h1>
    <p class="auth-card__subtitle">
      Apply to become a Kmind member. Your application will be reviewed by an admin.
    </p>

    <?php if ($success): ?>
      <div class="alert alert--success">
        <span>&#10003;</span>
        <div>
          <?php if ($isAdmin ?? false): ?>
            <strong>Welcome, Admin!</strong> Your account is ready.
            <a href="/login.php" style="color:inherit;text-decoration:underline;">Sign in now &rarr;</a>
          <?php else: ?>
            <strong>Application submitted!</strong> Your application is under review.
            We&rsquo;ll get back to you within 5&ndash;7 days.
            <a href="/login.php" style="color:inherit;text-decoration:underline;">Sign in &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

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

    <?php if (!$success): ?>
    <form method="POST" action="/signup.php" novalidate>
      <?= csrf_field() ?>

      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label for="first_name" class="form-label">First Name</label>
          <input
            type="text" id="first_name" name="first_name" class="form-input"
            placeholder="Arif" value="<?= e($old['first_name'] ?? '') ?>"
            required autocomplete="given-name"
          />
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label for="last_name" class="form-label">Last Name</label>
          <input
            type="text" id="last_name" name="last_name" class="form-input"
            placeholder="Rahman" value="<?= e($old['last_name'] ?? '') ?>"
            required autocomplete="family-name"
          />
        </div>
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input
          type="email" id="email" name="email" class="form-input"
          placeholder="arif@university.edu" value="<?= e($old['email'] ?? '') ?>"
          required autocomplete="email"
        />
      </div>

      <div class="form-group">
        <label for="department" class="form-label">
          Department <span class="form-label--optional">(optional)</span>
        </label>
        <input
          type="text" id="department" name="department" class="form-input"
          placeholder="e.g. CSE, EEE, Mathematics"
          value="<?= e($old['department'] ?? '') ?>"
        />
      </div>

      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label for="background" class="form-label">Experience Level</label>
          <select id="background" name="background" class="form-input form-select" required>
            <option value="" disabled <?= empty($old['background']) ? 'selected' : '' ?>>Select level</option>
            <option value="beginner"     <?= ($old['background'] ?? '') === 'beginner'     ? 'selected' : '' ?>>Beginner</option>
            <option value="intermediate" <?= ($old['background'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            <option value="advanced"     <?= ($old['background'] ?? '') === 'advanced'     ? 'selected' : '' ?>>Advanced</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label for="interest" class="form-label">
            Area of Interest <span class="form-label--optional">(optional)</span>
          </label>
          <select id="interest" name="interest" class="form-input form-select">
            <option value="" <?= empty($old['interest']) ? 'selected' : '' ?>>Select area</option>
            <option value="cv"       <?= ($old['interest'] ?? '') === 'cv'       ? 'selected' : '' ?>>Computer Vision</option>
            <option value="nlp"      <?= ($old['interest'] ?? '') === 'nlp'      ? 'selected' : '' ?>>NLP</option>
            <option value="rl"       <?= ($old['interest'] ?? '') === 'rl'       ? 'selected' : '' ?>>Reinforcement Learning</option>
            <option value="ds"       <?= ($old['interest'] ?? '') === 'ds'       ? 'selected' : '' ?>>Data Science</option>
            <option value="mlops"    <?= ($old['interest'] ?? '') === 'mlops'    ? 'selected' : '' ?>>MLOps</option>
            <option value="research" <?= ($old['interest'] ?? '') === 'research' ? 'selected' : '' ?>>AI Research</option>
          </select>
        </div>
      </div>

      <hr class="form-divider" />

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input
          type="password" id="password" name="password" class="form-input"
          placeholder="Min. 8 characters" required autocomplete="new-password"
        />
      </div>

      <div class="form-group">
        <label for="password_confirm" class="form-label">Confirm Password</label>
        <input
          type="password" id="password_confirm" name="password_confirm" class="form-input"
          placeholder="Repeat password" required autocomplete="new-password"
        />
      </div>

      <button type="submit" class="btn btn--primary btn--full" style="margin-top: 0.5rem;">
        Submit Application
      </button>
    </form>
    <?php endif; ?>
  </div>

  <p class="auth-footer">
    Already have an account?
    <a href="/login.php">Sign in</a>
  </p>

</body>
</html>
