<?php
// Load the current schedule recommendations from the configured vaccine catalog.
require_once __DIR__ . '/includes/db.php';
$pdo = db();
$schedule = $pdo instanceof PDO
    ? $pdo->query('SELECT name, disease_prevented, recommended_age_days, availability FROM vaccines ORDER BY recommended_age_days, name')->fetchAll(PDO::FETCH_ASSOC)
    : [];
require_once __DIR__ . '/includes/header.php';
$publicEscape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$scheduleUrl = $loggedIn && ($currentUser['role'] ?? '') === 'parent'
    ? appUrl('parent/schedule.php')
    : appUrl('register_parent.php');
$scheduleAction = $loggedIn && ($currentUser['role'] ?? '') === 'parent' ? 'Open my schedule' : 'Create a child schedule';
?>
<!-- Explain general vaccine timing before opening a parent's personalized schedule. -->
<main class="landing-page public-info-page">
    <section class="container public-info-heading">
        <span class="eyebrow">Recommended timing</span>
        <h1>Vaccination <span class="highlight">schedule</span><span class="highlight alt">.</span></h1>
        <p>Use these age recommendations as a guide. A parent account shows the personalized due dates for each child.</p>
    </section>
    <!-- This catalog-backed table stays current with vaccine recommendations. -->
    <section class="container public-info-panel">
        <?php if ($schedule): ?>
            <div class="table-responsive"><table class="public-vaccine-table"><thead><tr><th>Recommended age</th><th>Vaccine</th><th>Protects against</th><th>Availability</th></tr></thead><tbody>
                <?php foreach ($schedule as $dose): ?><tr><td><strong><?= $publicEscape(convertAgeDaysToText((int) $dose['recommended_age_days'])) ?></strong></td><td><?= $publicEscape($dose['name']) ?></td><td><?= $publicEscape($dose['disease_prevented']) ?></td><td><span class="info-status <?= $dose['availability'] === 'available' ? 'is-available' : 'is-unavailable' ?>"><?= $publicEscape(ucfirst($dose['availability'])) ?></span></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><p class="muted-copy">Schedule recommendations are temporarily unavailable.</p><?php endif; ?>
    </section>
    <!-- Parents can continue to their own schedule or register for one. -->
    <section class="container public-info-cta"><div><h2>Dates tailored to your child</h2><p>VaxCare calculates recommended milestones from each child’s birth date.</p></div><a class="btn btn-primary" href="<?= $publicEscape($scheduleUrl) ?>"><?= $publicEscape($scheduleAction) ?></a></section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>