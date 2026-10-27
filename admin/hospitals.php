<?php
// Authenticate and load hospital details alongside their account status.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'hospitals';
$pageTitle = 'Hospitals';
$hospitalStmt = $pdo->query(
    "SELECT h.id AS hospital_id, h.user_id, h.hospital_name, h.address, h.city, h.location, h.phone, u.name AS contact_name, u.email, u.status
     FROM hospitals h INNER JOIN users u ON u.id = h.user_id
     WHERE u.role = 'hospital' ORDER BY h.hospital_name"
);
$hospitals = $hospitalStmt->fetchAll();
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Hospital access directory with the create entry point. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Facility network</span><h1>Hospital <span class="highlight">Management</span><span class="highlight alt">.</span></h1><p>Review facility details and manage account access.</p></div><a class="btn btn-primary" href="<?= e(appUrl('admin/hospital_form.php')) ?>"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add hospital</a></section>
<!-- Status and action forms use the requested admin hospital contract. -->
<section class="admin-panel"><div class="table-responsive"><table class="admin-table"><thead><tr><th>Hospital</th><th>Contact</th><th>Location</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($hospitals as $hospital): ?><tr><td><strong><?= e($hospital['hospital_name']) ?></strong><span class="table-subline"><?= e($hospital['address']) ?></span></td><td><?= e($hospital['contact_name']) ?><span class="table-subline"><?= e($hospital['email']) ?></span></td><td><?= e($hospital['city']) ?><span class="table-subline"><?= e($hospital['location']) ?></span></td><td><?= e($hospital['phone']) ?></td><td><span class="status-badge <?= e(admin_status_class($hospital['status'])) ?>"><?= e(ucfirst($hospital['status'])) ?></span></td><td><div class="admin-inline-actions"><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/hospital_form.php')) ?>?id=<?= (int) $hospital['hospital_id'] ?>" aria-label="Edit <?= e($hospital['hospital_name']) ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
        <?php if ($hospital['status'] === 'pending'): ?><form method="post" action="<?= e(appUrl('actions/admin_hospital.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="hospital_id" value="<?= (int) $hospital['hospital_id'] ?>"><input type="hidden" name="user_id" value="<?= (int) $hospital['user_id'] ?>"><button class="btn btn-secondary btn-small" type="submit">Approve</button></form><?php endif; ?>
        <?php if ($hospital['status'] === 'active'): ?><form method="post" action="<?= e(appUrl('actions/admin_hospital.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="block"><input type="hidden" name="hospital_id" value="<?= (int) $hospital['hospital_id'] ?>"><button class="btn btn-secondary btn-small" type="submit">Block</button></form><?php elseif ($hospital['status'] === 'blocked'): ?><form method="post" action="<?= e(appUrl('actions/admin_hospital.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="unblock"><input type="hidden" name="hospital_id" value="<?= (int) $hospital['hospital_id'] ?>"><button class="btn btn-secondary btn-small" type="submit">Unblock</button></form><?php endif; ?>
        <button class="btn btn-danger btn-small" type="button" data-open-modal="delete-hospital-<?= (int) $hospital['hospital_id'] ?>" aria-label="Delete <?= e($hospital['hospital_name']) ?>"><i class="bi bi-trash3" aria-hidden="true"></i></button></div></td></tr>
        <!-- Destructive actions require a second confirmation. -->
        <dialog class="admin-dialog" id="delete-hospital-<?= (int) $hospital['hospital_id'] ?>"><form method="post" action="<?= e(appUrl('actions/admin_hospital.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="hospital_id" value="<?= (int) $hospital['hospital_id'] ?>"><div class="dialog-icon danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div><h2>Delete hospital?</h2><p>This will remove <?= e($hospital['hospital_name']) ?> from the facility directory.</p><div class="admin-form-actions"><button class="btn btn-danger" type="submit">Delete hospital</button><button class="btn btn-secondary" type="button" data-close-modal>Cancel</button></div></form></dialog>
    <?php endforeach; ?>
    <?php if (!$hospitals): ?><tr><td colspan="6" class="empty-table-cell">No hospitals have registered.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
