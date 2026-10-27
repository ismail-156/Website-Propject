<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
mark_missed($pdo);
$activePage = 'dashboard';
$pageTitle = 'Parent dashboard';

// Dashboard counts only use children, schedules, and bookings owned by this parent.
$countStmt = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM children WHERE parent_id = :children_parent) AS children_count,
        (SELECT COUNT(*) FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id
            WHERE c.parent_id = :upcoming_parent AND cv.status IN ('pending', 'missed')
              AND cv.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS upcoming_count,
        (SELECT COUNT(*) FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id
            WHERE c.parent_id = :vaccinated_parent AND cv.status = 'vaccinated') AS vaccinated_count,
        (SELECT COUNT(*) FROM bookings WHERE parent_id = :bookings_parent AND status = 'pending') AS pending_count"
);
$countStmt->execute([
    'children_parent' => $pid,
    'upcoming_parent' => $pid,
    'vaccinated_parent' => $pid,
    'bookings_parent' => $pid,
]);
$stats = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// The dashboard list includes all future pending/missed doses; the summary stat remains a 30-day count.
$upcomingStmt = $pdo->prepare(
    "SELECT cv.cv_id, cv.child_id, cv.vaccine_id, cv.due_date, cv.status, c.name AS child_name,
            v.name AS vaccine_name, DATEDIFF(cv.due_date, CURDATE()) AS days_left
     FROM child_vaccinations cv
     INNER JOIN children c ON c.id = cv.child_id
     INNER JOIN vaccines v ON v.id = cv.vaccine_id
     WHERE c.parent_id = :parent_id
       AND cv.status IN ('pending', 'missed')
             AND cv.due_date >= CURDATE()
     ORDER BY cv.due_date ASC
     LIMIT 6"
);
$upcomingStmt->execute(['parent_id' => $pid]);
$upcomingItems = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

// Recent alerts are restricted to the signed-in user's notification records.
$notificationsStmt = $pdo->prepare(
    'SELECT id, message, is_read FROM notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 5'
);
$notificationsStmt->execute(['user_id' => (int) $_SESSION['user_id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$greetingName = parent_display_name($pdo, (int) $_SESSION['user_id']);

require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Dashboard summary and family-specific next steps. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Your family health hub</span>
    <h1>Hello, <span class="highlight"><?= e($greetingName) ?></span><span class="highlight alt">.</span></h1>
    <p>Keep your children’s vaccine schedules and care visits together in one calm place.</p>
</section>

<!-- At-a-glance parent statistics. -->
<section class="row g-3 mb-4" aria-label="Vaccination overview">
    <?php foreach ([
        ['children_count', 'My children', 'bi-people', 'purple'],
        ['upcoming_count', 'Due in 30 days', 'bi-calendar-heart', 'orange'],
        ['vaccinated_count', 'Vaccinated', 'bi-shield-check', 'green'],
        ['pending_count', 'Pending bookings', 'bi-clock-history', 'pink'],
    ] as [$key, $label, $icon, $tone]): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="parent-card parent-stat-card">
                <span class="parent-stat-icon <?= e($tone) ?>"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span>
                <span>
                    <strong class="parent-stat-number"><?= (int) ($stats[$key] ?? 0) ?></strong>
                    <span class="parent-stat-label"><?= e($label) ?></span>
                </span>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<!-- Main dashboard columns keep actions and reminders visible together. -->
<section class="row g-4 dashboard-content-row">
    <div class="col-12 col-lg-8 dashboard-column">
        <article class="parent-card upcoming-card">
            <div class="parent-card-heading">
                <div><h2>Upcoming vaccinations</h2><p>Your next schedule milestones</p></div>
                <a class="parent-btn-secondary parent-btn-small btn" href="<?= e(parent_path('schedule.php')) ?>">Full schedule</a>
            </div>
            <?php if ($upcomingItems): ?>
                <div class="parent-list">
                    <?php foreach ($upcomingItems as $item):
                        $daysLeft = (int) $item['days_left'];
                        $isOverdue = $daysLeft < 0;
                    ?>
                        <div class="upcoming-row">
                            <div>
                                <h3><?= e($item['vaccine_name']) ?></h3>
                                <p><?= e($item['child_name']) ?> · <?= e(date('M j, Y', strtotime($item['due_date']))) ?></p>
                            </div>
                            <span class="days-pill<?= $isOverdue ? ' is-overdue' : '' ?>">
                                <?= $isOverdue ? abs($daysLeft) . ' days overdue' : ($daysLeft === 0 ? 'Due today' : 'In ' . $daysLeft . ' days') ?>
                            </span>
                            <?php if (in_array($item['status'], ['pending', 'missed'], true)): ?>
                                <a class="btn btn-primary parent-btn-small" href="<?= e(parent_path('book.php')) ?>?child_id=<?= (int) $item['child_id'] ?>&amp;vaccine_id=<?= (int) $item['vaccine_id'] ?>">Book</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-art"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
                    <?php if ((int) ($stats['children_count'] ?? 0) > 0): ?>
                        <h3>No upcoming vaccinations</h3>
                        <p>There are no future doses scheduled for your children right now.</p>
                        <a class="parent-btn-secondary btn" href="<?= e(parent_path('schedule.php')) ?>">View full schedule</a>
                    <?php else: ?>
                        <h3>Add a child to begin</h3>
                        <p>Add a child to see their recommended vaccine milestones here.</p>
                        <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>">Add a child</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>

        <!-- Quick actions are displayed as primary page tasks. -->
        <article class="parent-card quick-actions-card mt-4">
            <div class="parent-card-heading"><div><h2>Quick actions</h2><p>Pick up where you left off</p></div></div>
            <div class="quick-actions">
                <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>"><i class="bi bi-person-plus me-2" aria-hidden="true"></i>Add child</a>
                <a class="parent-btn-secondary btn" href="<?= e(parent_path('book.php')) ?>"><i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Book vaccine</a>
            </div>
        </article>
    </div>

    <!-- Notifications panel includes a CSRF-protected mark-read action. -->
    <div class="col-12 col-lg-4 dashboard-column">
        <article class="parent-card notifications-card">
            <div class="parent-card-heading"><div><h2>Notifications</h2><p>Recent updates about your family</p></div></div>
            <?php if ($notifications): ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item">
                            <span class="notification-dot" aria-hidden="true"></span>
                            <span><?= e($notification['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" action="<?= e(parent_action('parent_notifications.php')) ?>" class="mt-3">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button class="text-action" type="submit">Mark all as read</button>
                </form>
            <?php else: ?>
                <p class="muted-copy mb-0">No notifications yet. New updates will appear here.</p>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
