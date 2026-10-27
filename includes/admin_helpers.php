<?php
// Return the signed-in administrator's display name for the shared top bar.
function admin_display_name(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id AND role = \'admin\' LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $name = $stmt->fetchColumn();

    return is_string($name) && $name !== '' ? $name : 'Administrator';
}

// Keep badge classes explicit so database status values cannot become arbitrary CSS classes.
function admin_status_class(string $status): string
{
    return match ($status) {
        'pending' => 'status-pending',
        'approved' => 'status-approved',
        'booked' => 'status-booked',
        'vaccinated', 'active', 'available', 'completed' => 'status-active',
        'rejected', 'missed', 'blocked', 'not_vaccinated', 'unavailable' => 'status-danger',
        default => 'status-neutral',
    };
}

// Render consistent numbered pagination for list views.
function admin_pagination(int $page, int $pages, string $baseUrl): void
{
    if ($pages < 2) return;
    echo '<nav class="admin-pagination" aria-label="Pagination">';
    for ($number = 1; $number <= $pages; $number++) {
        $url = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $number;
        echo '<a class="' . ($number === $page ? 'is-active' : '') . '" href="' . e($url) . '"' . ($number === $page ? ' aria-current="page"' : '') . '>' . $number . '</a>';
    }
    echo '</nav>';
}