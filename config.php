<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_NAME = 'Vaccination Management System';
const DB_HOST = 'localhost';
const DB_NAME = 'vaccination_management';
const DB_USER = 'root';
const DB_PASS = '';

function appUrl(string $path = ''): string
{
    $scriptDirectory = dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/'));

    if (in_array(basename($scriptDirectory), ['admin', 'parent', 'hospital', 'actions'], true)) {
        $scriptDirectory = dirname($scriptDirectory);
    }

    $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');

    return $basePath . '/' . ltrim($path, '/');
}

function flash(?string $key = null, ?string $message = null): mixed
{
    if ($key !== null && $message !== null) {
        $_SESSION['flash'][$key] = $message;
        return $message;
    }

    if ($key === null) {
        if (empty($_SESSION['flash'])) {
            return null;
        }

        $type = (string) array_key_first($_SESSION['flash']);
        $message = (string) $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);

        if (empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }

        return ['type' => $type === 'error' ? 'danger' : $type, 'msg' => $message];
    }

    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    return null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function userRoleLabel(string $role): string
{
    $labels = [
        'admin' => 'Administrator',
        'parent' => 'Parent',
        'hospital' => 'Hospital',
    ];

    return $labels[$role] ?? ucfirst($role);
}

function convertAgeDaysToText(int $days): string
{
    if ($days < 30) {
        return $days . ' days';
    }

    if ($days < 365) {
        $weeks = round($days / 7);
        return $weeks . ' weeks';
    }

    $totalMonths = (int) round($days / 30.4375);
    $years = intdiv($totalMonths, 12);
    $months = $totalMonths % 12;
    $parts = [];
    if ($years > 0) $parts[] = $years . ' year' . ($years === 1 ? '' : 's');
    if ($months > 0) $parts[] = $months . ' month' . ($months === 1 ? '' : 's');
    return implode(' ', $parts);
}

function ensureCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireCsrfToken(): void
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        flash('error', 'Invalid request. Please try again.');
        redirect('login.php');
    }
}
