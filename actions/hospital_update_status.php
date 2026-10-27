<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
require_hospital_post();
$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT) ?: 0;
$result = (string) ($_POST['result'] ?? '');
$allowedTransitions = [
    'approved' => ['vaccinated', 'not_vaccinated'],
];

// Lock and validate the booking state before applying a hospital-owned transition.
try {
    $pdo->beginTransaction();
    $bookingStmt = $pdo->prepare(
        'SELECT id, parent_id, child_id, vaccine_id, status FROM bookings
         WHERE id = :booking_id AND hospital_id = :hospital_id FOR UPDATE'
    );
    $bookingStmt->execute(['booking_id' => $bookingId, 'hospital_id' => $hid]);
    $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking || !in_array($result, $allowedTransitions[$booking['status']] ?? [], true)) {
        $pdo->rollBack();
        flash('error', 'That appointment has already changed or does not belong to your hospital.');
        redirect(hospital_path('appointments.php'));
    }

    $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :booking_id AND hospital_id = :hospital_id');
    $update->execute(['status' => $result, 'booking_id' => $bookingId, 'hospital_id' => $hid]);
    if ($result === 'vaccinated') {
        $schedule = $pdo->prepare(
            "UPDATE child_vaccinations SET status = 'vaccinated', vaccinated_on = CURDATE()
             WHERE child_id = :child_id AND vaccine_id = :vaccine_id"
        );
        $schedule->execute(['child_id' => (int) $booking['child_id'], 'vaccine_id' => (int) $booking['vaccine_id']]);
    } elseif ($result === 'not_vaccinated') {
        $schedule = $pdo->prepare(
            "UPDATE child_vaccinations SET status = IF(due_date < CURDATE(), 'missed', 'pending')
             WHERE child_id = :child_id AND vaccine_id = :vaccine_id AND status = 'booked'"
        );
        $schedule->execute(['child_id' => (int) $booking['child_id'], 'vaccine_id' => (int) $booking['vaccine_id']]);
    }

    $noticeText = match ($result) {
        'vaccinated' => 'Your child’s vaccination has been recorded as complete.',
        'not_vaccinated' => 'The hospital recorded that the vaccination was not given at this visit.',
    };
    $parentUserStmt = $pdo->prepare('SELECT user_id FROM parents WHERE id = :parent_id LIMIT 1');
    $parentUserStmt->execute(['parent_id' => (int) $booking['parent_id']]);
    $parentUserId = $parentUserStmt->fetchColumn();
    if ($parentUserId !== false) {
        $notificationStmt = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)');
        $notificationStmt->execute(['user_id' => (int) $parentUserId, 'message' => $noticeText]);
    }
    $pdo->commit();
    flash('success', 'Appointment status updated.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Hospital appointment update failed: ' . $exception->getMessage());
    flash('error', 'Unable to update this appointment right now. Please try again.');
}
redirect(hospital_path('appointments.php'));
