<?php
require_once __DIR__ . '/auth/init.php';
require_login();

$db   = getDB();
$user = current_user();

$profileErrors  = [];
$passwordErrors = [];
$profileSuccess = false;
$passwordSuccess = false;

// ── Handle profile update ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $profileErrors[] = 'Invalid request. Please try again.';
    } else {
        $firstName  = trim($_POST['first_name']  ?? '');
        $lastName   = trim($_POST['last_name']   ?? '');
        $department = trim($_POST['department']  ?? '');
        $background = $_POST['background'] ?? '';
        $interest   = $_POST['interest']   ?? '';

        $allowedBg       = ['beginner','intermediate','advanced'];
        $allowedInterest = ['cv','nlp','rl','ds','mlops','research'];

        if (!$firstName) $profileErrors[] = 'First name is required.';
        if (!$lastName)  $profileErrors[] = 'Last name is required.';
        if ($background && !in_array($background, $allowedBg))
            $profileErrors[] = 'Invalid experience level.';
        if ($interest && !in_array($interest, $allowedInterest))
            $profileErrors[] = 'Invalid area of interest.';

        if (empty($profileErrors)) {
            $stmt = $db->prepare('
                UPDATE users SET first_name=?, last_name=?, department=?, background=?, interest=?
                WHERE id=?
            ');
            $stmt->execute([
                $firstName,
                $lastName,
                $department ?: null,
                $background ?: null,
                $interest   ?: null,
                $user['id'],
            ]);
            $profileSuccess = true;
            // Reload user from DB
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        }
    }
}

// ── Handle password change ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $passwordErrors[] = 'Invalid request. Please try again.';
    } else {
        $current  = $_POST['current_password']  ?? '';
        $new      = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (!$current) {
            $passwordErrors[] = 'Current password is required.';
        } elseif (!password_verify($current, $user['password'])) {
            $passwordErrors[] = 'Current password is incorrect.';
        }

        if (strlen($new) < 8) {
            $passwordErrors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $passwordErrors[] = 'New passwords do not match.';
        }

        if (empty($passwordErrors)) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')
               ->execute([$hash, $user['id']]);
            $passwordSuccess = true;
        }
    }
}

$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$isAdmin  = $user['role'] === 'admin';

