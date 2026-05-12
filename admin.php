<?php
require_once __DIR__ . '/auth/init.php';
require_admin();

$user = current_user();
$db   = getDB();

// ── Handle approve / reject ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        // ignore
    } else {
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId && in_array($action, ['approve', 'reject'], true)) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ? AND id != ?');
            $stmt->execute([$newStatus, $targetId, $user['id']]); // can't change own status
        }
    }
    header('Location: /admin.php');
    exit;
}

// ── Fetch users by status ─────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'])) {
    $filter = 'pending';
}

if ($filter === 'all') {
    $stmt = $db->query('SELECT * FROM users ORDER BY created_at DESC');
} else {
    $stmt = $db->prepare('SELECT * FROM users WHERE status = ? ORDER BY created_at DESC');
    $stmt->execute([$filter]);
}
$users = $stmt->fetchAll();

// ── Counts for tab badges ─────────────────────────────────────────────────────
$counts = [];
foreach (['pending', 'approved', 'rejected', 'all'] as $s) {
    if ($s === 'all') {
        $counts[$s] = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE status = ?');
        $stmt->execute([$s]);
        $counts[$s] = (int) $stmt->fetchColumn();
    }
}

$interestLabels = [
    'cv' => 'Computer Vision', 'nlp' => 'NLP', 'rl' => 'Reinforcement Learning',
    'ds' => 'Data Science', 'mlops' => 'MLOps', 'research' => 'AI Research',
];
$bgLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="admin-page">

  <!-- Top bar (reuse dash-topbar) -->
  <header class="dash-topbar">
    <a href="/index.html" class="dash-topbar__logo"><span>K</span>mind</a>
    <nav class="dash-topbar__nav">
      <a href="/dashboard.php">Dashboard</a>
      <a href="/admin.php" class="active">Admin Panel</a>
      <a href="/index.html">Main Site</a>
    </nav>
    <div class="dash-topbar__right">
      <div class="dash-topbar__user">
        <?php $initials = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)); ?>
        <div class="dash-topbar__avatar"><?= e($initials) ?></div>
        <span class="dash-topbar__name"><?= e($user['first_name']) ?></span>
      </div>
      <a href="/logout.php" class="btn btn--ghost" style="padding:0.45rem 1rem;font-size:0.82rem;">Sign out</a>
    </div>
  </header>

  <div class="admin-body">

    <div class="admin-header">
      <div>
        <p class="admin-header__eyebrow">Admin Panel</p>
        <h1 class="admin-header__title">Member Management</h1>
      </div>
      <a href="/dashboard.php" class="btn btn--ghost">&larr; Back to Dashboard</a>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
      <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
        <a
          href="/admin.php?filter=<?= $key ?>"
          class="admin-tab <?= $filter === $key ? 'active' : '' ?>"
        >
          <?= $label ?>
          <?php if ($counts[$key] > 0): ?>
            <span style="
              background: <?= $key === 'pending' ? 'rgba(245,158,11,0.2)' : 'rgba(255,255,255,0.08)' ?>;
              color: <?= $key === 'pending' ? 'var(--clr-warning)' : 'var(--clr-text-muted)' ?>;
              border-radius: var(--radius-full);
              padding: 0 7px;
              font-size: 0.72rem;
              margin-left: 4px;
            "><?= $counts[$key] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="admin-table-wrap">
      <?php if (empty($users)): ?>
        <div class="admin-empty">
          <p style="font-size:1.5rem;margin-bottom:0.5rem;">&#10003;</p>
          <p>No <?= $filter === 'all' ? '' : $filter ?> members to show.</p>
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Department / Background</th>
              <th>Interest</th>
              <th>Applied</th>
              <th>Status</th>
              <?php if (in_array($filter, ['pending', 'all'])): ?>
                <th>Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <p class="admin-table__name"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></p>
                <p class="admin-table__email"><?= e($u['email']) ?></p>
              </td>
              <td>
                <p><?= $u['department'] ? e($u['department']) : '<span style="color:var(--clr-text-faint)">—</span>' ?></p>
                <p style="font-size:0.8rem;color:var(--clr-text-muted);">
                  <?= e($bgLabels[$u['background']] ?? ucfirst($u['background'] ?? '—')) ?>
                </p>
              </td>
              <td><?= e($u['interest'] ? ($interestLabels[$u['interest']] ?? ucfirst($u['interest'])) : '—') ?></td>
              <td style="color:var(--clr-text-muted);font-size:0.82rem;">
                <?= e(date('M j, Y', strtotime($u['created_at']))) ?>
              </td>
              <td>
                <span class="badge badge--<?= e($u['status']) ?> badge--<?= e($u['role']) === 'admin' ? 'admin' : '' ?>">
                  <?php if ($u['role'] === 'admin'): ?>
                    &#9733; Admin
                  <?php else: ?>
                    <?= e(ucfirst($u['status'])) ?>
                  <?php endif; ?>
                </span>
              </td>
              <?php if (in_array($filter, ['pending', 'all'])): ?>
              <td>
                <div class="admin-table__actions">
                  <?php if ($u['status'] === 'pending' && $u['id'] !== $user['id']): ?>
                    <form method="POST" action="/admin.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" name="action" value="approve" class="btn btn--success btn--sm">
                        Approve
                      </button>
                    </form>
                    <form method="POST" action="/admin.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" name="action" value="reject" class="btn btn--danger btn--sm">
                        Reject
                      </button>
                    </form>
                  <?php elseif ($u['id'] === $user['id']): ?>
                    <span style="font-size:0.8rem;color:var(--clr-text-faint);">You</span>
                  <?php else: ?>
                    <span style="font-size:0.8rem;color:var(--clr-text-faint);">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
