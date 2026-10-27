<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
require_parent_post();

// Validate child fields before starting any database changes.
$action = (string) ($_POST['action'] ?? '');
$childId = filter_input(INPUT_POST, 'child_id', FILTER_VALIDATE_INT) ?: 0;
if ($action === 'delete') {
    if ($childId < 1) {
        flash('error', 'Choose a valid child profile.');
        redirect(parent_path('children.php'));
    }

    try {
        $pdo->beginTransaction();
        $owned = $pdo->prepare('SELECT id FROM children WHERE id = :child_id AND parent_id = :parent_id FOR UPDATE');
        $owned->execute(['child_id' => $childId, 'parent_id' => $pid]);
        if (!$owned->fetchColumn()) {
            $pdo->rollBack();
            flash('error', 'That child profile could not be found.');
            redirect(parent_path('children.php'));
        }

        $deleteBookings = $pdo->prepare('DELETE FROM bookings WHERE parent_id = :parent_id AND child_id = :child_id');
        $deleteBookings->execute(['parent_id' => $pid, 'child_id' => $childId]);
        $deleteSchedules = $pdo->prepare('DELETE FROM child_vaccinations WHERE child_id = :child_id');
        $deleteSchedules->execute(['child_id' => $childId]);
        $deleteChild = $pdo->prepare('DELETE FROM children WHERE id = :child_id AND parent_id = :parent_id');
        $deleteChild->execute(['child_id' => $childId, 'parent_id' => $pid]);
        $pdo->commit();
        flash('success', 'Child profile and associated vaccination records were deleted.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Parent child deletion failed: ' . $exception->getMessage());
        flash('error', 'Unable to delete this profile right now. Please try again.');
    }
    redirect(parent_path('children.php'));
}

$name = trim((string) ($_POST['name'] ?? ''));
$dob = (string) ($_POST['dob'] ?? '');
$gender = strtolower((string) ($_POST['gender'] ?? ''));
$bloodGroup = (string) ($_POST['blood_group'] ?? '');
$validDob = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
$validBloodGroups = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || !$validDob || $validDob->format('Y-m-d') !== $dob
    || $validDob > new DateTimeImmutable('today') || !in_array($gender, ['male', 'female'], true)
    || !in_array($bloodGroup, $validBloodGroups, true)) {
    flash('error', 'Check the child’s name, date of birth, gender, and blood group.');
    redirect($action === 'edit' && $childId > 0 ? parent_path('edit_child.php') . '?child_id=' . $childId : parent_path('add_child.php'));
}

try {
    $pdo->beginTransaction();
    if ($action === 'add') {
        $insertChild = $pdo->prepare(
            'INSERT INTO children (parent_id, name, dob, gender, blood_group) VALUES (:parent_id, :name, :dob, :gender, :blood_group)'
        );
        $insertChild->execute([
            'parent_id' => $pid,
            'name' => $name,
            'dob' => $dob,
            'gender' => $gender,
            'blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
        ]);
        $childId = (int) $pdo->lastInsertId();
    } elseif ($action === 'edit' && $childId > 0) {
        $updateChild = $pdo->prepare(
            'UPDATE children SET name = :name, dob = :dob, gender = :gender, blood_group = :blood_group
             WHERE id = :child_id AND parent_id = :parent_id'
        );
        $updateChild->execute([
            'name' => $name,
            'dob' => $dob,
            'gender' => $gender,
            'blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
            'child_id' => $childId,
            'parent_id' => $pid,
        ]);
        if ($updateChild->rowCount() === 0) {
            $verify = $pdo->prepare('SELECT id FROM children WHERE id = :child_id AND parent_id = :parent_id');
            $verify->execute(['child_id' => $childId, 'parent_id' => $pid]);
            if (!$verify->fetchColumn()) {
                $pdo->rollBack();
                http_response_code(404);
                exit('Child profile not found.');
            }
        }
        $updateSchedule = $pdo->prepare(
            "UPDATE child_vaccinations cv INNER JOIN vaccines v ON v.id = cv.vaccine_id
             SET cv.due_date = DATE_ADD(:dob, INTERVAL v.recommended_age_days DAY)
             WHERE cv.child_id = :child_id AND cv.status IN ('pending', 'missed')"
        );
        $updateSchedule->execute(['dob' => $dob, 'child_id' => $childId]);
    } else {
        $pdo->rollBack();
        flash('error', 'The requested child action is not valid.');
        redirect(parent_path('children.php'));
    }

    // Existing schedule entries are preserved; missing vaccine recommendations are added.
    $scheduleInsert = $pdo->prepare(
        "INSERT INTO child_vaccinations (child_id, vaccine_id, due_date, status)
         SELECT :child_id, v.id, DATE_ADD(:dob, INTERVAL v.recommended_age_days DAY), 'pending'
         FROM vaccines v
         WHERE NOT EXISTS (
             SELECT 1 FROM child_vaccinations cv
             WHERE cv.child_id = :existing_child AND cv.vaccine_id = v.id
         )"
    );
    $scheduleInsert->execute(['child_id' => $childId, 'dob' => $dob, 'existing_child' => $childId]);
    $pdo->commit();
    flash('success', $action === 'add' ? 'Child profile created and a vaccination schedule prepared.' : 'Child profile updated.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Parent child save failed: ' . $exception->getMessage());
    flash('error', 'Unable to save this child profile right now. Please try again.');
    redirect($action === 'edit' && $childId > 0 ? parent_path('edit_child.php') . '?child_id=' . $childId : parent_path('add_child.php'));
}
redirect(parent_path('children.php'));
