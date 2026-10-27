<?php
$footerRole = $_SESSION['user_role'] ?? '';
$escapeFooter = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isParentFooter = $footerRole === 'parent';
$isAdminFooter = $footerRole === 'admin';

if ($isParentFooter) {
    $exploreLinks = [
        ['Dashboard', appUrl('parent/dashboard.php')],
        ['My children', appUrl('parent/children.php')],
        ['Vaccination schedule', appUrl('parent/schedule.php')],
    ];
    $accountHeading = 'Your account';
    $accountLinks = [
        ['Find a hospital', appUrl('parent/hospitals.php')],
        ['My bookings', appUrl('parent/my_bookings.php')],
        ['Profile and settings', appUrl('parent/profile.php')],
    ];
} elseif ($isAdminFooter) {
    $exploreLinks = [
        ['Admin dashboard', appUrl('admin/dashboard.php')],
        ['Hospital applications', appUrl('admin/hospitals.php')],
        ['Public home', appUrl('index.php')],
    ];
    $accountHeading = 'Your account';
    $accountLinks = [
        ['Vaccines', appUrl('vaccines.php')],
        ['Public site', appUrl('index.php')],
        ['Log out', appUrl('logout.php')],
    ];
} else {
    $exploreLinks = [
        ['Home', appUrl('index.php')],
        ['How it works', appUrl('how-it-works.php')],
        ['Vaccines', appUrl('vaccines.php')],
    ];
    $accountHeading = 'Get started';
    $accountLinks = [
        ['Register as parent', appUrl('register_parent.php')],
        ['Register a hospital', appUrl('register_hospital.php')],
        ['Log in', appUrl('login.php')],
    ];
}
?>
<!-- Shared brand footer used across public, administrator, and parent surfaces. -->
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="footer-brand-link" href="<?= $escapeFooter(appUrl('index.php')) ?>" aria-label="VaxCare home">
                    <img src="<?= $escapeFooter(appUrl('VaxCare Logo.png')) ?>" alt="VaxCare">
            </a>
            <p>Care, one milestone at a time. Keep your family’s vaccination journey easy to follow.</p>
        </div>
        <nav class="footer-group" aria-label="Explore">
            <h2>Explore</h2>
            <ul class="footer-links">
                <?php foreach ($exploreLinks as [$label, $url]): ?>
                    <li><a href="<?= $escapeFooter($url) ?>"><?= $escapeFooter($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <nav class="footer-group" aria-label="<?= $escapeFooter($accountHeading) ?>">
            <h2><?= $escapeFooter($accountHeading) ?></h2>
            <ul class="footer-links">
                <?php foreach ($accountLinks as [$label, $url]): ?>
                <li><a href="<?= $escapeFooter($url) ?>"><?= $escapeFooter($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <span>© <?= date('Y') ?> VaxCare</span>
            <span>Supporting healthier futures for every child.</span>
        </div>
    </div>
</footer>
