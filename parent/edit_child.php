<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'children';
$pageTitle = 'Edit child profile';
$childId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;

// The profile lookup includes the parent ownership predicate before rendering.
$childStmt = $pdo->prepare(
    'SELECT id, name, dob, gender, blood_group FROM children WHERE id = :child_id AND parent_id = :parent_id LIMIT 1'
);
$childStmt->execute(['child_id' => $childId, 'parent_id' => $pid]);
$child = $childStmt->fetch(PDO::FETCH_ASSOC);
if (!$child) {
    http_response_code(404);
    exit('Child profile not found.');
}
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Edit-child form reuses the same safe, server-side action as child creation. -->
<section class="parent-heading text-center">
    <span class="parent-eyebrow">Family profile</span>
    <h1>Edit <span class="highlight"><?= e($child['name']) ?></span></h1>
    <p>Update the details used for this child’s vaccination schedule.</p>
</section>
<form class="parent-card parent-form-card" method="post" action="<?= e(parent_action('parent_child.php')) ?>">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="child_id" value="<?= (int) $child['id'] ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-group">
        <label for="child-name">Child’s full name</label>
        <input id="child-name" name="name" type="text" maxlength="120" value="<?= e($child['name']) ?>" required>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6 form-group">
            <label for="child-dob">Date of birth</label>
            <input id="child-dob" name="dob" type="date" max="<?= e(date('Y-m-d')) ?>" value="<?= e($child['dob']) ?>" required>
        </div>
        <div class="col-12 col-md-6 form-group">
            <label for="blood-group">Blood group <span class="muted-copy">(optional)</span></label>
            <select id="blood-group" name="blood_group">
                <option value="">Select blood group</option>
                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bloodGroup): ?>
                    <option value="<?= e($bloodGroup) ?>"<?= $child['blood_group'] === $bloodGroup ? ' selected' : '' ?>><?= e($bloodGroup) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <fieldset class="form-group" data-gender-group>
        <legend class="form-label">Gender</legend>
        <div class="gender-options">
            <?php foreach (['Male', 'Female'] as $gender): ?>
                <?php $genderSelected = strtolower((string) $child['gender']) === strtolower($gender); ?>
                <label class="gender-choice<?= $genderSelected ? ' is-selected' : '' ?>">
                    <input type="radio" name="gender" value="<?= e(strtolower($gender)) ?>"<?= $genderSelected ? ' checked' : '' ?> required>
                    <i class="bi <?= $gender === 'Male' ? 'bi-gender-male' : 'bi-gender-female' ?>" aria-hidden="true"></i><?= e($gender) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <p class="parent-form-note"><i class="bi bi-stars" aria-hidden="true"></i><span>The vaccination schedule is created automatically.</span></p>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="parent-btn-secondary btn" href="<?= e(parent_path('children.php')) ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
