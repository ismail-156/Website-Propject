<?php
// Authenticate and optionally load the selected vaccine for editing.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'vaccines';
$vaccineId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$vaccine = ['name' => '', 'disease_prevented' => '', 'recommended_age_days' => 0, 'description' => '', 'availability' => 'available'];
if ($vaccineId > 0) {
    $vaccineStmt = $pdo->prepare('SELECT id, name, disease_prevented, recommended_age_days, availability FROM vaccines WHERE id = :id LIMIT 1');
    $vaccineStmt->execute(['id' => $vaccineId]);
    $record = $vaccineStmt->fetch();
    if ($record) $vaccine = array_merge($vaccine, $record); else $vaccineId = 0;
}
$pageTitle = $vaccineId > 0 ? 'Edit vaccine' : 'Add vaccine';
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Vaccine editor keeps all contract fields together in a single panel. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Immunization catalog</span><h1><?= $vaccineId > 0 ? 'Edit' : 'Add' ?> <span class="highlight">Vaccine</span><span class="highlight alt">.</span></h1><p>Set the prevention details and recommended schedule age.</p></div><a class="btn btn-secondary" href="<?= e(appUrl('admin/vaccines.php')) ?>"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Vaccine library</a></section>
<!-- The action endpoint receives the required CSRF token and vaccine fields. -->
<section class="admin-panel admin-form-panel"><form class="admin-form-grid" method="post" action="<?= e(appUrl('actions/admin_vaccine.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><?php if ($vaccineId > 0): ?><input type="hidden" name="vaccine_id" value="<?= $vaccineId ?>"><?php endif; ?>
    <label class="admin-field"><span>Vaccine name</span><input name="name" value="<?= e($vaccine['name']) ?>" maxlength="150" required></label>
    <label class="admin-field"><span>Disease prevented</span><input name="disease_prevented" value="<?= e($vaccine['disease_prevented']) ?>" maxlength="150" required></label>
    <label class="admin-field"><span>Recommended age</span><span class="admin-age-control" data-age-converter><input type="number" min="0" max="36525" step="1" name="recommended_age_value" value="<?= (int) $vaccine['recommended_age_days'] ?>" required data-age-value aria-label="Recommended age"><select name="recommended_age_unit" data-age-unit aria-label="Age unit"><option value="days">Days</option><option value="years">Years</option></select><input type="hidden" name="recommended_age_days" value="<?= (int) $vaccine['recommended_age_days'] ?>" data-age-days></span></label>
    <label class="admin-field"><span>Availability</span><select name="availability"><option value="available"<?= $vaccine['availability'] === 'available' ? ' selected' : '' ?>>Available</option><option value="unavailable"<?= $vaccine['availability'] === 'unavailable' ? ' selected' : '' ?>>Unavailable</option></select></label>
    <label class="admin-field admin-field-wide"><span>Description</span><textarea name="description" rows="5"><?= e($vaccine['description']) ?></textarea></label>
    <div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Save vaccine</button><a class="btn btn-secondary" href="<?= e(appUrl('admin/vaccines.php')) ?>">Cancel</a></div>
</form></section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>