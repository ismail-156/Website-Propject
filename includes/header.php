<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$currentUser = currentUser();
$loggedIn = isLoggedIn();
$currentPage = basename($_SERVER['PHP_SELF']);
$dashboardUrl = 'login.php';

if ($loggedIn && $currentUser) {
    $dashboardUrl = match ($currentUser['role']) {
    'admin' => appUrl('admin/dashboard.php'),
    'parent' => appUrl('parent/dashboard.php'),
    'hospital' => appUrl('hospital/dashboard.php'),
    default => appUrl('login.php'),
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo appUrl('assets/css/style.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>
<body>
    <?php require __DIR__ . '/medical_decorations.php'; ?>
        <header class="site-header">
        <div class="container">
            <nav class="navbar" aria-label="Main navigation">
                <a href="<?php echo appUrl('index.php'); ?>" class="brand" aria-label="VaxCare home">
                    <img src="<?php echo appUrl('VaxCare Logo.png'); ?>" alt="VaxCare Logo" class="brand-logo">
                    <span class="brand-name">VaxCare</span>
                </a>

                <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="nav-links">
                    <?php foreach ([
                        'index.php' => ['Home', 'index.php'],
                        'how-it-works.php' => ['How It Works', 'how-it-works.php'],
                        'vaccines.php' => ['Vaccines', 'vaccines.php'],
                        'schedule.php' => ['Schedule', 'schedule.php'],
                    ] as $page => [$label, $path]): ?>
                        <a href="<?php echo appUrl($path); ?>" class="nav-link<?php echo $currentPage === $page ? ' active' : ''; ?>"<?php echo $currentPage === $page ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="nav-actions">
                    <?php if (!$loggedIn): ?>
                        <a href="<?php echo appUrl('login.php'); ?>" class="btn btn-login">Log In</a>
                        <a href="<?php echo appUrl('register_parent.php'); ?>" class="btn btn-primary btn-compact">Get Started</a>
                    <?php else: ?>
                        <a href="<?php echo $dashboardUrl; ?>" class="btn btn-login">Dashboard</a>
                        <a href="<?php echo appUrl('logout.php'); ?>" class="btn btn-primary btn-compact">Logout</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <?php if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])): ?>
        <div class="container flash-stack">
            <?php foreach ($_SESSION['flash'] as $key => $message): ?>
                <div class="alert alert-<?php echo $key === 'error' ? 'danger' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
