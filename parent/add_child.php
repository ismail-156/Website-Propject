<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'children';
$pageTitle = 'Add a child';
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Add-child form posts to the parent-scoped action endpoint. -->
<section class="parent-heading text-center">
    <span class="parent-eyebrow">Family profile</span>
    <h1>Add a child</h1>
    <p>Just the essentials. You can review the schedule as soon as the profile is saved.</p>
</section>
<form class="parent-card parent-form-card" method="post" action="<?= e(parent_action('parent_child.php')) ?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-group">
        <label for="child-name">Child’s full name</label>
        <input id="child-name" name="name" type="text" maxlength="120" autocomplete="name" required>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6 form-group">
            <label for="child-dob">Date of birth</label>
            <input id="child-dob" name="dob" type="date" max="<?= e(date('Y-m-d')) ?>" required>
        </div>
        <div class="col-12 col-md-6 form-group">
            <label for="blood-group">Blood group <span class="muted-copy">(optional)</span></label>
            <select id="blood-group" name="blood_group">
                <option value="">Select blood group</option>
                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bloodGroup): ?>
                    <option value="<?= e($bloodGroup) ?>"><?= e($bloodGroup) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <fieldset class="form-group" data-gender-group>
        <legend class="form-label">Gender</legend>
        <div class="gender-options">
            <?php foreach (['Male', 'Female'] as $gender): ?>
                <label class="gender-choice">
                    <input type="radio" name="gender" value="<?= e(strtolower($gender)) ?>" required>
                    <i class="bi <?= $gender === 'Male' ? 'bi-gender-male' : 'bi-gender-female' ?>" aria-hidden="true"></i><?= e($gender) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <p class="parent-form-note"><i class="bi bi-stars" aria-hidden="true"></i><span>The vaccination schedule is created automatically.</span></p>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit">Save child profile</button>
        <a class="parent-btn-secondary btn" href="<?= e(parent_path('children.php')) ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
