<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
$hospitalName = hospital_display_name($pdo, $hid);
$activeNav = 'appointments';
$pageTitle = 'Appointments';
$status = (string) ($_GET['status'] ?? 'all');
$dateFilter = (string) ($_GET['date'] ?? '');
$allowedStatuses = ['all', 'pending', 'approved', 'vaccinated', 'not_vaccinated'];
if (!in_array($status, $allowedStatuses, true)) $status = 'all';
$parsedDate = $dateFilter === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $dateFilter);
if ($dateFilter !== '' && (!$parsedDate || $parsedDate->format('Y-m-d') !== $dateFilter)) $dateFilter = '';

// Appointment rows join to the current schema and are restricted to this hospital.
$sql = "SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.status,
               c.name AS child_name, c.dob, p.phone AS parent_phone,
               u.name AS parent_name, v.name AS vaccine_name
        FROM bookings b
        INNER JOIN children c ON c.id = b.child_id
        INNER JOIN parents p ON p.id = b.parent_id
        INNER JOIN users u ON u.id = p.user_id
        INNER JOIN vaccines v ON v.id = b.vaccine_id
                WHERE b.hospital_id = :hospital_id
                    AND b.status IN ('pending', 'approved', 'vaccinated', 'not_vaccinated')";
$params = ['hospital_id' => $hid];
if ($status !== 'all') {
    $sql .= ' AND b.status = :status';
    $params['status'] = $status;
}
if ($dateFilter !== '') {
    $sql .= ' AND b.booking_date = :booking_date';
    $params['booking_date'] = $dateFilter;
}
$sql .= ' ORDER BY b.booking_date DESC, b.booking_time ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statusLabels = ['all' => 'All appointments', 'pending' => 'Awaiting admin', 'approved' => 'Approved', 'vaccinated' => 'Vaccinated', 'not_vaccinated' => 'Not vaccinated'];

require __DIR__ . '/../includes/hospital_layout_start.php';
?>
<!-- Hospital appointment list with status tabs and a date filter. -->
<section class="hospital-page">
    <div class="page-header">
        <div><span class="parent-eyebrow">Visit management</span><h1 class="page-title">Appointments</h1><p class="page-subtitle">Assigned bookings appear here; admin approval unlocks visit outcome actions.</p></div>
    </div>
    <section class="hospital-panel">
        <nav class="filter-tabs mb-3" aria-label="Filter appointments by status">
            <?php foreach ($statusLabels as $key => $label): ?>
                <a class="tab-button<?= $status === $key ? ' active' : '' ?>"
                   href="<?= e(hospital_path('appointments.php')) ?>?status=<?= e($key) ?><?= $dateFilter !== '' ? '&amp;date=' . rawurlencode($dateFilter) : '' ?>"
                   <?= $status === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <form method="get" action="<?= e(hospital_path('appointments.php')) ?>" class="form-grid mb-3">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <div class="two-col filter-date-grid">
                <div class="form-group"><label for="date">Appointment date</label><input id="date" type="date" name="date" value="<?= e($dateFilter) ?>"></div>
                <div class="form-actions"><button class="btn btn-primary btn-small" type="submit">Apply date</button><a class="btn btn-secondary btn-small" href="<?= e(hospital_path('appointments.php')) ?>?status=<?= e($status) ?>">Clear</a></div>
            </div>
        </form>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Child</th><th>Parent</th><th>Vaccine</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="7" class="empty-table-cell">No appointments match these filters.</td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td><div class="row-user"><strong><?= e($row['child_name']) ?></strong><span><?= e((new DateTimeImmutable($row['dob']))->diff(new DateTimeImmutable('today'))->y) ?> years old</span></div></td>
                            <td><div class="row-user"><strong><?= e($row['parent_name']) ?></strong><span><?= e($row['parent_phone']) ?></span></div></td>
                            <td><?= e($row['vaccine_name']) ?></td>
                            <td><?= e(date('M j, Y', strtotime($row['booking_date']))) ?></td>
                            <td><?= e(date('g:i A', strtotime($row['booking_time']))) ?></td>
                            <td><span class="status-badge status-<?= e($row['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $row['status']))) ?></span></td>
                            <td>
                                <?php if ($row['status'] === 'approved'): ?>
                                    <div class="inline-actions">
                                        <?php
                                        $actions = ['vaccinated' => 'Vaccinated', 'not_vaccinated' => 'Not vaccinated'];
                                        foreach ($actions as $result => $label):
                                        ?>
                                            <form class="inline-form" method="post" action="<?= e(hospital_action('hospital_update_status.php')) ?>">
                                                <input type="hidden" name="booking_id" value="<?= (int) $row['booking_id'] ?>">
                                                <input type="hidden" name="result" value="<?= e($result) ?>">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <button type="submit" class="btn btn-small <?= in_array($result, ['approved', 'vaccinated'], true) ? 'btn-success' : 'btn-danger' ?>"><?= e($label) ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($row['status'] === 'pending'): ?>
                                    <span class="status-badge status-pending">Awaiting admin</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php require __DIR__ . '/../includes/hospital_layout_end.php'; ?>
