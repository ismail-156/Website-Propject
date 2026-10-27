<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
require_parent_post();
$userId = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');

// Profile changes update only the authenticated account and its resolved parent row.
if ($action === 'profile') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || $phone === '' || mb_strlen($phone, 'UTF-8') > 30
        || $address === '' || mb_strlen($address, 'UTF-8') > 255 || $city === '' || mb_strlen($city, 'UTF-8') > 120) {
        flash('error', 'Please provide a name, phone, address, and city within the allowed lengths.');
        redirect(parent_path('profile.php'));
    }

    try {
        $pdo->beginTransaction();
        $userStmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :user_id AND role = :role');
        $userStmt->execute(['name' => $name, 'user_id' => $userId, 'role' => 'parent']);
        $parentStmt = $pdo->prepare(
            'UPDATE parents SET phone = :phone, address = :address, city = :city
             WHERE id = :parent_id AND user_id = :user_id'
        );
        $parentStmt->execute([
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'parent_id' => $pid,
            'user_id' => $userId,
        ]);
        $pdo->commit();
        flash('success', 'Your contact details have been updated.');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Parent profile update failed: ' . $exception->getMessage());
        flash('error', 'Unable to update your profile right now. Please try again.');
    }
    redirect(parent_path('profile.php'));
}

// Password changes require the current password and verify the confirmation server-side.
if ($action === 'password') {
    $oldPassword = (string) ($_POST['old_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordStmt = $pdo->prepare('SELECT password FROM users WHERE id = :user_id AND role = :role LIMIT 1');
    $passwordStmt->execute(['user_id' => $userId, 'role' => 'parent']);
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
            'role' => 'parent',
        ]);
        flash('success', 'Your password has been updated.');
    }
    redirect(parent_path('profile.php'));
}

flash('error', 'That profile action is not valid.');
redirect(parent_path('profile.php'));
