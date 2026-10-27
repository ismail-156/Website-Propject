<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'reports';
$pageTitle = 'Vaccination report';

// Select a child only from the authenticated parent's own family profiles.
$childrenStmt = $pdo->prepare('SELECT id, name, dob FROM children WHERE parent_id = :parent_id ORDER BY name');
$childrenStmt->execute(['parent_id' => $pid]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
$requestedChildId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;
$child = null;
foreach ($children as $childOption) {
    if ((int) $childOption['id'] === $requestedChildId) {
        $child = $childOption;
        break;
    }
}
if (!$child && $children) $child = $children[0];

// Completed records pair to a completed hospital booking when one exists.
$records = [];
if ($child) {
    $recordsStmt = $pdo->prepare(
        "SELECT cv.cv_id, cv.vaccinated_on, v.name AS vaccine_name, v.disease_prevented,
                (SELECT h.hospital_name
                 FROM bookings b
                 INNER JOIN hospitals h ON h.id = b.hospital_id
                 WHERE b.parent_id = :booking_parent AND b.child_id = cv.child_id
                   AND b.vaccine_id = cv.vaccine_id AND b.status = 'vaccinated'
                 ORDER BY b.booking_date DESC, b.id DESC LIMIT 1) AS hospital_name
         FROM child_vaccinations cv
         INNER JOIN children c ON c.id = cv.child_id
         INNER JOIN vaccines v ON v.id = cv.vaccine_id
         WHERE c.parent_id = :parent_id AND c.id = :child_id AND cv.status = 'vaccinated'
         ORDER BY cv.vaccinated_on DESC, v.name"
    );
    $recordsStmt->execute([
        'booking_parent' => $pid,
        'parent_id' => $pid,
        'child_id' => (int) $child['id'],
    ]);
    $records = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);
}
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Digital record is intentionally printable without application chrome. -->
<section class="parent-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><span class="parent-eyebrow">Digital vaccination card</span><h1>Vaccination report</h1><p>A clear record of completed vaccinations for each child.</p></div>
    <?php if ($child): ?><button class="btn btn-primary no-print" type="button" onclick="window.print()"><i class="bi bi-printer me-2" aria-hidden="true"></i>Print report</button><?php endif; ?>
</section>
<?php if ($children): ?>
    <nav class="child-tabs mb-4 no-print" aria-label="Select child">
        <?php foreach ($children as $childOption): ?>
            <a class="child-tab<?= $child && (int) $childOption['id'] === (int) $child['id'] ? ' is-active' : '' ?>"
               href="<?= e(parent_path('report.php')) ?>?child_id=<?= (int) $childOption['id'] ?>">
                <i class="bi bi-person-circle" aria-hidden="true"></i><?= e($childOption['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if ($child): ?>
        <section class="parent-card">
            <div class="parent-card-heading">
                <div><h2><?= e($child['name']) ?></h2><p>Born <?= e(date('F j, Y', strtotime($child['dob']))) ?> · VaxCare vaccination record</p></div>
                <span class="parent-stat-icon green"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            </div>
            <?php if ($records): ?>
                <div class="row g-3">
                    <?php foreach ($records as $record): ?>
                        <div class="col-12 col-md-6">
                            <article class="vaccine-record">
                                <h3><i class="bi bi-patch-check-fill me-2" aria-hidden="true"></i><?= e($record['vaccine_name']) ?></h3>
                                <p><?= e($record['disease_prevented'] ?: 'Vaccination completed') ?></p>
                                <p><strong>Date:</strong> <?= $record['vaccinated_on'] ? e(date('M j, Y', strtotime($record['vaccinated_on']))) : 'Date not recorded' ?></p>
                                <p><strong>Hospital:</strong> <?= e($record['hospital_name'] ?: 'Hospital not recorded') ?></p>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-art"><i class="bi bi-shield-plus" aria-hidden="true"></i></span>
                    <h3>No completed vaccinations recorded yet</h3>
                    <p>Once a vaccination is marked complete, its details will be added to this digital card.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section class="parent-card empty-state">
        <span class="empty-art"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
        <h3>No child profiles yet</h3>
        <p>Add a child to begin keeping a digital vaccination record.</p>
        <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>">Add a child</a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
