<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/auth/init.php';
require_login();

$user = current_user();
$db   = getDB();

// ── Events from DB ────────────────────────────────────────────────────────────
$eventsRaw = $db->query('SELECT * FROM events ORDER BY id ASC')->fetchAll();
// Normalise 'event_time' → 'time' so templates keep working
$events = array_map(function($e) {
    $e['time'] = $e['event_time'];
    return $e;
}, $eventsRaw);

// ── Live stats ────────────────────────────────────────────────────────────────
$statMembers = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
$statEvents  = (int) $db->query('SELECT COUNT(*) FROM events')->fetchColumn();

// ── Handle RSVP POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        // silently ignore bad CSRF
    } else {
        $slug   = $_POST['event_slug'] ?? '';
        $action = $_POST['rsvp_action']; // 'going' or 'cancel'

        // Find event title from static list
        $eventTitle = '';
        foreach ($events as $ev) {
            if ($ev['slug'] === $slug) { $eventTitle = $ev['title']; break; }
        }

        if ($slug && $eventTitle) {
            if ($action === 'going') {
                $stmt = $db->prepare('
                    INSERT OR IGNORE INTO events_rsvp (user_id, event_slug, event_title, rsvp_status)
                    VALUES (?, ?, ?, \'going\')
                ');
                $stmt->execute([$user['id'], $slug, $eventTitle]);
            } elseif ($action === 'cancel') {
                $stmt = $db->prepare('DELETE FROM events_rsvp WHERE user_id = ? AND event_slug = ?');
                $stmt->execute([$user['id'], $slug]);
            }
        }
    }
    // PRG pattern
    header('Location: /dashboard.php');
    exit;
}

// ── Fetch RSVP data ───────────────────────────────────────────────────────────
// Which events this user has RSVP'd to
$stmt = $db->prepare('SELECT event_slug FROM events_rsvp WHERE user_id = ?');
$stmt->execute([$user['id']]);
$myRsvps = array_column($stmt->fetchAll(), 'event_slug');

// Count RSVPs per event
$stmt = $db->query('SELECT event_slug, COUNT(*) as cnt FROM events_rsvp GROUP BY event_slug');
$rsvpCounts = [];
foreach ($stmt->fetchAll() as $row) {
    $rsvpCounts[$row['event_slug']] = (int) $row['cnt'];
}

// ── Interest / background labels ──────────────────────────────────────────────
$interestLabels = [
    'cv' => 'Computer Vision', 'nlp' => 'NLP', 'rl' => 'Reinforcement Learning',
    'ds' => 'Data Science', 'mlops' => 'MLOps', 'research' => 'AI Research',
];
$bgLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$isPending  = $user['status'] === 'pending';
$isApproved = $user['status'] === 'approved';
$isAdmin    = $user['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="dash-page">

  <!-- ── Top bar ── -->
  <header class="dash-topbar">
    <a href="/index.html" class="dash-topbar__logo"><span>K</span>mind</a>

    <nav class="dash-topbar__nav">
      <a href="/dashboard.php" class="active">Dashboard</a>
      <?php if ($isAdmin): ?>
        <a href="/admin.php">Admin Panel</a>
      <?php endif; ?>
      <a href="/index.html">Main Site</a>
    </nav>

    <div class="dash-topbar__right">
      <div class="dash-topbar__user">
        <div class="dash-topbar__avatar"><?= e($initials) ?></div>
        <span class="dash-topbar__name"><?= e($user['first_name']) ?></span>
      </div>
      <a href="/logout.php" class="btn btn--ghost" style="padding: 0.45rem 1rem; font-size: 0.82rem;">
        Sign out
      </a>
    </div>
  </header>

  <!-- ── Body ── -->
  <div class="dash-body">

    <!-- ── Main column ── -->
    <main class="dash-main">

      <!-- Welcome -->
      <div class="dash-welcome">
        <p class="dash-welcome__eyebrow">
          <?= $isAdmin ? 'Admin Dashboard' : 'Member Dashboard' ?>
        </p>
        <h1 class="dash-welcome__title">
          Welcome back, <?= e($user['first_name']) ?>
        </h1>
      </div>

      <?php if ($isPending): ?>
      <!-- ── Pending banner ── -->
      <div class="dash-pending-banner">
        <div class="dash-pending-banner__icon">&#9203;</div>
        <div>
          <p class="dash-pending-banner__title">Your application is under review</p>
          <p class="dash-pending-banner__desc">
            An admin will review your application and approve your access.
            You&rsquo;ll be able to RSVP to events and see full stats once approved.
            Check back here after approval.
          </p>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($isApproved || $isAdmin): ?>
      <!-- ── Stats ── -->
      <h2 class="dash-section-title">Club Stats</h2>
      <div class="dash-stat-grid">
        <div class="dash-stat">
          <div class="dash-stat__num"><?= $statMembers ?></div>
          <div class="dash-stat__label">Members</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat__num">14<span style="font-size:1.2rem;color:var(--clr-text-muted)">+</span></div>
          <div class="dash-stat__label">Projects</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat__num"><?= $statEvents ?></div>
          <div class="dash-stat__label">Events</div>
        </div>
        <div class="dash-stat">
          <div class="dash-stat__num">6</div>
          <div class="dash-stat__label">Papers</div>
        </div>
      </div>

      <!-- ── Events / RSVP ── -->
      <h2 class="dash-section-title">Upcoming Events</h2>
      <div class="dash-events">
        <?php foreach ($events as $ev):
            $going = in_array($ev['slug'], $myRsvps);
            $count = $rsvpCounts[$ev['slug']] ?? 0;
        ?>
        <div class="dash-event-card">
          <div class="dash-event-card__date">
            <span class="dash-event-card__day"><?= e($ev['day']) ?></span>
            <span class="dash-event-card__month"><?= e($ev['month']) ?></span>
          </div>

          <div class="dash-event-card__body">
            <p class="dash-event-card__title"><?= e($ev['title']) ?></p>
            <div class="dash-event-card__meta">
              <span><svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= e($ev['location']) ?></span>
              <span><svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> <?= e($ev['time']) ?></span>
            </div>
          </div>

          <div class="dash-event-card__rsvp">
            <form class="rsvp-form" method="POST" action="/dashboard.php">
              <?= csrf_field() ?>
              <input type="hidden" name="event_slug" value="<?= e($ev['slug']) ?>">
              <?php if ($going): ?>
                <button type="submit" name="rsvp_action" value="cancel" class="btn--rsvp-cancel">
                  &#10003; Going
                </button>
              <?php else: ?>
                <button type="submit" name="rsvp_action" value="going" class="btn--rsvp-going">
                  + RSVP
                </button>
              <?php endif; ?>
              <?php if ($count > 0): ?>
                <span class="rsvp-count"><?= $count ?> going</span>
              <?php endif; ?>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </main>

    <!-- ── Sidebar ── -->
    <aside class="dash-sidebar">

      <!-- Profile card -->
      <div class="dash-card">
        <p class="dash-card__title">Profile</p>
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
        <?php if ($user['department']): ?>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Dept.</span>
          <span class="dash-profile__row-val"><?= e($user['department']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($user['background']): ?>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Level</span>
          <span class="dash-profile__row-val"><?= e($bgLabels[$user['background']] ?? ucfirst($user['background'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($user['interest']): ?>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Focus</span>
          <span class="dash-profile__row-val"><?= e($interestLabels[$user['interest']] ?? ucfirst($user['interest'])) ?></span>
        </div>
        <?php endif; ?>
        <div class="dash-profile__row">
          <span class="dash-profile__row-label">Joined</span>
          <span class="dash-profile__row-val"><?= e(date('M j, Y', strtotime($user['created_at']))) ?></span>
        </div>
        <a href="/profile.php" class="btn btn--ghost btn--full" style="margin-top:var(--space-4);font-size:0.82rem;padding:0.45rem 1rem;">
          Edit Profile
        </a>
      </div>

      <?php if ($isAdmin): ?>
      <!-- Admin quick link -->
      <div class="dash-card" style="text-align:center;">
        <p class="dash-card__title">Admin</p>
        <p style="font-size:0.85rem;color:var(--clr-text-muted);margin-bottom:var(--space-5);">
          Review and approve member applications.
        </p>
        <?php
          $pending = (int) $db->query('SELECT COUNT(*) FROM users WHERE status = \'pending\'')->fetchColumn();
        ?>
        <a href="/admin.php" class="btn btn--primary btn--full">
          Admin Panel
          <?php if ($pending > 0): ?>
            <span style="background:rgba(255,255,255,0.2);border-radius:var(--radius-full);padding:1px 7px;font-size:0.75rem;">
              <?= $pending ?>
            </span>
          <?php endif; ?>
        </a>
      </div>
      <?php endif; ?>

      <!-- My events summary -->
      <?php if ($isApproved || $isAdmin): ?>
      <div class="dash-card">
        <p class="dash-card__title">My RSVPs</p>
        <?php if (empty($myRsvps)): ?>
          <p style="font-size:0.85rem;color:var(--clr-text-faint);">No RSVPs yet. Join an event above!</p>
        <?php else: ?>
          <ul style="display:flex;flex-direction:column;gap:var(--space-2);">
            <?php foreach ($events as $ev):
              if (!in_array($ev['slug'], $myRsvps)) continue;
            ?>
            <li style="display:flex;align-items:center;gap:var(--space-3);font-size:0.85rem;">
              <span style="color:var(--clr-success);">&#10003;</span>
              <span><?= e($ev['title']) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </aside>
  </div>

</body>
</html>
