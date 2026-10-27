<?php
require_once __DIR__ . '/db.php';

$pdo = db();

if (!$pdo instanceof PDO) {
    http_response_code(503);
    exit('The database is unavailable. Please try again later.');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return ensureCsrfToken();
}

function verify_csrf(): bool
{
    $submitted = $_POST['csrf'] ?? '';
    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $submitted);
}

function require_role(string $role): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (($_SESSION['user_role'] ?? '') !== $role || $userId < 1) {
        flash('error', 'Please sign in with an account that can access this page.');
        redirect(appUrl('login.php'));
    }

    $stmt = db()->prepare('SELECT role, status FROM users WHERE id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account || $account['role'] !== $role || $account['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        flash('error', 'Your account does not currently have access. Please sign in or contact the administrator.');
        redirect(appUrl('login.php'));
    }
}

function get_parent_id(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $parentId = $stmt->fetchColumn();

    if ($parentId === false) {
        http_response_code(403);
        exit('The parent profile for this account could not be found.');
    }

    return (int) $parentId;
}

function mark_missed(PDO $pdo): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId < 1) {
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE child_vaccinations cv
         INNER JOIN children c ON c.id = cv.child_id
         SET cv.status = 'missed'
         WHERE c.parent_id = (
             SELECT p.id FROM parents p WHERE p.user_id = :user_id LIMIT 1
         )
           AND cv.status = 'pending' AND cv.due_date < CURDATE()"
    );
    $stmt->execute(['user_id' => $userId]);
}

function parent_path(string $path): string
{
    return appUrl('parent/' . ltrim($path, '/'));
}

function parent_action(string $path): string
{
    return appUrl('actions/' . ltrim($path, '/'));
}

function require_parent_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
        flash('error', 'Your session expired or the request was invalid. Please try again.');
        redirect(parent_path('dashboard.php'));
    }
}

function parent_display_name(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $name = $stmt->fetchColumn();

    return is_string($name) && $name !== '' ? $name : 'Parent';
}

function get_hospital_id(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT id FROM hospitals WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $hospitalId = $stmt->fetchColumn();

    if ($hospitalId === false) {
        http_response_code(403);
        exit('The hospital profile for this account could not be found.');
    }

    return (int) $hospitalId;
}

function hospital_path(string $path): string
{
    return appUrl('hospital/' . ltrim($path, '/'));
}

function hospital_action(string $path): string
{
    return appUrl('actions/' . ltrim($path, '/'));
}

function require_hospital_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
        flash('error', 'Your session expired or the request was invalid. Please try again.');
        redirect(hospital_path('dashboard.php'));
    }
}

function hospital_display_name(PDO $pdo, int $hospitalId): string
{
    $stmt = $pdo->prepare('SELECT hospital_name FROM hospitals WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $hospitalId]);
    $name = $stmt->fetchColumn();

    return is_string($name) && $name !== '' ? $name : 'Hospital';
}
