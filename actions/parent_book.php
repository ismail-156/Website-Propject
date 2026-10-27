<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
require_parent_post();

// Validate identifiers and visit fields before checking their parent-owned relationships.
$childId = filter_input(INPUT_POST, 'child_id', FILTER_VALIDATE_INT) ?: 0;
$vaccineId = filter_input(INPUT_POST, 'vaccine_id', FILTER_VALIDATE_INT) ?: 0;
$hospitalId = filter_input(INPUT_POST, 'hospital_id', FILTER_VALIDATE_INT) ?: 0;
$bookingDate = (string) ($_POST['booking_date'] ?? '');
$bookingTime = (string) ($_POST['booking_time'] ?? '');
$validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate);
$validTimes = ['09:00', '10:30', '12:00', '14:00', '15:30'];

if ($childId < 1 || $vaccineId < 1 || $hospitalId < 1 || !$validDate
    || $validDate->format('Y-m-d') !== $bookingDate || $validDate < new DateTimeImmutable('today')
    || !in_array($bookingTime, $validTimes, true)) {
    flash('error', 'Choose a valid child, vaccine, hospital, date, and available time.');
    redirect(parent_path('book.php'));
}

try {
    $pdo->beginTransaction();
    $scheduleStmt = $pdo->prepare(
        "SELECT cv.cv_id, cv.status, v.availability
         FROM child_vaccinations cv
         INNER JOIN children c ON c.id = cv.child_id
         INNER JOIN vaccines v ON v.id = cv.vaccine_id
         WHERE c.parent_id = :parent_id AND c.id = :child_id AND v.id = :vaccine_id
         FOR UPDATE"
    );
    $scheduleStmt->execute(['parent_id' => $pid, 'child_id' => $childId, 'vaccine_id' => $vaccineId]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$schedule || !in_array($schedule['status'], ['pending', 'missed'], true)
        || strtolower((string) $schedule['availability']) !== 'available') {
        $pdo->rollBack();
        flash('error', 'That vaccine is no longer available to book for this child.');
        redirect(parent_path('schedule.php') . '?child_id=' . $childId);
    }

    $hospitalCheck = $pdo->prepare(
        "SELECT h.id FROM hospitals h
         INNER JOIN users u ON u.id = h.user_id
         WHERE h.id = :hospital_id AND u.status = 'active' LIMIT 1"
    );
    $hospitalCheck->execute(['hospital_id' => $hospitalId]);
    if (!$hospitalCheck->fetchColumn()) {
        $pdo->rollBack();
        flash('error', 'The selected hospital is no longer available.');
        redirect(parent_path('book.php') . '?child_id=' . $childId . '&vaccine_id=' . $vaccineId);
    }

    $stockCheck = $pdo->prepare(
        "SELECT COALESCE(
            (SELECT hv.in_stock FROM hospital_vaccines hv
             WHERE hv.hospital_id = :hospital_id AND hv.vaccine_id = :vaccine_id),
            'yes'
         )"
    );
    $stockCheck->execute(['hospital_id' => $hospitalId, 'vaccine_id' => $vaccineId]);
    if ($stockCheck->fetchColumn() !== 'yes') {
        $pdo->rollBack();
        flash('error', 'This hospital has marked that vaccine unavailable. Choose another facility.');
        redirect(parent_path('book.php') . '?child_id=' . $childId . '&vaccine_id=' . $vaccineId);
    }

    $duplicateStmt = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE parent_id = :parent_id AND child_id = :child_id AND vaccine_id = :vaccine_id
           AND status IN ('pending', 'approved') LIMIT 1"
    );
    $duplicateStmt->execute(['parent_id' => $pid, 'child_id' => $childId, 'vaccine_id' => $vaccineId]);
    if ($duplicateStmt->fetchColumn()) {
        $pdo->rollBack();
        flash('error', 'There is already an active booking request for this vaccine.');
        redirect(parent_path('my_bookings.php'));
    }

    $bookingStmt = $pdo->prepare(
        "INSERT INTO bookings (parent_id, child_id, vaccine_id, hospital_id, booking_date, booking_time, status)
         VALUES (:parent_id, :child_id, :vaccine_id, :hospital_id, :booking_date, :booking_time, 'pending')"
    );
    $bookingStmt->execute([
        'parent_id' => $pid,
        'child_id' => $childId,
        'vaccine_id' => $vaccineId,
        'hospital_id' => $hospitalId,
        'booking_date' => $bookingDate,
        'booking_time' => $bookingTime,
    ]);
    $updateSchedule = $pdo->prepare(
        "UPDATE child_vaccinations SET status = 'booked' WHERE cv_id = :cv_id"
    );
    $updateSchedule->execute(['cv_id' => (int) $schedule['cv_id']]);
    $pdo->commit();
    flash('success', 'Your vaccination visit request has been sent to the hospital.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Parent booking create failed: ' . $exception->getMessage());
    flash('error', 'Unable to send this booking request right now. Please try again.');
    redirect(parent_path('book.php') . '?child_id=' . $childId . '&vaccine_id=' . $vaccineId);
}
redirect(parent_path('my_bookings.php'));
