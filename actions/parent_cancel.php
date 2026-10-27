<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
require_parent_post();
$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT) ?: 0;

// The lock and update predicates both enforce booking ownership and pending-only cancellation.
try {
    $pdo->beginTransaction();
    $bookingStmt = $pdo->prepare(
        "SELECT id, child_id, vaccine_id FROM bookings
         WHERE id = :booking_id AND parent_id = :parent_id AND status = 'pending'
         FOR UPDATE"
    );
    $bookingStmt->execute(['booking_id' => $bookingId, 'parent_id' => $pid]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        $pdo->rollBack();
        flash('error', 'This booking cannot be cancelled. It may already have been reviewed.');
        redirect(parent_path('my_bookings.php'));
    }

    $cancelStmt = $pdo->prepare(
        "UPDATE bookings SET status = 'rejected', admin_remark = 'Cancelled by parent'
         WHERE id = :booking_id AND parent_id = :parent_id AND status = 'pending'"
    );
    $cancelStmt->execute(['booking_id' => $bookingId, 'parent_id' => $pid]);
    $scheduleStmt = $pdo->prepare(
        "UPDATE child_vaccinations SET status = IF(due_date < CURDATE(), 'missed', 'pending')
         WHERE child_id = :child_id AND vaccine_id = :vaccine_id AND status = 'booked'"
    );
    $scheduleStmt->execute(['child_id' => (int) $booking['child_id'], 'vaccine_id' => (int) $booking['vaccine_id']]);
    $pdo->commit();
    flash('success', 'Your booking request has been cancelled.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Parent booking cancellation failed: ' . $exception->getMessage());
    flash('error', 'Unable to cancel this booking right now. Please try again.');
}
redirect(parent_path('my_bookings.php'));
