<?php
// Authenticate and apply booking status, text search, and pagination filters.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'bookings';
$pageTitle = 'All bookings';
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'all');
$validStatuses = ['pending', 'approved', 'rejected', 'vaccinated', 'not_vaccinated'];
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$where = [];
$params = [];
if ($statusFilter !== 'all') { $where[] = 'b.status = :status'; $params['status'] = $statusFilter; }
if ($search !== '') {
    $where[] = '(c.name LIKE :child_search OR pu.name LIKE :parent_search OR v.name LIKE :vaccine_search OR h.hospital_name LIKE :hospital_search)';
    $searchValue = '%' . $search . '%';
    $params['child_search'] = $searchValue;
    $params['parent_search'] = $searchValue;
    $params['vaccine_search'] = $searchValue;
    $params['hospital_search'] = $searchValue;
}
$baseSql = ' FROM bookings b INNER JOIN children c ON c.id = b.child_id INNER JOIN parents p ON p.id = b.parent_id INNER JOIN users pu ON pu.id = p.user_id INNER JOIN vaccines v ON v.id = b.vaccine_id INNER JOIN hospitals h ON h.id = b.hospital_id';
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$bookingStmt = $pdo->prepare('SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.status, b.admin_remark, c.name AS child_name, pu.name AS parent_name, v.name AS vaccine_name, h.hospital_name' . $baseSql . $whereSql . ' ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) $bookingStmt->bindValue(':' . $key, $value);
$bookingStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$bookingStmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$bookingStmt->execute();
$bookings = $bookingStmt->fetchAll();
$pageUrl = appUrl('admin/bookings.php') . '?' . http_build_query(['q' => $search, 'status' => $statusFilter]);
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Searchable booking ledger includes every workflow status. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Appointments</span><h1>All <span class="highlight">Bookings</span><span class="highlight alt">.</span></h1><p>Search the appointment ledger and review current outcomes.</p></div><span class="admin-total-chip"><?= number_format($total) ?> records</span></section>
<section class="admin-panel">
    <!-- GET filters remain composable and retain values across pagination. -->
    <form class="admin-toolbar" method="get"><label class="admin-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search child, parent, vaccine, hospital" aria-label="Search bookings"></label><label class="admin-select"><span class="visually-hidden">Status</span><select name="status"><option value="all">All statuses</option><?php foreach ($validStatuses as $status): ?><option value="<?= e($status) ?>"<?= $statusFilter === $status ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $status))) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary btn-small" type="submit">Apply</button></form>
    <!-- Booking rows stay readable and horizontally scroll on small screens. -->
    <div class="table-responsive"><table class="admin-table"><thead><tr><th>Date</th><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Status</th><th>Remark</th></tr></thead><tbody>
        <?php foreach ($bookings as $booking): ?><tr><td><?= e(date('M j, Y', strtotime($booking['booking_date']))) ?><span class="table-subline"><?= e($booking['booking_time']) ?></span></td><td><strong><?= e($booking['child_name']) ?></strong></td><td><?= e($booking['parent_name']) ?></td><td><?= e($booking['vaccine_name']) ?></td><td><?= e($booking['hospital_name']) ?></td><td><span class="status-badge <?= e(admin_status_class($booking['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $booking['status']))) ?></span></td><td><?= e($booking['admin_remark'] ?: '—') ?></td></tr><?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="7" class="empty-table-cell">No bookings match the selected filters.</td></tr><?php endif; ?>
    </tbody></table></div>
    <!-- Numbered pages keep the current search and status filter. -->
    <?php admin_pagination($page, $pages, $pageUrl); ?>
</section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>