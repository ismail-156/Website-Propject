<?php
// Authenticate and load the vaccine catalog for availability administration.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'vaccines';
$pageTitle = 'Vaccines';
$vaccines = $pdo->query('SELECT id AS vaccine_id, name, disease_prevented, recommended_age_days, availability FROM vaccines ORDER BY name')->fetchAll();
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Catalog heading pairs vaccine management with a direct create action. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Immunization catalog</span><h1>Vaccine <span class="highlight">Library</span><span class="highlight alt">.</span></h1><p>Manage recommended vaccines and their public availability.</p></div><a class="btn btn-primary" href="<?= e(appUrl('admin/vaccine_form.php')) ?>"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add vaccine</a></section>
<!-- Vaccine table includes the contract toggle form and edit navigation. -->
<section class="admin-panel"><div class="panel-heading"><div><span class="admin-eyebrow">Catalog</span><h2><?= count($vaccines) ?> vaccines</h2></div></div><div class="table-responsive"><table class="admin-table"><thead><tr><th>Vaccine</th><th>Protects against</th><th>Recommended age</th><th>Availability</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($vaccines as $vaccine): $available = $vaccine['availability'] === 'available'; ?>
        <tr><td><strong><?= e($vaccine['name']) ?></strong></td><td><?= e($vaccine['disease_prevented']) ?></td><td><?= e(convertAgeDaysToText((int) $vaccine['recommended_age_days'])) ?></td><td><form class="admin-toggle-form" method="post" action="<?= e(appUrl('actions/admin_vaccine.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="vaccine_id" value="<?= (int) $vaccine['vaccine_id'] ?>"><input type="hidden" name="availability" value="unavailable"><label class="admin-switch"><input type="checkbox" name="availability" value="available"<?= $available ? ' checked' : '' ?> onchange="this.form.requestSubmit()" aria-label="Make <?= e($vaccine['name']) ?> <?= $available ? 'unavailable' : 'available' ?>"><span aria-hidden="true"></span></label><span class="status-badge <?= e(admin_status_class($vaccine['availability'])) ?>"><?= $available ? 'Available' : 'Unavailable' ?></span></form></td><td><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/vaccine_form.php')) ?>?id=<?= (int) $vaccine['vaccine_id'] ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$vaccines): ?><tr><td colspan="5" class="empty-table-cell">No vaccines have been added.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>