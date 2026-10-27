<?php
$pageTitle = $pageTitle ?? 'Hospital dashboard';
$activeNav = $activeNav ?? '';
$hospitalName = $hospitalName ?? 'Hospital';
$hospitalInitial = mb_strtoupper(mb_substr($hospitalName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Shared VaxCare typography, Bootstrap grid, icons, and hospital module styles. -->
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
    <link rel="stylesheet" href="<?= e(appUrl('assets/css/hospital.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/hospital.css')) ?>">
</head>
<body class="hospital-app">
    <?php require __DIR__ . '/medical_decorations.php'; ?>
    <!-- Hospital navigation links and account menu work from nested routes. -->
    <header class="hospital-header">
        <nav class="hospital-navbar" aria-label="Hospital navigation">
            <a class="hospital-brand" href="<?= e(hospital_path('dashboard.php')) ?>" aria-label="VaxCare hospital home">
                <img src="<?= e(appUrl('VaxCare Logo.png')) ?>" alt="VaxCare" class="brand-logo">
                <span class="brand-name">VaxCare</span>
            </a>
            <button class="hospital-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-hospital-menu-toggle>
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="hospital-nav-content" data-hospital-menu>
                <div class="hospital-nav-links">
                    <?php foreach ([
                        'dashboard' => ['Overview', 'dashboard.php', 'bi-grid-1x2'],
                        'appointments' => ['Appointments', 'appointments.php', 'bi-calendar2-week'],
                        'history' => ['History', 'history.php', 'bi-journal-check'],
                        'stock' => ['Vaccine stock', 'stock.php', 'bi-prescription2'],
                        'profile' => ['Profile', 'profile.php', 'bi-hospital'],
                    ] as $key => [$label, $path, $icon]): ?>
                        <a class="hospital-nav-link<?= $activeNav === $key ? ' is-active' : '' ?>"
                           href="<?= e(hospital_path($path)) ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= e($icon) ?>" aria-hidden="true"></i><span><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="hospital-account">
                    <span class="hospital-avatar" aria-hidden="true"><?= e($hospitalInitial) ?></span>
                    <span class="hospital-account-name"><?= e($hospitalName) ?></span>
                    <a class="hospital-logout" href="<?= e(hospital_action('logout.php')) ?>"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Log out</span></a>
                </div>
            </div>
        </nav>
    </header>
    <main class="hospital-main container">
        <!-- One-time request feedback uses the shared flash queue. -->
        <?php $hospitalFlash = flash(); ?>
        <?php if ($hospitalFlash): ?>
            <div class="hospital-flash <?= ($hospitalFlash['type'] ?? '') === 'danger' ? 'is-danger' : 'is-success' ?>" role="status">
                <i class="bi <?= ($hospitalFlash['type'] ?? '') === 'danger' ? 'bi-exclamation-circle' : 'bi-check-circle' ?>" aria-hidden="true"></i>
                <span><?= e($hospitalFlash['msg'] ?? '') ?></span>
            </div>
        <?php endif; ?>
