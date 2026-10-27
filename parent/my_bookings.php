<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'bookings';
$pageTitle = 'My bookings';

// Booking history is always scoped by parent_id and does not expose other families' visits.
$bookingsStmt = $pdo->prepare(
    "SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.status, b.admin_remark,
            c.name AS child_name, v.name AS vaccine_name,
            h.hospital_name AS hospital_name, h.city
     FROM bookings b
     INNER JOIN children c ON c.id = b.child_id AND c.parent_id = b.parent_id
     INNER JOIN vaccines v ON v.id = b.vaccine_id
     INNER JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.parent_id = :parent_id
     ORDER BY b.booking_date DESC, b.booking_time DESC"
);
$bookingsStmt->execute(['parent_id' => $pid]);
$bookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Booking history and cancel controls for requests not yet reviewed. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Visit history</span>
    <h1>My bookings</h1>
    <p>Review requests, confirmed appointments, and completed visits.</p>
</section>
<?php if ($bookings): ?>
    <section class="row g-3" aria-label="Bookings">
        <?php foreach ($bookings as $booking):
            $statusClass = strtolower((string) $booking['status']);
            $timeLabel = $booking['booking_time'] ? date('g:i A', strtotime($booking['booking_time'])) : '';
        ?>
            <div class="col-12">
                <article class="booking-row">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <h3><?= e($booking['vaccine_name']) ?></h3>
                            <span class="status-badge status-<?= e($statusClass) ?>"><?= e(ucwords(str_replace('_', ' ', $statusClass))) ?></span>
                        </div>
                        <p><i class="bi bi-person me-1" aria-hidden="true"></i><?= e($booking['child_name']) ?> · <i class="bi bi-hospital ms-1 me-1" aria-hidden="true"></i><?= e($booking['hospital_name']) ?>, <?= e($booking['city']) ?></p>
                        <p><i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= e(date('M j, Y', strtotime($booking['booking_date']))) ?><?= $timeLabel ? ' · ' . e($timeLabel) : '' ?></p>
                        <?php if ($statusClass === 'rejected' && $booking['admin_remark']): ?>
                            <div class="booking-remark"><strong>Hospital note:</strong> <?= e($booking['admin_remark']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($statusClass === 'pending'): ?>
                        <form method="post" action="<?= e(parent_action('parent_cancel.php')) ?>" data-confirm-form="Cancel this vaccination booking request?">
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['booking_id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <button class="parent-btn-secondary parent-btn-small btn" type="submit">Cancel</button>
                        </form>
                    <?php endif; ?>
                </article>
            </div>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="parent-card empty-state">
        <span class="empty-art"><i class="bi bi-journal-check" aria-hidden="true"></i></span>
        <h3>No bookings yet</h3>
        <p>When you book a vaccine, the visit details and approval status will appear here.</p>
        <a class="btn btn-primary" href="<?= e(parent_path('book.php')) ?>">Book a vaccine</a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
