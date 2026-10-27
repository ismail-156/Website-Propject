<?php
// Authenticate and prepare the searchable, paginated children directory.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/admin_helpers.php';
$activeAdminPage = 'children';
$pageTitle = 'Children';
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$where = $search === '' ? '' : ' WHERE c.name LIKE :child_search OR pu.name LIKE :parent_search OR p.phone LIKE :phone_search';
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM children c INNER JOIN parents p ON p.id = c.parent_id INNER JOIN users pu ON pu.id = p.user_id' . $where);
if ($search !== '') $countStmt->execute(['child_search' => '%' . $search . '%', 'parent_search' => '%' . $search . '%', 'phone_search' => '%' . $search . '%']); else $countStmt->execute();
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$childrenStmt = $pdo->prepare(
    'SELECT c.id AS child_id, c.name, c.dob, c.gender, c.blood_group, pu.name AS parent_name, p.phone
     FROM children c INNER JOIN parents p ON p.id = c.parent_id INNER JOIN users pu ON pu.id = p.user_id' . $where .
    ' ORDER BY c.name LIMIT :limit OFFSET :offset'
);
if ($search !== '') {
    $childrenStmt->bindValue(':child_search', '%' . $search . '%');
    $childrenStmt->bindValue(':parent_search', '%' . $search . '%');
    $childrenStmt->bindValue(':phone_search', '%' . $search . '%');
}
$childrenStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$childrenStmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$childrenStmt->execute();
$children = $childrenStmt->fetchAll();
require __DIR__ . '/../includes/admin_layout_start.php';
?>
<!-- Directory header and search form stay compact for repeated staff use. -->
<section class="admin-heading"><div><span class="admin-eyebrow">People</span><h1>Children <span class="highlight">Directory</span><span class="highlight alt">.</span></h1><p>Search child profiles and open their complete vaccination schedule.</p></div><span class="admin-total-chip"><i class="bi bi-people" aria-hidden="true"></i><?= number_format($total) ?> profiles</span></section>
<!-- GET search preserves the selected query between numbered pages. -->
<section class="admin-panel">
    <form class="admin-toolbar" method="get" role="search"><label class="admin-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search child, parent, or phone" aria-label="Search child directory"></label><button class="btn btn-primary btn-small" type="submit">Search</button><?php if ($search !== ''): ?><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/children.php')) ?>">Clear</a><?php endif; ?></form>
    <!-- Table layout retains horizontal scrolling on narrow viewports. -->
    <div class="table-responsive"><table class="admin-table"><thead><tr><th>Child</th><th>Parent</th><th>Phone</th><th>Age</th><th>Gender</th><th></th></tr></thead><tbody>
        <?php foreach ($children as $child): $age = (new DateTimeImmutable($child['dob']))->diff(new DateTimeImmutable('today')); $ageLabel = $age->y > 0 ? $age->y . ' yr' . ($age->y === 1 ? '' : 's') : $age->m . ' mo' . ($age->m === 1 ? '' : 's'); ?>
            <tr><td><strong><?= e($child['name']) ?></strong><span class="table-subline"><?= e($child['blood_group'] ?: 'Blood group not set') ?></span></td><td><?= e($child['parent_name']) ?></td><td><?= e($child['phone']) ?></td><td><?= e($ageLabel) ?></td><td><?= e(ucfirst($child['gender'])) ?></td><td><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/child_view.php')) ?>?id=<?= (int) $child['child_id'] ?>"><i class="bi bi-eye me-1" aria-hidden="true"></i>View</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$children): ?><tr><td class="empty-table-cell" colspan="6">No child profiles match this search.</td></tr><?php endif; ?>
    </tbody></table></div>
    <!-- Pagination links retain the active search phrase. -->
    <?php admin_pagination($page, $pages, appUrl('admin/children.php') . ($search !== '' ? '?q=' . rawurlencode($search) : '')); ?>
</section>
<?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>