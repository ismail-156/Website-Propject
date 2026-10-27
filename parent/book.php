<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'schedule';
$pageTitle = 'Book a vaccination';

// Parent-owned child choices and only currently bookable schedule entries.
$childrenStmt = $pdo->prepare('SELECT id, name FROM children WHERE parent_id = :parent_id ORDER BY name');
$childrenStmt->execute(['parent_id' => $pid]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
$requestedChildId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;
$selectedChildId = 0;
foreach ($children as $childOption) {
    if ((int) $childOption['id'] === $requestedChildId) $selectedChildId = $requestedChildId;
}
if ($selectedChildId === 0 && count($children) === 1) $selectedChildId = (int) $children[0]['id'];

$vaccines = [];
if ($selectedChildId > 0) {
    $vaccineStmt = $pdo->prepare(
        "SELECT v.id AS vaccine_id, v.name, v.disease_prevented, cv.due_date
         FROM child_vaccinations cv
         INNER JOIN children c ON c.id = cv.child_id
         INNER JOIN vaccines v ON v.id = cv.vaccine_id
         WHERE c.parent_id = :parent_id AND c.id = :child_id
           AND cv.status IN ('pending', 'missed') AND LOWER(v.availability) = 'available'
         ORDER BY cv.due_date, v.name"
    );
    $vaccineStmt->execute(['parent_id' => $pid, 'child_id' => $selectedChildId]);
    $vaccines = $vaccineStmt->fetchAll(PDO::FETCH_ASSOC);
}
$hospitalStmt = $pdo->query(
    "SELECT h.id AS hospital_id, h.hospital_name AS name, h.city
     FROM hospitals h INNER JOIN users u ON u.id = h.user_id
     WHERE u.status = 'active' ORDER BY h.hospital_name"
);
$hospitals = $hospitalStmt->fetchAll(PDO::FETCH_ASSOC);
$requestedVaccineId = filter_input(INPUT_GET, 'vaccine_id', FILTER_VALIDATE_INT) ?: 0;
$requestedHospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: 0;
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Three-step booking form with prefilled choices and server-verifiable option lists. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Plan a visit</span>
    <h1>Book a vaccine</h1>
    <p>Choose a child, an available vaccine, then a hospital and visit time.</p>
</section>
<form class="parent-card parent-form-card" method="post" action="<?= e(parent_action('parent_book.php')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="booking-stepper" aria-label="Booking steps">
        <div class="booking-step is-active"><span class="booking-step-number">1</span><span>Child</span></div>
        <div class="booking-step is-active"><span class="booking-step-number">2</span><span>Vaccine</span></div>
        <div class="booking-step is-active"><span class="booking-step-number">3</span><span>Hospital &amp; date</span></div>
    </div>
    <?php if (!$children): ?>
        <div class="parent-alert parent-alert-danger"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Add a child before booking a vaccination.</span></div>
        <a class="btn btn-primary" href="<?= e(parent_path('add_child.php')) ?>">Add child</a>
    <?php else: ?>
        <div class="form-group">
            <label for="booking-child">Child</label>
            <select id="booking-child" name="child_id" required data-book-child>
                <option value="">Choose a child</option>
                <?php foreach ($children as $childOption): ?>
                    <option value="<?= (int) $childOption['id'] ?>"<?= (int) $childOption['id'] === $selectedChildId ? ' selected' : '' ?>><?= e($childOption['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="booking-vaccine">Available vaccine</label>
            <select id="booking-vaccine" name="vaccine_id" required>
                <option value="">Choose a vaccine</option>
                <?php foreach ($vaccines as $vaccine): ?>
                    <option value="<?= (int) $vaccine['vaccine_id'] ?>"<?= (int) $vaccine['vaccine_id'] === $requestedVaccineId ? ' selected' : '' ?>>
                        <?= e($vaccine['name']) ?><?= $vaccine['disease_prevented'] ? ' · ' . e($vaccine['disease_prevented']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selectedChildId && !$vaccines): ?><small class="muted-copy">No pending, available vaccines are listed for this child right now.</small><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="booking-hospital">Hospital</label>
            <select id="booking-hospital" name="hospital_id" required>
                <option value="">Choose a hospital</option>
                <?php foreach ($hospitals as $hospital): ?>
                    <option value="<?= (int) $hospital['hospital_id'] ?>"<?= (int) $hospital['hospital_id'] === $requestedHospitalId ? ' selected' : '' ?>><?= e($hospital['name']) ?> · <?= e($hospital['city']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-6 form-group">
                <label for="booking-date">Visit date</label>
                <input id="booking-date" name="booking_date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <fieldset class="col-12 col-md-6 form-group">
                <legend class="form-label">Available time</legend>
                <div class="booking-time-options">
                    <?php foreach (['09:00', '10:30', '12:00', '14:00', '15:30'] as $time): ?>
                        <label class="booking-time-option">
                            <input type="radio" name="booking_time" value="<?= e($time) ?>" required>
                            <span><?= e(date('g:i A', strtotime($time))) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-primary" type="submit"<?= !$vaccines || !$hospitals ? ' disabled' : '' ?>>Request booking</button>
            <a class="parent-btn-secondary btn" href="<?= e(parent_path('schedule.php')) ?>">Back to schedule</a>
        </div>
    <?php endif; ?>
</form>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
