<?php
// Load the shared public header and its authenticated navigation state.
require_once __DIR__ . '/includes/header.php';
$publicEscape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!-- Explain the parent vaccination workflow using the site's established cards. -->
<main class="landing-page public-info-page">
    <section class="container public-info-heading">
        <span class="eyebrow">A simpler care journey</span>
        <h1>How <span class="highlight">VaxCare</span> works<span class="highlight alt">.</span></h1>
        <p>From a child’s profile to a completed vaccination record, keep every step connected.</p>
    </section>
    <!-- Steps map directly to the account and booking features in the app. -->
    <section class="container public-info-grid" aria-label="How VaxCare works">
        <article class="feature-card public-info-card"><span class="info-step">01</span><h2>Create a family profile</h2><p>Add each child’s name, date of birth, gender, and blood group. VaxCare prepares a schedule from the child’s date of birth.</p></article>
        <article class="feature-card public-info-card"><span class="info-step">02</span><h2>Review due vaccines</h2><p>See due dates and status in the family dashboard and schedule. Upcoming milestones stay connected to each child.</p></article>
        <article class="feature-card public-info-card"><span class="info-step">03</span><h2>Choose a hospital</h2><p>Search active vaccination providers, select an available vaccine, and request an appointment that fits your family.</p></article>
        <article class="feature-card public-info-card"><span class="info-step">04</span><h2>Keep the record</h2><p>Follow the appointment outcome and view or print a child-specific record of completed vaccinations.</p></article>
    </section>
    <!-- Give visitors a direct next step without adding another navigation layer. -->
    <section class="container public-info-cta"><div><h2>Start with your child’s schedule</h2><p>Create a parent account to add a profile and see recommended milestones.</p></div><a class="btn btn-primary" href="<?= $publicEscape(appUrl('register_parent.php')) ?>">Get started</a></section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>