<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/auth/init.php';
require_admin();

$db   = getDB();
$user = current_user();

$errors  = [];
$success = '';

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title    = trim($_POST['title']    ?? '');
            $day      = trim($_POST['day']      ?? '');
            $month    = trim($_POST['month']    ?? '');
            $year     = trim($_POST['year']     ?? date('Y'));
            $location = trim($_POST['location'] ?? '');
            $time     = trim($_POST['event_time'] ?? '');

            if (!$title)    $errors[] = 'Event title is required.';
            if (!$day || !is_numeric($day) || (int)$day < 1 || (int)$day > 31)
                            $errors[] = 'Day must be 1–31.';
            if (!$month)    $errors[] = 'Month is required.';
            if (!$location) $errors[] = 'Location is required.';
            if (!$time)     $errors[] = 'Time / duration is required.';

            if (empty($errors)) {
                // Generate slug from title
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
                $slug = trim($slug, '-');
                // Append year to reduce collisions
                $slug .= '-' . $year;

                // Ensure unique slug
                $existing = $db->prepare('SELECT id FROM events WHERE slug = ?');
                $existing->execute([$slug]);
                if ($existing->fetch()) {
                    $slug .= '-' . substr(md5(microtime()), 0, 4);
                }

                $stmt = $db->prepare('
                    INSERT INTO events (slug, title, day, month, year, location, event_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$slug, $title, $day, $month, $year, $location, $time]);
                $success = 'Event "' . htmlspecialchars($title) . '" created.';
            }

        } elseif ($action === 'delete') {
            $id = (int) ($_POST['event_id'] ?? 0);
            if ($id > 0) {
                $db->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
                $db->prepare('DELETE FROM events_rsvp WHERE event_slug = (SELECT slug FROM events WHERE id = ?)')
                   ->execute([$id]); // clean up RSVPs (slug no longer valid)
                $success = 'Event deleted.';
            }
        }
    }
}

// ── Fetch all events ──────────────────────────────────────────────────────────
$events = $db->query('SELECT e.*, (SELECT COUNT(*) FROM events_rsvp r WHERE r.event_slug = e.slug) AS rsvp_count FROM events e ORDER BY e.id ASC')->fetchAll();

$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Events — Kmind ML Club</title>
  <link rel="stylesheet" href="/auth.css" />
</head>
<body class="dash-page">

  <!-- ── Top bar ── -->
  <header class="dash-topbar">
    <a href="/index.html" class="dash-topbar__logo"><span>K</span>mind</a>

    <nav class="dash-topbar__nav">
      <a href="/dashboard.php">Dashboard</a>
      <a href="/admin.php">Members</a>
      <a href="/admin-events.php" class="active">Events</a>
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
  <div class="dash-body" style="grid-template-columns: 1fr 340px;">

    <!-- ── Events table ── -->
    <main class="dash-main">
      <div class="dash-welcome">
        <p class="dash-welcome__eyebrow">Admin Panel</p>
        <h1 class="dash-welcome__title">Manage Events</h1>
      </div>

      <?php if ($success): ?>
        <div class="alert alert--success" style="margin-bottom:var(--space-6);">
          <span>&#10003;</span>
          <span><?= e($success) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" style="margin-bottom:var(--space-6);">
          <span>&#9888;</span>
          <div>
            <?php if (count($errors) === 1): ?>
              <?= e($errors[0]) ?>
            <?php else: ?>
              <ul style="margin-left:1rem;">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <h2 class="dash-section-title">All Events (<?= count($events) ?>)</h2>

      <?php if (empty($events)): ?>
        <p style="color:var(--clr-text-faint);font-size:0.9rem;">No events yet. Add one using the form.</p>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Date</th>
              <th>Location</th>
              <th>Time</th>
              <th style="text-align:center;">RSVPs</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $ev): ?>
            <tr>
              <td>
                <div class="admin-table__name"><?= e($ev['title']) ?></div>
              </td>
              <td style="white-space:nowrap;"><?= e($ev['day'] . ' ' . $ev['month'] . ' ' . $ev['year']) ?></td>
              <td><?= e($ev['location']) ?></td>
              <td style="white-space:nowrap;"><?= e($ev['event_time']) ?></td>
              <td style="text-align:center;">
                <span class="badge badge--member"><?= (int)$ev['rsvp_count'] ?></span>
              </td>
              <td>
                <form method="POST" action="/admin-events.php"
                      onsubmit="return confirm('Delete this event? This also removes all RSVPs.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </main>

    <!-- ── Add Event sidebar ── -->
    <aside class="dash-sidebar">
      <div class="dash-card">
        <p class="dash-card__title">Add New Event</p>

        <form method="POST" action="/admin-events.php" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">

          <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-input"
                   placeholder="e.g. NLP Workshop" required
                   value="<?= e($_POST['title'] ?? '') ?>" />
          </div>

          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Day</label>
              <input type="number" name="day" class="form-input"
                     placeholder="15" min="1" max="31" required
                     value="<?= e($_POST['day'] ?? '') ?>" />
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Month</label>
              <select name="month" class="form-input form-select" required>
                <option value="">Month</option>
                <?php
                $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                foreach ($months as $m):
                    $sel = (($_POST['month'] ?? '') === $m) ? 'selected' : '';
                ?>
                  <option value="<?= $m ?>" <?= $sel ?>><?= $m ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-input"
                   placeholder="<?= date('Y') ?>" min="2020" max="2099"
                   value="<?= e($_POST['year'] ?? date('Y')) ?>" />
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-input"
                   placeholder="e.g. Room 301, CS Building" required
                   value="<?= e($_POST['location'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label">Time / Duration</label>
            <input type="text" name="event_time" class="form-input"
                   placeholder="e.g. 3:00 PM – 5:00 PM" required
                   value="<?= e($_POST['event_time'] ?? '') ?>" />
          </div>

          <button type="submit" class="btn btn--primary btn--full">
            Create Event
          </button>
        </form>
      </div>
    </aside>

  </div>

</body>
</html>
