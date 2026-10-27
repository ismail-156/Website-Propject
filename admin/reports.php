<?php
// Authenticate and select the requested report tab and filters.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'reports';
$pageTitle = 'Vaccination reports';
$tab = (string) ($_GET['tab'] ?? 'dates');
if (!in_array($tab, ['dates', 'children', 'vaccines'], true)) $tab = 'dates';
$fromDate = (string) ($_GET['from'] ?? date('Y-m-01'));
$toDate = (string) ($_GET['to'] ?? date('Y-m-d'));
$selectedChild = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;
$children = $pdo->query('SELECT id, name FROM children ORDER BY name')->fetchAll();
$rows = [];
$reportTitle = 'Vaccinations by date range';
if ($tab === 'dates') {
    $reportStmt = $pdo->prepare("SELECT cv.vaccinated_on, c.name AS child_name, v.name AS vaccine_name, h.hospital_name FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id INNER JOIN vaccines v ON v.id = cv.vaccine_id LEFT JOIN bookings b ON b.child_id = c.id AND b.vaccine_id = v.id AND b.status = 'vaccinated' LEFT JOIN hospitals h ON h.id = b.hospital_id WHERE cv.status = 'vaccinated' AND cv.vaccinated_on BETWEEN :from_date AND :to_date GROUP BY cv.cv_id ORDER BY cv.vaccinated_on DESC, c.name");
    $reportStmt->execute(['from_date' => $fromDate, 'to_date' => $toDate]);
    $rows = $reportStmt->fetchAll();
} elseif ($tab === 'children') {
    $reportTitle = 'Child-wise vaccination history';
    $reportStmt = $pdo->prepare("SELECT c.name AS child_name, cv.vaccinated_on, v.name AS vaccine_name, v.disease_prevented, cv.status FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id INNER JOIN vaccines v ON v.id = cv.vaccine_id WHERE (:all_children = 0 OR c.id = :selected_child) ORDER BY c.name, cv.due_date");
    $reportStmt->execute(['all_children' => $selectedChild, 'selected_child' => $selectedChild]);
    $rows = $reportStmt->fetchAll();
} else {
    $reportTitle = 'Vaccine-wise summary';
    $rows = $pdo->query("SELECT v.name AS vaccine_name, COUNT(cv.cv_id) AS total_doses, SUM(CASE WHEN cv.status = 'vaccinated' THEN 1 ELSE 0 END) AS vaccinated_count, SUM(CASE WHEN cv.status = 'pending' THEN 1 ELSE 0 END) AS pending_count, SUM(CASE WHEN cv.status = 'missed' THEN 1 ELSE 0 END) AS missed_count FROM vaccines v LEFT JOIN child_vaccinations cv ON cv.vaccine_id = v.id GROUP BY v.id, v.name ORDER BY v.name")->fetchAll();
}
$exportParams = ['report' => $tab, 'from' => $fromDate, 'to' => $toDate, 'child_id' => $selectedChild];
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Report heading offers print and CSV actions without extra navigation. -->
<section class="admin-heading"><div><span class="admin-eyebrow">System insights</span><h1>Vaccination <span class="highlight">Reports</span><span class="highlight alt">.</span></h1><p>Explore completion activity, child history, and vaccine demand.</p></div><div class="admin-heading-actions"><a class="btn btn-secondary" href="<?= e(appUrl('admin/export.php') . '?' . http_build_query($exportParams)) ?>"><i class="bi bi-download me-2" aria-hidden="true"></i>Export CSV</a><button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-2" aria-hidden="true"></i>Print</button></div></section>
<!-- Tabs switch between the three supported report slices. -->
<nav class="admin-tabs" aria-label="Report types"><?php foreach (['dates' => 'By date range', 'children' => 'Child history', 'vaccines' => 'Vaccine summary'] as $key => $label): ?><a class="<?= $tab === $key ? 'is-active' : '' ?>" href="<?= e(appUrl('admin/reports.php') . '?' . http_build_query(array_merge($exportParams, ['tab' => $key]))) ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
<!-- Active report filters and output use one consistent table surface. -->
<section class="admin-panel report-print-area"><div class="panel-heading"><div><span class="admin-eyebrow">Report</span><h2><?= e($reportTitle) ?></h2></div></div>
    <?php if ($tab === 'dates'): ?><form class="admin-filter-grid report-filter" method="get"><input type="hidden" name="tab" value="dates"><label class="admin-field"><span>From</span><input type="date" name="from" value="<?= e($fromDate) ?>" required></label><label class="admin-field"><span>Through</span><input type="date" name="to" value="<?= e($toDate) ?>" required></label><button class="btn btn-primary btn-small" type="submit">Run report</button></form><?php elseif ($tab === 'children'): ?><form class="admin-filter-grid report-filter" method="get"><input type="hidden" name="tab" value="children"><label class="admin-field"><span>Child</span><select name="child_id"><option value="0">All children</option><?php foreach ($children as $child): ?><option value="<?= (int) $child['id'] ?>"<?= $selectedChild === (int) $child['id'] ? ' selected' : '' ?>><?= e($child['name']) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary btn-small" type="submit">Run report</button></form><?php endif; ?>
    <!-- Data columns vary by selected tab but share safe escaped output. -->
    <div class="table-responsive"><table class="admin-table"><thead><tr><?php if ($tab === 'dates'): ?><th>Date</th><th>Child</th><th>Vaccine</th><th>Hospital</th><?php elseif ($tab === 'children'): ?><th>Child</th><th>Vaccine</th><th>Disease prevented</th><th>Vaccinated on</th><th>Status</th><?php else: ?><th>Vaccine</th><th>Total doses</th><th>Vaccinated</th><th>Pending</th><th>Missed</th><?php endif; ?></tr></thead><tbody>
        <?php foreach ($rows as $row): ?><?php if ($tab === 'dates'): ?><tr><td><?= e(date('M j, Y', strtotime($row['vaccinated_on']))) ?></td><td><?= e($row['child_name']) ?></td><td><?= e($row['vaccine_name']) ?></td><td><?= e($row['hospital_name'] ?: 'Not recorded') ?></td></tr><?php elseif ($tab === 'children'): ?><tr><td><?= e($row['child_name']) ?></td><td><?= e($row['vaccine_name']) ?></td><td><?= e($row['disease_prevented']) ?></td><td><?= $row['vaccinated_on'] ? e(date('M j, Y', strtotime($row['vaccinated_on']))) : '—' ?></td><td><span class="status-badge <?= e(admin_status_class($row['status'])) ?>"><?= e(ucfirst($row['status'])) ?></span></td></tr><?php else: ?><tr><td><strong><?= e($row['vaccine_name']) ?></strong></td><td><?= (int) $row['total_doses'] ?></td><td><?= (int) $row['vaccinated_count'] ?></td><td><?= (int) $row['pending_count'] ?></td><td><?= (int) $row['missed_count'] ?></td></tr><?php endif; ?><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty-table-cell">No data is available for this report selection.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>