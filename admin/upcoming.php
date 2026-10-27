<?php
// Authenticate and refresh missed-dose statuses before assembling upcoming work.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
mark_missed($pdo);
$activeAdminPage = 'upcoming';
$pageTitle = 'Upcoming vaccinations';
$fromDate = (string) ($_GET['from'] ?? date('Y-m-d'));
$toDate = (string) ($_GET['to'] ?? date('Y-m-d', strtotime('+30 days')));
$vaccineId = filter_input(INPUT_GET, 'vaccine_id', FILTER_VALIDATE_INT) ?: 0;
$where = ["cv.status IN ('pending', 'missed')"];
$params = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) { $where[] = 'cv.due_date >= :from_date'; $params['from_date'] = $fromDate; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) { $where[] = 'cv.due_date <= :to_date'; $params['to_date'] = $toDate; }
if ($vaccineId > 0) { $where[] = 'v.id = :vaccine_id'; $params['vaccine_id'] = $vaccineId; }
$vaccineOptions = $pdo->query('SELECT id, name FROM vaccines ORDER BY name')->fetchAll();
$dueStmt = $pdo->prepare(
    'SELECT cv.cv_id, cv.due_date, cv.status, c.id AS child_id, c.name AS child_name, v.name AS vaccine_name, pUser.name AS parent_name
     FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id
     INNER JOIN vaccines v ON v.id = cv.vaccine_id INNER JOIN parents p ON p.id = c.parent_id
     INNER JOIN users pUser ON pUser.id = p.user_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY cv.due_date, c.name'
);
$dueStmt->execute($params);
$dueRows = $dueStmt->fetchAll();
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Date and vaccine filters narrow the cross-family due-date list. -->
<section class="admin-heading"><div><span class="admin-eyebrow">Schedule oversight</span><h1>Upcoming <span class="highlight">Vaccinations</span><span class="highlight alt">.</span></h1><p>See pending and overdue doses across every child profile.</p></div><span class="admin-total-chip"><i class="bi bi-calendar-check" aria-hidden="true"></i><?= count($dueRows) ?> doses</span></section>
<section class="admin-panel">
    <form class="admin-filter-grid" method="get"><label class="admin-field"><span>From</span><input type="date" name="from" value="<?= e($fromDate) ?>"></label><label class="admin-field"><span>Through</span><input type="date" name="to" value="<?= e($toDate) ?>"></label><label class="admin-field"><span>Vaccine</span><select name="vaccine_id"><option value="0">All vaccines</option><?php foreach ($vaccineOptions as $option): ?><option value="<?= (int) $option['id'] ?>"<?= $vaccineId === (int) $option['id'] ? ' selected' : '' ?>><?= e($option['name']) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary btn-small" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter</button></form>
    <!-- Overdue items are distinctly tinted while their status remains explicit. -->
    <div class="table-responsive"><table class="admin-table"><thead><tr><th>Due date</th><th>Child</th><th>Parent</th><th>Vaccine</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($dueRows as $dose): $isOverdue = $dose['due_date'] < date('Y-m-d'); ?>
            <tr class="<?= $isOverdue ? 'row-overdue' : '' ?>"><td><strong><?= e(date('M j, Y', strtotime($dose['due_date']))) ?></strong></td><td><?= e($dose['child_name']) ?></td><td><?= e($dose['parent_name']) ?></td><td><?= e($dose['vaccine_name']) ?></td><td><span class="status-badge <?= e($isOverdue ? 'status-danger' : admin_status_class($dose['status'])) ?>"><?= $isOverdue ? 'Overdue' : e(ucfirst($dose['status'])) ?></span></td><td><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/child_view.php')) ?>?id=<?= (int) $dose['child_id'] ?>">View child</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$dueRows): ?><tr><td colspan="6" class="empty-table-cell">No doses match the selected date range.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>