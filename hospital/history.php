<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
$hospitalName = hospital_display_name($pdo, $hid);
$activeNav = 'history';
$pageTitle = 'Visit history';
$startDate = (string) ($_GET['start_date'] ?? '');
$endDate = (string) ($_GET['end_date'] ?? '');
$parsedStart = $startDate === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
$parsedEnd = $endDate === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
if ($startDate !== '' && (!$parsedStart || $parsedStart->format('Y-m-d') !== $startDate)) $startDate = '';
if ($endDate !== '' && (!$parsedEnd || $parsedEnd->format('Y-m-d') !== $endDate)) $endDate = '';

// Completed outcomes are displayed only for this hospital and optional date range.
$sql = "SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.status,
               c.name AS child_name, v.name AS vaccine_name
        FROM bookings b
        INNER JOIN children c ON c.id = b.child_id
        INNER JOIN vaccines v ON v.id = b.vaccine_id
        WHERE b.hospital_id = :hospital_id AND b.status IN ('vaccinated','not_vaccinated')";
$params = ['hospital_id' => $hid];
if ($startDate !== '') {
    $sql .= ' AND b.booking_date >= :start_date';
    $params['start_date'] = $startDate;
}
if ($endDate !== '') {
    $sql .= ' AND b.booking_date <= :end_date';
    $params['end_date'] = $endDate;
}
$sql .= ' ORDER BY b.booking_date DESC, b.booking_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/hospital_layout_start.php';
?>
<!-- Completed visit history with inclusive start/end date filters. -->
<section class="hospital-page">
    <div class="page-header">
        <div><span class="parent-eyebrow">Care delivered</span><h1 class="page-title">Visit history</h1><p class="page-subtitle">Review completed appointment outcomes for your facility.</p></div>
        <div class="history-total"><span>Completed records</span><strong><?= count($rows) ?></strong></div>
    </div>
    <section class="hospital-panel">
        <form method="get" action="<?= e(hospital_path('history.php')) ?>" class="form-grid mb-3">
            <div class="two-col filter-date-grid">
                <div class="form-group"><label for="start_date">From</label><input id="start_date" type="date" name="start_date" value="<?= e($startDate) ?>"></div>
                <div class="form-group"><label for="end_date">Through</label><input id="end_date" type="date" name="end_date" value="<?= e($endDate) ?>"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary btn-small" type="submit">Apply range</button><a class="btn btn-secondary btn-small" href="<?= e(hospital_path('history.php')) ?>">Reset</a></div>
        </form>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Child</th><th>Vaccine</th><th>Date</th><th>Time</th><th>Outcome</th></tr></thead>
                <tbody>
                    <?php if (!$rows): ?><tr><td colspan="5" class="empty-table-cell">No completed records found for this period.</td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['child_name']) ?></td>
                            <td><?= e($row['vaccine_name']) ?></td>
                            <td><?= e(date('M j, Y', strtotime($row['booking_date']))) ?></td>
                            <td><?= e(date('g:i A', strtotime($row['booking_time']))) ?></td>
                            <td><span class="status-badge status-<?= e($row['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $row['status']))) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php require __DIR__ . '/../includes/hospital_layout_end.php'; ?>
