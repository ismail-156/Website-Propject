<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
mark_missed($pdo);
$activePage = 'schedule';
$pageTitle = 'Vaccination schedule';

// Child tabs and the selected schedule are always restricted to this parent's records.
$childrenStmt = $pdo->prepare('SELECT id, name FROM children WHERE parent_id = :parent_id ORDER BY name');
$childrenStmt->execute(['parent_id' => $pid]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
$requestedChildId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;
$childId = 0;
foreach ($children as $childOption) {
    if ((int) $childOption['id'] === $requestedChildId) {
        $childId = $requestedChildId;
        break;
    }
}
if ($childId === 0 && $children) $childId = (int) $children[0]['id'];
$schedule = [];
$selectedChildName = '';
if ($childId > 0) {
    foreach ($children as $childOption) {
        if ((int) $childOption['id'] === $childId) $selectedChildName = (string) $childOption['name'];
    }
    $scheduleStmt = $pdo->prepare(
        'SELECT cv.cv_id, cv.due_date, cv.status, cv.vaccinated_on,
                v.id AS vaccine_id, v.name AS vaccine_name, v.disease_prevented, v.availability
         FROM child_vaccinations cv
         INNER JOIN children c ON c.id = cv.child_id
         INNER JOIN vaccines v ON v.id = cv.vaccine_id
         WHERE c.parent_id = :parent_id AND c.id = :child_id
         ORDER BY cv.due_date, v.name'
    );
    $scheduleStmt->execute(['parent_id' => $pid, 'child_id' => $childId]);
    $schedule = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
}
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Schedule view with a child selector and status-coloured timeline rows. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Stay on track</span>
    <h1>Vaccination schedule</h1>
    <p>See each recommended vaccine and the next step for <?= $selectedChildName ? e($selectedChildName) : 'your family' ?>.</p>
</section>
<?php if ($children): ?>
    <nav class="child-tabs mb-4" aria-label="Select child">
        <?php foreach ($children as $childOption): ?>
            <a class="child-tab<?= (int) $childOption['id'] === $childId ? ' is-active' : '' ?>"
               href="<?= e(parent_path('schedule.php')) ?>?child_id=<?= (int) $childOption['id'] ?>"
               <?= (int) $childOption['id'] === $childId ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-person-circle" aria-hidden="true"></i><?= e($childOption['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if ($schedule): ?>
        <section class="schedule-list" aria-label="Vaccine doses">
            <?php foreach ($schedule as $dose):
                $status = (string) $dose['status'];
                $bookable = in_array($status, ['pending', 'missed'], true) && strtolower((string) $dose['availability']) === 'available';
            ?>
                <article class="schedule-row status-<?= e($status) ?>">
                    <div>
                        <h3><?= e($dose['vaccine_name']) ?></h3>
                        <p>Helps prevent <?= e($dose['disease_prevented'] ?: 'vaccine-preventable illness') ?> · Due <?= e(date('M j, Y', strtotime($dose['due_date']))) ?></p>
                    </div>
                    <span class="status-badge status-<?= e($status) ?>"><?= e(ucfirst($status)) ?></span>
                    <?php if ($bookable): ?>
                        <a class="btn btn-primary parent-btn-small" href="<?= e(parent_path('book.php')) ?>?child_id=<?= $childId ?>&amp;vaccine_id=<?= (int) $dose['vaccine_id'] ?>">Book</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="parent-card empty-state">
            <span class="empty-art"><i class="bi bi-calendar2-heart" aria-hidden="true"></i></span>
            <h3>No schedule yet</h3>
            <p>There are no recommended vaccines in the system for this child yet. Please check back later.</p>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section class="parent-card empty-state">
        <span class="empty-art"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
        <h3>Add a child to begin</h3>
        <p>Your child’s recommended vaccination schedule will appear here.</p>
        <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>">Add your first child</a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
