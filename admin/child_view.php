<?php
// Authenticate and load a single child's profile without exposing other fields.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'children';
$pageTitle = 'Child profile';
$childId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$profileStmt = $pdo->prepare(
    'SELECT c.id AS child_id, c.name, c.dob, c.gender, c.blood_group, pu.name AS parent_name, pu.email, p.phone, p.address, p.city
     FROM children c INNER JOIN parents p ON p.id = c.parent_id INNER JOIN users pu ON pu.id = p.user_id
     WHERE c.id = :child_id LIMIT 1'
);
$profileStmt->execute(['child_id' => $childId]);
$child = $profileStmt->fetch();
if (!$child) { http_response_code(404); }
$schedule = [];
if ($child) {
    $scheduleStmt = $pdo->prepare(
        'SELECT cv.cv_id, cv.due_date, cv.status, cv.vaccinated_on, v.name AS vaccine_name, v.disease_prevented
         FROM child_vaccinations cv INNER JOIN vaccines v ON v.id = cv.vaccine_id
         WHERE cv.child_id = :child_id ORDER BY cv.due_date, v.name'
    );
    $scheduleStmt->execute(['child_id' => $childId]);
    $schedule = $scheduleStmt->fetchAll();
}
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Profile summary brings together child and parent contact details. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Child record</span><h1>Profile <span class="highlight">Details</span><span class="highlight alt">.</span></h1><p>Review contact information and every scheduled dose.</p></div><a class="btn btn-secondary" href="<?= e(appUrl('admin/children.php')) ?>"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Children</a></section>
<?php if ($child): ?>
    <!-- Profile header card keeps demographic and parent data readable. -->
    <section class="admin-panel child-profile"><div class="child-profile-avatar"><i class="bi bi-person-heart" aria-hidden="true"></i></div><div class="child-profile-main"><span class="admin-eyebrow">Child</span><h2><?= e($child['name']) ?></h2><p><?= e(ucfirst($child['gender'])) ?> · <?= e($child['blood_group'] ?: 'Blood group not set') ?> · Born <?= e(date('M j, Y', strtotime($child['dob']))) ?></p></div><div class="profile-contact"><span>Parent</span><strong><?= e($child['parent_name']) ?></strong><span><?= e($child['phone']) ?></span><span><?= e($child['email']) ?></span></div></section>
    <!-- Full schedule records use the shared pastel status palette. -->
    <section class="admin-panel mt-4"><div class="panel-heading"><div><span class="admin-eyebrow">Immunization</span><h2>Vaccination schedule</h2></div><span class="admin-total-chip"><?= count($schedule) ?> doses</span></div>
        <div class="table-responsive"><table class="admin-table"><thead><tr><th>Vaccine</th><th>Protects against</th><th>Due date</th><th>Vaccinated on</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($schedule as $dose): ?><tr><td><strong><?= e($dose['vaccine_name']) ?></strong></td><td><?= e($dose['disease_prevented']) ?></td><td><?= e(date('M j, Y', strtotime($dose['due_date']))) ?></td><td><?= $dose['vaccinated_on'] ? e(date('M j, Y', strtotime($dose['vaccinated_on']))) : '—' ?></td><td><span class="status-badge <?= e(admin_status_class($dose['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $dose['status']))) ?></span></td></tr><?php endforeach; ?>
            <?php if (!$schedule): ?><tr><td class="empty-table-cell" colspan="5">No vaccination schedule is recorded for this child.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
<?php else: ?><section class="admin-panel admin-empty-state"><span class="empty-art"><i class="bi bi-person-exclamation" aria-hidden="true"></i></span><h2>Child not found</h2><p>This profile may have been removed or the link is invalid.</p></section><?php endif; ?>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>