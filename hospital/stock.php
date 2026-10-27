<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
$hospitalName = hospital_display_name($pdo, $hid);
$activeNav = 'stock';
$pageTitle = 'Vaccine availability';

// A missing facility stock row defaults to available; explicit changes are facility-specific.
$stockStmt = $pdo->prepare(
    "SELECT v.id AS vaccine_id, v.name, v.disease_prevented, v.availability,
            COALESCE(hv.in_stock, 'yes') AS in_stock
     FROM vaccines v
     LEFT JOIN hospital_vaccines hv ON hv.vaccine_id = v.id AND hv.hospital_id = :hospital_id
     ORDER BY v.name"
);
$stockStmt->execute(['hospital_id' => $hid]);
$stock = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/hospital_layout_start.php';
?>
<!-- Facility staff can mark each globally bookable vaccine as locally available or unavailable. -->
<section class="hospital-page">
    <div class="page-header"><div><span class="parent-eyebrow">Supply status</span><h1 class="page-title">Vaccine availability</h1><p class="page-subtitle">Keep parents informed about what your facility can provide today.</p></div></div>
    <?php if ($stock): ?>
        <section class="hospital-panel">
            <div class="stock-grid">
                <?php foreach ($stock as $item):
                    $available = $item['availability'] === 'available' && $item['in_stock'] === 'yes';
                ?>
                    <article class="stock-card">
                        <div class="stock-card-heading"><span class="parent-stat-icon pink"><i class="bi bi-prescription2" aria-hidden="true"></i></span><span class="stock-state<?= $available ? '' : ' out-of-stock' ?>"><?= $available ? 'Available here' : 'Unavailable' ?></span></div>
                        <h2><?= e($item['name']) ?></h2>
                        <p><?= e($item['disease_prevented']) ?></p>
                        <?php if ($item['availability'] === 'available'): ?>
                            <form method="post" action="<?= e(hospital_action('hospital_stock.php')) ?>" class="stock-toggle-form">
                                <input type="hidden" name="vaccine_id" value="<?= (int) $item['vaccine_id'] ?>">
                                <input type="hidden" name="in_stock" value="<?= $item['in_stock'] === 'yes' ? 'yes' : 'no' ?>">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <label class="switch">
                                    <span class="visually-hidden">Mark <?= e($item['name']) ?> as available at this facility</span>
                                    <input type="checkbox" data-stock-toggle <?= $item['in_stock'] === 'yes' ? 'checked' : '' ?>>
                                    <span class="slider" aria-hidden="true"></span>
                                </label>
                            </form>
                        <?php else: ?>
                            <span class="muted-copy">This vaccine is currently unavailable in the system.</span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="hospital-panel hospital-empty-state"><span class="empty-art"><i class="bi bi-prescription2" aria-hidden="true"></i></span><h2>No vaccines listed</h2><p>Available vaccines will appear here when they are added by an administrator.</p></section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/hospital_layout_end.php'; ?>
