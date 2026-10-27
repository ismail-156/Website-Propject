<?php
// Authenticate and load a hospital profile when an edit id is provided.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'hospitals';
$hospitalId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$hospital = ['hospital_name' => '', 'address' => '', 'city' => '', 'location' => '', 'phone' => '', 'email' => ''];
if ($hospitalId > 0) {
    $hospitalStmt = $pdo->prepare('SELECT h.id, h.hospital_name, h.address, h.city, h.location, h.phone, u.email FROM hospitals h INNER JOIN users u ON u.id = h.user_id WHERE h.id = :id AND u.role = \'hospital\' LIMIT 1');
    $hospitalStmt->execute(['id' => $hospitalId]);
    $record = $hospitalStmt->fetch();
    if ($record) $hospital = array_merge($hospital, $record); else $hospitalId = 0;
}
$pageTitle = $hospitalId > 0 ? 'Edit hospital' : 'Add hospital';
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Hospital editor reflects stored profile details and the requested account fields. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Facility network</span><h1><?= $hospitalId > 0 ? 'Edit' : 'Add' ?> <span class="highlight">Hospital</span><span class="highlight alt">.</span></h1><p>Maintain accurate contact and location information.</p></div><a class="btn btn-secondary" href="<?= e(appUrl('admin/hospitals.php')) ?>"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Hospitals</a></section>
<!-- Admin hospital handler receives profile data and add-only login credentials. -->
<section class="admin-panel admin-form-panel"><form class="admin-form-grid" method="post" action="<?= e(appUrl('actions/admin_hospital.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><?php if ($hospitalId > 0): ?><input type="hidden" name="hospital_id" value="<?= $hospitalId ?>"><?php endif; ?>
    <label class="admin-field"><span>Hospital name</span><input name="name" value="<?= e($hospital['hospital_name']) ?>" maxlength="150" required></label><label class="admin-field"><span>Phone</span><input name="phone" type="tel" value="<?= e($hospital['phone']) ?>" maxlength="20" required></label>
    <label class="admin-field"><span>City</span><input name="city" value="<?= e($hospital['city']) ?>" maxlength="100" required></label><label class="admin-field"><span>Location / area</span><input name="location" value="<?= e($hospital['location']) ?>" maxlength="150" required></label>
    <label class="admin-field admin-field-wide"><span>Street address</span><input name="address" value="<?= e($hospital['address']) ?>" maxlength="255" required></label>
    <?php if ($hospitalId === 0): ?><label class="admin-field"><span>Account email</span><input name="email" type="email" maxlength="150" required></label><label class="admin-field"><span>Temporary password</span><input name="password" type="password" minlength="8" autocomplete="new-password" required></label><?php endif; ?>
    <div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Save hospital</button><a class="btn btn-secondary" href="<?= e(appUrl('admin/hospitals.php')) ?>">Cancel</a></div>
</form></section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>