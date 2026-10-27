<?php
$pageTitle = $pageTitle ?? 'Parent dashboard';
$activePage = $activePage ?? '';
$userName = parent_display_name($pdo, (int) $_SESSION['user_id']);
$userInitial = strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
$unreadStmt->execute(['user_id' => (int) $_SESSION['user_id']]);
$unreadCount = (int) $unreadStmt->fetchColumn();
$notificationStmt = $pdo->prepare(
    'SELECT id, message, is_read FROM notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 5'
);
$notificationStmt->execute(['user_id' => (int) $_SESSION['user_id']]);
$layoutNotifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Shared metadata, framework utilities, brand styling, and module styling. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#edf0ff">
    <title><?= e($pageTitle) ?> · VaxCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(appUrl('assets/css/style.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(appUrl('assets/css/parent.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/parent.css')) ?>">
</head>
<body class="parent-app">
    <?php require __DIR__ . '/medical_decorations.php'; ?>
    <!-- Floating parent navigation with responsive links and account controls. -->
    <header class="parent-header">
        <nav class="parent-navbar container-fluid" aria-label="Parent navigation">
            <a class="parent-brand" href="<?= e(parent_path('dashboard.php')) ?>" aria-label="VaxCare parent home">
                <img class="brand-logo" src="<?= e(appUrl('VaxCare Logo.png')) ?>" alt="VaxCare">
                <span class="brand-name">VaxCare</span>
            </a>
            <button class="parent-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-parent-menu-toggle>
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="parent-nav-content" data-parent-menu>
                <div class="parent-nav-links">
                    <?php foreach ([
                        'dashboard' => ['Dashboard', 'dashboard.php', 'bi-grid-1x2'],
                        'children' => ['My Children', 'children.php', 'bi-people'],
                        'schedule' => ['Schedule', 'schedule.php', 'bi-calendar2-week'],
                        'hospitals' => ['Hospitals', 'hospitals.php', 'bi-hospital'],
                        'bookings' => ['Bookings', 'my_bookings.php', 'bi-journal-check'],
                        'reports' => ['Reports', 'report.php', 'bi-card-checklist'],
                    ] as $key => [$label, $path, $icon]): ?>
                        <a class="parent-nav-link<?= $activePage === $key ? ' is-active' : '' ?>"
                           href="<?= e(parent_path($path)) ?>"<?= $activePage === $key ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= e($icon) ?>" aria-hidden="true"></i><span><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="parent-nav-actions">
                    <!-- Notifications are limited to the signed-in user's records. -->
                    <details class="parent-dropdown notification-dropdown">
                        <summary class="notification-trigger" aria-label="Notifications, <?= $unreadCount ?> unread">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            <?php if ($unreadCount > 0): ?><span class="notification-count"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span><?php endif; ?>
                        </summary>
                        <div class="parent-dropdown-panel notification-panel">
                            <div class="dropdown-panel-heading"><strong>Notifications</strong><span><?= $unreadCount ?> unread</span></div>
                            <?php if ($layoutNotifications): ?>
                                <?php foreach ($layoutNotifications as $notice): ?>
                                    <p class="notification-preview<?= (int) $notice['is_read'] === 0 ? ' is-unread' : '' ?>"><?= e($notice['message']) ?></p>
                                <?php endforeach; ?>
                                <?php if ($unreadCount > 0): ?>
                                    <form method="post" action="<?= e(parent_action('parent_notifications.php')) ?>">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <button class="text-action" type="submit">Mark all as read</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="muted-copy mb-0">You’re all caught up.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                    <!-- Native details/summary keeps the profile menu keyboard accessible. -->
                    <details class="parent-dropdown profile-dropdown">
                        <summary class="profile-trigger">
                            <span class="profile-avatar"><?= e($userInitial) ?></span>
                            <span class="profile-name"><?= e($userName) ?></span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="parent-dropdown-panel profile-panel">
                            <a href="<?= e(parent_path('profile.php')) ?>"><i class="bi bi-person" aria-hidden="true"></i> Profile</a>
                            <a href="<?= e(parent_action('logout.php')) ?>"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout</a>
                        </div>
                    </details>
                </div>
            </div>
        </nav>
    </header>
    <main class="parent-main<?= $activePage === 'dashboard' ? ' parent-dashboard' : '' ?> container">
        <!-- The one-time flash message is shown at the top of the page. -->
        <?php $layoutFlash = flash(); ?>
        <?php if ($layoutFlash): ?>
            <div class="parent-alert parent-alert-<?= e($layoutFlash['type'] ?? 'success') ?>" role="status" data-auto-dismiss>
                <i class="bi <?= ($layoutFlash['type'] ?? '') === 'danger' ? 'bi-exclamation-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                <span><?= e($layoutFlash['msg'] ?? '') ?></span>
                <button type="button" aria-label="Dismiss message" data-dismiss-alert><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
        <?php endif; ?>
