<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Require a valid CSRF token before accepting hospital management actions.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash('error', 'Your session expired or the request was invalid. Please try again.');
    redirect(appUrl('admin/hospitals.php'));
}
$action = (string) ($_POST['action'] ?? '');
$hospitalId = filter_var($_POST['hospital_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

// Validate profile details before creating or updating a hospital.
if ($action === 'save') {
    $isEdit = array_key_exists('hospital_id', $_POST);
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $formUrl = appUrl('admin/hospital_form.php') . ($hospitalId > 0 ? '?id=' . $hospitalId : '');

    if (($isEdit && $hospitalId < 1) || $name === '' || strlen($name) > 150
        || $address === '' || strlen($address) > 255 || strlen($city) > 100
        || strlen($location) > 150 || strlen($phone) > 20) {
        flash('error', 'Enter valid hospital details. Hospital name and address are required, and each field must fit its allowed length.');
        redirect($formUrl);
    }

    if (!$isEdit) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid account email address.');
            redirect($formUrl);
        }
        if (strlen($password) < 8) {
            flash('error', 'The temporary password must be at least 8 characters.');
            redirect($formUrl);
        }
    }

    try {
        if ($isEdit) {
            $existsStmt = $pdo->prepare(
                "SELECT h.id FROM hospitals h
                 INNER JOIN users u ON u.id = h.user_id
                 WHERE h.id = :hospital_id AND u.role = 'hospital' LIMIT 1"
            );
            $existsStmt->execute(['hospital_id' => $hospitalId]);
            if (!$existsStmt->fetchColumn()) {
                flash('error', 'That hospital could not be found.');
                redirect(appUrl('admin/hospitals.php'));
            }
            $updateStmt = $pdo->prepare(
                'UPDATE hospitals SET hospital_name = :name, address = :address, city = :city, location = :location, phone = :phone WHERE id = :hospital_id'
            );
            $updateStmt->execute([
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'location' => $location,
                'phone' => $phone,
                'hospital_id' => $hospitalId,
            ]);
        } else {
            $pdo->beginTransaction();
            $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $emailStmt->execute(['email' => $email]);
            if ($emailStmt->fetchColumn()) {
                $pdo->rollBack();
                flash('error', 'Unable to create this account.');
                redirect($formUrl);
            }
            $userStmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role, status)
                 VALUES (:name, :email, :password, 'hospital', 'active')"
            );
            $userStmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $newUserId = (int) $pdo->lastInsertId();
            $hospitalStmt = $pdo->prepare(
                'INSERT INTO hospitals (user_id, hospital_name, address, city, location, phone) VALUES (:user_id, :name, :address, :city, :location, :phone)'
            );
            $hospitalStmt->execute([
                'user_id' => $newUserId,
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'location' => $location,
                'phone' => $phone,
            ]);
            $pdo->commit();
        }
        flash('success', 'Hospital saved.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Admin hospital save failed: ' . $exception->getMessage());
        flash('error', $isEdit ? 'Unable to save this hospital right now. Please try again.' : 'Unable to create this account.');
        redirect($formUrl);
    }
    redirect(appUrl('admin/hospitals.php'));
}

