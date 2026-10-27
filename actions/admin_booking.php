<?php
// Require an authenticated administrator before reviewing booking requests.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Reject non-POST and invalid-CSRF requests before reading submitted values.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash('error', 'Your session expired or the request was invalid. Please try again.');
    redirect(appUrl('admin/requests.php'));
}

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT) ?: 0;
$decision = (string) ($_POST['decision'] ?? '');
$remark = trim((string) ($_POST['remark'] ?? ''));
if ($bookingId < 1 || !in_array($decision, ['approve', 'reject'], true)
    || ($decision === 'reject' && ($remark === '' || strlen($remark) > 2000))) {
    flash('error', 'Choose a pending request and provide a remark when rejecting it.');
    redirect(appUrl('admin/requests.php'));
}

// Lock the pending request so duplicate submissions cannot apply conflicting decisions.
try {
    $pdo->beginTransaction();
    $bookingStmt = $pdo->prepare(
        'SELECT id, parent_id, child_id, vaccine_id, status FROM bookings WHERE id = :id FOR UPDATE'
    );
    $bookingStmt->execute(['id' => $bookingId]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking || $booking['status'] !== 'pending') {
        $pdo->rollBack();
        flash('error', 'This booking request has already been reviewed.');
        redirect(appUrl('admin/requests.php'));
    }

    $newStatus = $decision === 'approve' ? 'approved' : 'rejected';
    $updateStmt = $pdo->prepare(
        'UPDATE bookings SET status = :status, admin_remark = :remark WHERE id = :id AND status = \'pending\''
    );
    $updateStmt->execute([
        'status' => $newStatus,
        'remark' => $decision === 'reject' ? $remark : null,
        'id' => $bookingId,
    ]);

    // Release the scheduled dose after rejection so the parent can request another visit.
    if ($decision === 'reject') {
        $scheduleStmt = $pdo->prepare(
            "UPDATE child_vaccinations SET status = IF(due_date < CURDATE(), 'missed', 'pending')
             WHERE child_id = :child_id AND vaccine_id = :vaccine_id AND status = 'booked'"
        );
        $scheduleStmt->execute(['child_id' => (int) $booking['child_id'], 'vaccine_id' => (int) $booking['vaccine_id']]);
    }

    // Notify the parent with the decision and include the administrator's remark when present.
    $parentUserStmt = $pdo->prepare('SELECT user_id FROM parents WHERE id = :parent_id LIMIT 1');
    $parentUserStmt->execute(['parent_id' => (int) $booking['parent_id']]);
    $parentUserId = $parentUserStmt->fetchColumn();
    if ($parentUserId !== false) {
        $message = $decision === 'approve'
            ? 'Your vaccination appointment request has been approved.'
            : 'Your vaccination appointment request was declined. Remark: ' . $remark;
        $noticeStmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)');
        $noticeStmt->execute(['user_id' => (int) $parentUserId, 'message' => $message]);
    }

    $pdo->commit();
    flash('success', $decision === 'approve' ? 'Booking request approved.' : 'Booking request rejected.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin booking review failed: ' . $exception->getMessage());
    flash('error', 'Unable to review this booking right now. Please try again.');
}
redirect(appUrl('admin/requests.php'));