$interestLabels = [
    'cv' => 'Computer Vision', 'nlp' => 'NLP', 'rl' => 'Reinforcement Learning',
    'ds' => 'Data Science', 'mlops' => 'MLOps', 'research' => 'AI Research',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="dash-page">

  <!-- ── Top bar ── -->
  <header class="dash-topbar">
    <a href="/index.html" class="dash-topbar__logo"><span>K</span>mind</a>

    <nav class="dash-topbar__nav">
      <a href="/dashboard.php">Dashboard</a>
      <?php if ($isAdmin): ?>
        <a href="/admin.php">Members</a>
        <a href="/admin-events.php">Events</a>
      <?php endif; ?>
      <a href="/index.html">Main Site</a>
    </nav>

    <div class="dash-topbar__right">
      <div class="dash-topbar__user">
        <div class="dash-topbar__avatar"><?= e($initials) ?></div>
        <span class="dash-topbar__name"><?= e($user['first_name']) ?></span>
      </div>
      <a href="/logout.php" class="btn btn--ghost" style="padding:0.45rem 1rem;font-size:0.82rem;">
        Sign out
      </a>
    </div>
  </header>

  <!-- ── Body ── -->
  <div class="dash-body" style="grid-template-columns: 1fr 320px;">

    <!-- ── Main column ── -->
    <main class="dash-main">

      <div class="dash-welcome">
        <p class="dash-welcome__eyebrow">Settings</p>
        <h1 class="dash-welcome__title">Edit Profile</h1>
      </div>

      <!-- Profile info form -->
      <h2 class="dash-section-title">Personal Info</h2>

      <?php if ($profileSuccess): ?>
        <div class="alert alert--success" style="margin-bottom:var(--space-6);">
          <span>&#10003;</span>
          <span>Profile updated successfully.</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($profileErrors)): ?>
        <div class="alert alert--error" style="margin-bottom:var(--space-6);">
          <span>&#9888;</span>
          <div>
            <?php if (count($profileErrors) === 1): ?>
              <?= e($profileErrors[0]) ?>
            <?php else: ?>
              <ul style="margin-left:1rem;">
                <?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="dash-card" style="max-width:600px;">
        <form method="POST" action="/profile.php" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="update_profile" value="1">

          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label for="first_name" class="form-label">First Name</label>
              <input type="text" id="first_name" name="first_name" class="form-input"
                     value="<?= e($user['first_name']) ?>" required />
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label for="last_name" class="form-label">Last Name</label>
              <input type="text" id="last_name" name="last_name" class="form-input"
                     value="<?= e($user['last_name']) ?>" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-input"
                   value="<?= e($user['email']) ?>" disabled
                   style="opacity:0.5;cursor:not-allowed;" />
            <p style="font-size:0.78rem;color:var(--clr-text-faint);margin-top:4px;">
              Email cannot be changed. Contact an admin if needed.
            </p>
          </div>

          <div class="form-group">
            <label for="department" class="form-label">
              Department <span class="form-label--optional">(optional)</span>
            </label>
            <input type="text" id="department" name="department" class="form-input"
                   placeholder="e.g. CSE, EEE, Mathematics"
                   value="<?= e($user['department'] ?? '') ?>" />
          </div>

          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label for="background" class="form-label">Experience Level</label>
              <select id="background" name="background" class="form-input form-select">
                <option value="">Select level</option>
                <?php foreach (['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $v => $l): ?>
                  <option value="<?= $v ?>" <?= ($user['background'] ?? '') === $v ? 'selected' : '' ?>>
                    <?= $l ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label for="interest" class="form-label">
                Area of Interest <span class="form-label--optional">(optional)</span>
              </label>
              <select id="interest" name="interest" class="form-input form-select">
                <option value="">Select area</option>
                <?php foreach ($interestLabels as $v => $l): ?>
                  <option value="<?= $v ?>" <?= ($user['interest'] ?? '') === $v ? 'selected' : '' ?>>
                    <?= $l ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" class="btn btn--primary" style="margin-top:var(--space-4);">
            Save Changes
          </button>
        </form>
      </div>

      <!-- Password change form -->
      <h2 class="dash-section-title" style="margin-top:var(--space-10);">Change Password</h2>

      <?php if ($passwordSuccess): ?>
        <div class="alert alert--success" style="margin-bottom:var(--space-6);">
          <span>&#10003;</span>
          <span>Password updated successfully.</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($passwordErrors)): ?>
        <div class="alert alert--error" style="margin-bottom:var(--space-6);">
          <span>&#9888;</span>
          <div>
            <?php if (count($passwordErrors) === 1): ?>
              <?= e($passwordErrors[0]) ?>
            <?php else: ?>
              <ul style="margin-left:1rem;">
                <?php foreach ($passwordErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="dash-card" style="max-width:600px;">
        <form method="POST" action="/profile.php" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="update_password" value="1">

          <div class="form-group">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" id="current_password" name="current_password"
                   class="form-input" placeholder="••••••••"
                   required autocomplete="current-password" />
          </div>

          <div class="form-group">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" id="new_password" name="new_password"
                   class="form-input" placeholder="Min. 8 characters"
                   required autocomplete="new-password" />
          </div>

          <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   class="form-input" placeholder="Repeat new password"
                   required autocomplete="new-password" />
          </div>

          <button type="submit" class="btn btn--primary" style="margin-top:var(--space-4);">
            Update Password
          </button>
        </form>
      </div>

    </main>

    <!-- ── Sidebar ── -->
    <aside class="dash-sidebar">
      <div class="dash-card">
        <p class="dash-card__title">Your Account</p>
        <div class="dash-profile__avatar"><?= e($initials) ?></div>
        <p class="dash-profile__name"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
        <p class="dash-profile__email"><?= e($user['email']) ?></p>

        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Status</span>
          <span class="dash-profile__row-val">
            <span class="badge badge--<?= e($user['status']) ?>"><?= e(ucfirst($user['status'])) ?></span>
          </span>
        </div>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Role</span>
          <span class="dash-profile__row-val">
            <span class="badge badge--<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
          </span>
        </div>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Joined</span>
          <span class="dash-profile__row-val"><?= e(date('M j, Y', strtotime($user['created_at']))) ?></span>
        </div>

        <a href="/dashboard.php" class="btn btn--ghost btn--full"
           style="margin-top:var(--space-4);font-size:0.82rem;padding:0.45rem 1rem;">
          ← Back to Dashboard
        </a>
      </div>
    </aside>

  </div>

</body>
</html>
