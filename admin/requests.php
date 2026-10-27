<?php
// Authenticate and load the pending booking queue for review.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'requests';
$pageTitle = 'Booking requests';
$requestStmt = $pdo->query(
    "SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.created_at, c.name AS child_name,
            pu.name AS parent_name, p.phone, v.name AS vaccine_name, h.hospital_name, h.city
     FROM bookings b INNER JOIN children c ON c.id = b.child_id
     INNER JOIN parents p ON p.id = b.parent_id INNER JOIN users pu ON pu.id = p.user_id
     INNER JOIN vaccines v ON v.id = b.vaccine_id INNER JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.status = 'pending' ORDER BY b.created_at ASC"
);
$requests = $requestStmt->fetchAll();
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Review heading makes the number waiting visible at a glance. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Review queue</span><h1>Booking <span class="highlight">Requests</span><span class="highlight alt">.</span></h1><p>Approve a requested appointment or reject it with a clear remark.</p></div><span class="admin-total-chip"><i class="bi bi-inbox" aria-hidden="true"></i><?= count($requests) ?> awaiting review</span></section>
<!-- Request cards group child, parent, vaccine, hospital, and appointment details. -->
<?php if ($requests): ?><section class="row g-3" aria-label="Pending booking requests">
    <?php foreach ($requests as $request): ?><div class="col-12 col-xl-6"><article class="admin-panel request-card"><div class="request-card-heading"><div><span class="admin-eyebrow">Request #<?= (int) $request['booking_id'] ?></span><h2><?= e($request['child_name']) ?></h2></div><span class="status-badge status-pending">Pending</span></div><div class="request-details"><div><span>Parent</span><strong><?= e($request['parent_name']) ?></strong><small><?= e($request['phone']) ?></small></div><div><span>Vaccine</span><strong><?= e($request['vaccine_name']) ?></strong></div><div><span>Hospital</span><strong><?= e($request['hospital_name']) ?></strong><small><?= e($request['city']) ?></small></div><div><span>Appointment</span><strong><?= e(date('M j, Y', strtotime($request['booking_date']))) ?></strong><small><?= e($request['booking_time']) ?></small></div></div><div class="request-actions">
        <form method="post" action="<?= e(appUrl('actions/admin_booking.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="booking_id" value="<?= (int) $request['booking_id'] ?>"><input type="hidden" name="decision" value="approve"><button class="btn btn-primary btn-small" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Approve</button></form>
        <button class="btn btn-danger btn-small" type="button" data-open-modal="reject-<?= (int) $request['booking_id'] ?>"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Reject</button>
    </div></article></div>
    <!-- Rejection requires an administrator remark before submission. -->
    <dialog class="admin-dialog" id="reject-<?= (int) $request['booking_id'] ?>"><form method="post" action="<?= e(appUrl('actions/admin_booking.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="booking_id" value="<?= (int) $request['booking_id'] ?>"><input type="hidden" name="decision" value="reject"><div class="dialog-icon danger"><i class="bi bi-chat-square-text" aria-hidden="true"></i></div><h2>Reject booking request</h2><p>Add a short reason for <?= e($request['parent_name']) ?>.</p><label class="admin-field"><span>Remark</span><textarea name="remark" rows="4" maxlength="1000" required></textarea></label><div class="admin-form-actions"><button class="btn btn-danger" type="submit">Reject request</button><button class="btn btn-secondary" type="button" data-close-modal>Cancel</button></div></form></dialog>
    <?php endforeach; ?>
</section><?php else: ?><section class="admin-panel admin-empty-state"><span class="empty-art"><i class="bi bi-check2-circle" aria-hidden="true"></i></span><h2>Queue is clear</h2><p>New parent booking requests will appear here.</p></section><?php endif; ?>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>