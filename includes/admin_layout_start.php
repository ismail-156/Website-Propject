<?php
// Resolve shared navigation and identity before emitting the page shell.
$pageTitle = $pageTitle ?? 'Admin workspace';
$activeAdminPage = $activeAdminPage ?? '';
$adminName = admin_display_name($pdo, (int) $_SESSION['user_id']);
$adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');
$adminNavigation = [
    'dashboard' => ['Dashboard', 'dashboard.php', 'bi-grid-1x2'],
    'children' => ['Children', 'children.php', 'bi-people'],
    'upcoming' => ['Upcoming', 'upcoming.php', 'bi-calendar2-week'],
    'vaccines' => ['Vaccines', 'vaccines.php', 'bi-capsule'],
    'requests' => ['Requests', 'requests.php', 'bi-inbox'],
    'bookings' => ['Bookings', 'bookings.php', 'bi-journal-check'],
    'hospitals' => ['Hospitals', 'hospitals.php', 'bi-hospital'],
    'reports' => ['Reports', 'reports.php', 'bi-bar-chart'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Shared VaxCare metadata, Bootstrap grid, icons, and existing brand styles. -->
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
    <link rel="stylesheet" href="<?= e(appUrl('assets/css/admin.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/admin.css')) ?>">
</head>
<body class="admin-app">
    <?php require __DIR__ . '/medical_decorations.php'; ?>
    <!-- Mobile scrim and floating sidebar provide the same navigation at every width. -->
    <button class="admin-scrim" type="button" aria-label="Close navigation" data-admin-close></button>
    <aside class="admin-sidebar" data-admin-sidebar aria-label="Administrator navigation">
        <a class="admin-brand" href="<?= e(appUrl('admin/dashboard.php')) ?>" aria-label="VaxCare admin dashboard">
            <img class="brand-logo" src="<?= e(appUrl('VaxCare Logo.png')) ?>" alt="VaxCare">
            <span>VaxCare<span class="admin-brand-dot">.</span></span>
        </a>
        <nav class="admin-nav">
            <span class="admin-nav-label">Workspace</span>
            <?php foreach ($adminNavigation as $key => [$label, $path, $icon]): ?>
                <a class="admin-nav-link<?= $activeAdminPage === $key ? ' is-active' : '' ?>" href="<?= e(appUrl('admin/' . $path)) ?>"<?= $activeAdminPage === $key ? ' aria-current="page"' : '' ?>>
                    <i class="bi <?= e($icon) ?>" aria-hidden="true"></i><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <a class="admin-nav-link admin-logout" href="<?= e(appUrl('actions/logout.php')) ?>">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Logout</span>
        </a>
    </aside>
    <!-- Top bar keeps identity visible while the admin content scrolls. -->
    <div class="admin-shell">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-admin-menu-toggle>
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <span class="admin-topbar-caption">Administration</span>
            <div class="admin-user"><span class="admin-avatar" aria-hidden="true"><?= e($adminInitial) ?></span><span><?= e($adminName) ?></span></div>
        </header>
        <main class="admin-main container-fluid">
            <!-- Shared one-time flash feedback is rendered above the active page. -->
            <?php $adminFlash = flash(); ?>
            <?php if ($adminFlash): ?>
                <div class="admin-flash <?= ($adminFlash['type'] ?? '') === 'danger' ? 'is-danger' : 'is-success' ?>" role="status" data-auto-dismiss>
                    <i class="bi <?= ($adminFlash['type'] ?? '') === 'danger' ? 'bi-exclamation-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                    <span><?= e($adminFlash['msg'] ?? '') ?></span>
                    <button type="button" aria-label="Dismiss message" data-dismiss-alert><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            <?php endif; ?>