<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'children';
$pageTitle = 'My children';

// Schedule progress is aggregated only for children belonging to this parent.
$childrenStmt = $pdo->prepare(
    "SELECT c.id AS child_id, c.name, c.dob, c.gender, c.blood_group,
            COUNT(cv.cv_id) AS total_count,
            SUM(CASE WHEN cv.status = 'vaccinated' THEN 1 ELSE 0 END) AS vaccinated_count
     FROM children c
     LEFT JOIN child_vaccinations cv ON cv.child_id = c.id
     WHERE c.parent_id = :parent_id
     GROUP BY c.id, c.name, c.dob, c.gender, c.blood_group
     ORDER BY c.name"
);
$childrenStmt->execute(['parent_id' => $pid]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Children directory and the add-child entry point. -->
<section class="parent-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><span class="parent-eyebrow">Family profiles</span><h1>My children</h1><p>Keep each child’s details and vaccination progress up to date.</p></div>
    <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add child</a>
</section>

<?php if ($children): ?>
    <section class="row g-4" aria-label="Children">
        <?php foreach ($children as $child):
            $name = (string) $child['name'];
            $initials = '';
            foreach (preg_split('/\s+/', trim($name)) as $part) {
                if ($part !== '') $initials .= mb_substr($part, 0, 1, 'UTF-8');
                if (mb_strlen($initials, 'UTF-8') >= 2) break;
            }
            $total = (int) $child['total_count'];
            $vaccinated = (int) $child['vaccinated_count'];
            $progress = $total > 0 ? (int) round($vaccinated / $total * 100) : 0;
            $age = (new DateTimeImmutable($child['dob']))->diff(new DateTimeImmutable('today'));
            $ageLabel = $age->y > 0 ? $age->y . ' yr' . ($age->y === 1 ? '' : 's') : $age->m . ' mo' . ($age->m === 1 ? '' : 's');
        ?>
            <div class="col-12 col-md-6 col-xl-4">
                <article class="child-card">
                    <div class="child-card-top">
                        <span class="child-avatar" aria-hidden="true"><?= e(mb_strtoupper($initials, 'UTF-8')) ?></span>
                        <div><h3><?= e($name) ?></h3><p><?= e($ageLabel) ?> old</p></div>
                    </div>
                    <div class="child-meta">
                        <div><span>Gender</span><strong><?= e($child['gender']) ?></strong></div>
                        <div><span>Blood group</span><strong><?= e($child['blood_group'] ?: 'Not set') ?></strong></div>
                    </div>
                    <div class="parent-progress-label"><span>Vaccination progress</span><span><?= $vaccinated ?> / <?= $total ?></span></div>
                    <div class="parent-progress" role="progressbar" aria-label="<?= e($name) ?> vaccination progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progress ?>">
                        <span style="width: <?= $progress ?>%"></span>
                    </div>
                    <div class="child-card-actions">
                        <a class="parent-btn-secondary parent-btn-small btn" href="<?= e(parent_path('schedule.php')) ?>?child_id=<?= (int) $child['child_id'] ?>">View schedule</a>
                        <a class="parent-btn-secondary parent-btn-small btn" href="<?= e(parent_path('edit_child.php')) ?>?child_id=<?= (int) $child['child_id'] ?>">Edit</a>
                        <form method="post" action="<?= e(parent_action('parent_child.php')) ?>" data-confirm-form="Delete <?= e($name) ?>’s profile and vaccination schedule?">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="child_id" value="<?= (int) $child['child_id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <button class="btn btn-primary parent-btn-small" type="submit" aria-label="Delete <?= e($name) ?>"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <!-- Friendly empty state invites the first child profile. -->
    <section class="parent-card empty-state">
        <span class="empty-art"><i class="bi bi-person-hearts" aria-hidden="true"></i></span>
        <h3>Your family profiles start here</h3>
        <p>Add your first child to create their vaccination schedule and keep important milestones in one place.</p>
        <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>">Add your first child</a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
