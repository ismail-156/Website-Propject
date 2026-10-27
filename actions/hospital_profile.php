<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
require_hospital_post();
$action = (string) ($_POST['action'] ?? '');
$userId = (int) $_SESSION['user_id'];

// Save facility contact information only on the signed-in hospital and user records.
if ($action === 'profile') {
    $name = trim((string) ($_POST['hospital_name'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    if ($name === '' || mb_strlen($name, 'UTF-8') > 150 || $address === '' || mb_strlen($address, 'UTF-8') > 255
        || $city === '' || mb_strlen($city, 'UTF-8') > 100 || $location === '' || mb_strlen($location, 'UTF-8') > 150
        || !preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        flash('error', 'Check the facility name, address, city, location, and phone number.');
        redirect(hospital_path('profile.php'));
    }
    try {
        $pdo->beginTransaction();
        $hospitalUpdate = $pdo->prepare(
            'UPDATE hospitals SET hospital_name = :name, address = :address, city = :city,
             location = :location, phone = :phone WHERE id = :hospital_id AND user_id = :user_id'
        );
        $hospitalUpdate->execute([
            'name' => $name, 'address' => $address, 'city' => $city, 'location' => $location,
            'phone' => $phone, 'hospital_id' => $hid, 'user_id' => $userId,
        ]);
        $userUpdate = $pdo->prepare('UPDATE users SET name = :name WHERE id = :user_id AND role = :role');
        $userUpdate->execute(['name' => $name, 'user_id' => $userId, 'role' => 'hospital']);
        $pdo->commit();
        flash('success', 'Hospital profile updated.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Hospital profile update failed: ' . $exception->getMessage());
        flash('error', 'Unable to update your hospital profile right now. Please try again.');
    }
    redirect(hospital_path('profile.php'));
}

// Password changes check the current password and confirmation before saving a new hash.
if ($action === 'password') {
    $oldPassword = (string) ($_POST['old_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordStmt = $pdo->prepare('SELECT password FROM users WHERE id = :user_id AND role = :role LIMIT 1');
    $passwordStmt->execute(['user_id' => $userId, 'role' => 'hospital']);
    $storedHash = $passwordStmt->fetchColumn();
    if (!is_string($storedHash) || !password_verify($oldPassword, $storedHash)) {
        flash('error', 'Your current password is incorrect.');
    } elseif (strlen($newPassword) < 8) {
        flash('error', 'Your new password must be at least 8 characters long.');
    } elseif ($newPassword !== $confirmPassword) {
        flash('error', 'The new password and confirmation do not match.');
    } else {
        $updatePassword = $pdo->prepare('UPDATE users SET password = :password WHERE id = :user_id AND role = :role');
        $updatePassword->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'user_id' => $userId,
            'role' => 'hospital',
        ]);
        flash('success', 'Your password has been updated.');
    }
    redirect(hospital_path('profile.php'));
}

flash('error', 'That profile action is not valid.');
redirect(hospital_path('profile.php'));
