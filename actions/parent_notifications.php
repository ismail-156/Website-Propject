<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
get_parent_id($pdo, (int) $_SESSION['user_id']);
require_parent_post();

// Only mark notifications owned by the signed-in user's account as read.
$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
$stmt->execute(['user_id' => (int) $_SESSION['user_id']]);
flash('success', 'Your notifications are marked as read.');
redirect(parent_path('dashboard.php'));
