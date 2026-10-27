<?php
// Load the public vaccine catalog from the configured database connection.
require_once __DIR__ . '/includes/db.php';
$pdo = db();
$vaccines = $pdo instanceof PDO
    ? $pdo->query('SELECT name, disease_prevented, recommended_age_days, availability FROM vaccines ORDER BY recommended_age_days, name')->fetchAll(PDO::FETCH_ASSOC)
    : [];
require_once __DIR__ . '/includes/header.php';
$publicEscape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!-- Public catalog pages use the current vaccine records and shared brand styles. -->
<main class="landing-page public-info-page">
    <section class="container public-info-heading">
        <span class="eyebrow">Immunization catalog</span>
        <h1>Vaccines for <span class="highlight">every milestone</span><span class="highlight alt">.</span></h1>
        <p>Review the vaccines in the VaxCare schedule, what they help prevent, and their recommended age.</p>
    </section>
    <!-- Vaccine cards keep age guidance and availability visible at a glance. -->
    <?php if ($vaccines): ?>
        <section class="container public-info-grid public-vaccine-grid" aria-label="Vaccine catalog">
            <?php foreach ($vaccines as $vaccine): ?>
                <article class="feature-card public-info-card">
                    <div class="vaccine-card-top"><span class="info-step">VC</span><span class="info-status <?= $vaccine['availability'] === 'available' ? 'is-available' : 'is-unavailable' ?>"><?= $publicEscape(ucfirst($vaccine['availability'])) ?></span></div>
                    <h2><?= $publicEscape($vaccine['name']) ?></h2>
                    <p>Helps prevent <?= $publicEscape($vaccine['disease_prevented'] ?: 'vaccine-preventable illness') ?>.</p>
                    <div class="vaccine-age"><span>Recommended age</span><strong><?= $publicEscape(convertAgeDaysToText((int) $vaccine['recommended_age_days'])) ?></strong></div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="container public-info-empty"><p>The vaccine catalog is temporarily unavailable. Please check back soon.</p></section>
    <?php endif; ?>
    <!-- Connect catalog browsing to personalized child schedules. -->
    <section class="container public-info-cta"><div><h2>See a schedule for your child</h2><p>Recommended dates are based on each child’s profile.</p></div><a class="btn btn-primary" href="<?= $publicEscape(appUrl('register_parent.php')) ?>">Create an account</a></section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>