// Change access only when the hospital account has the expected current status.
if ($action === 'block' || $action === 'unblock') {
    if ($hospitalId < 1) {
        flash('error', 'Choose a valid hospital.');
        redirect(appUrl('admin/hospitals.php'));
    }
    $expectedStatus = $action === 'block' ? 'active' : 'blocked';
    $newStatus = $action === 'block' ? 'blocked' : 'active';
    try {
        $accountStmt = $pdo->prepare(
            'SELECT h.user_id, u.role, u.status FROM hospitals h INNER JOIN users u ON u.id = h.user_id WHERE h.id = :hospital_id LIMIT 1'
        );
        $accountStmt->execute(['hospital_id' => $hospitalId]);
        $account = $accountStmt->fetch();
        if (!$account || $account['role'] !== 'hospital' || $account['status'] !== $expectedStatus) {
            flash('error', 'This hospital could not be found or is not in the expected status.');
            redirect(appUrl('admin/hospitals.php'));
        }
        $statusStmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :user_id AND role = :role AND status = :expected_status');
        $statusStmt->execute([
            'status' => $newStatus,
            'user_id' => $account['user_id'],
            'role' => 'hospital',
            'expected_status' => $expectedStatus,
        ]);
        if ($statusStmt->rowCount() !== 1) {
            flash('error', 'This hospital could not be found or is not in the expected status.');
            redirect(appUrl('admin/hospitals.php'));
        }
    } catch (PDOException $exception) {
        error_log('Admin hospital status update failed: ' . $exception->getMessage());
        flash('error', 'Unable to update this hospital right now. Please try again.');
        redirect(appUrl('admin/hospitals.php'));
    }
    flash('success', $action === 'block' ? 'Hospital account blocked.' : 'Hospital account unblocked.');
    redirect(appUrl('admin/hospitals.php'));
}

// Delete the hospital account so its related records follow the schema cascades.
if ($action === 'delete') {
    if ($hospitalId < 1) {
        flash('error', 'Choose a valid hospital.');
        redirect(appUrl('admin/hospitals.php'));
    }
    try {
        $pdo->beginTransaction();
        $accountStmt = $pdo->prepare(
            "SELECT h.user_id FROM hospitals h
             INNER JOIN users u ON u.id = h.user_id
             WHERE h.id = :hospital_id AND u.role = 'hospital' FOR UPDATE"
        );
        $accountStmt->execute(['hospital_id' => $hospitalId]);
        $deleteUserId = $accountStmt->fetchColumn();
        if (!$deleteUserId) {
            $pdo->rollBack();
            flash('error', 'That hospital could not be found.');
            redirect(appUrl('admin/hospitals.php'));
        }
        $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id AND role = :role');
        $deleteStmt->execute(['user_id' => $deleteUserId, 'role' => 'hospital']);
        $pdo->commit();
        flash('success', 'Hospital deleted.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Admin hospital delete failed: ' . $exception->getMessage());
        flash('error', 'Unable to delete this hospital right now. Please try again.');
    }
    redirect(appUrl('admin/hospitals.php'));
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
if ($userId < 1 || !in_array($action, ['approve', 'reject'], true)) {
    flash('error', 'Choose a valid pending hospital application.');
    redirect(appUrl('admin/hospitals.php'));
}
$newStatus = $action === 'approve' ? 'active' : 'blocked';

try {
    $pdo->beginTransaction();
    $application = $pdo->prepare(
        "SELECT u.id FROM users u
         INNER JOIN hospitals h ON h.user_id = u.id
         WHERE u.id = :user_id AND u.role = 'hospital' AND u.status = 'pending'
         FOR UPDATE"
    );
    $application->execute(['user_id' => $userId]);
    if (!$application->fetchColumn()) {
        $pdo->rollBack();
        flash('error', 'This application has already been reviewed.');
        redirect(appUrl('admin/hospitals.php'));
    }
    $update = $pdo->prepare('UPDATE users SET status = :status WHERE id = :user_id AND role = :role');
    $update->execute(['status' => $newStatus, 'user_id' => $userId, 'role' => 'hospital']);
    $message = $action === 'approve'
        ? 'Your hospital account has been approved. You can now sign in.'
        : 'Your hospital registration was not approved. Please contact the administrator for more information.';
    $notice = $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)');
    $notice->execute(['user_id' => $userId, 'message' => $message]);
    $pdo->commit();
    flash('success', $action === 'approve' ? 'Hospital account approved.' : 'Hospital application declined.');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin hospital review failed: ' . $exception->getMessage());
    flash('error', 'Unable to review this hospital application right now. Please try again.');
}
redirect(appUrl('admin/hospitals.php'));
