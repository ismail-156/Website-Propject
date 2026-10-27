<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = '';
$pageTitle = 'Profile and security';

// The account is joined to the already verified parent record.
$profileStmt = $pdo->prepare(
    'SELECT u.name, u.email, p.phone, p.address, p.city
     FROM users u INNER JOIN parents p ON p.user_id = u.id
     WHERE u.id = :user_id AND p.id = :parent_id LIMIT 1'
);
$profileStmt->execute(['user_id' => (int) $_SESSION['user_id'], 'parent_id' => $pid]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Account settings split into profile and password forms. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Your account</span>
    <h1>Profile &amp; security</h1>
    <p>Keep your contact details current and protect your account with a strong password.</p>
</section>
<section class="row g-4">
    <div class="col-12 col-lg-7">
        <form class="parent-card parent-form-card" method="post" action="<?= e(parent_action('parent_profile.php')) ?>">
            <input type="hidden" name="action" value="profile">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="parent-card-heading"><div><h2>Contact details</h2><p>Used for important appointment updates.</p></div></div>
            <div class="form-group">
                <label for="profile-name">Full name</label>
                <input id="profile-name" name="name" type="text" maxlength="120" value="<?= e($profile['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="profile-email">Email</label>
                <input id="profile-email" type="email" value="<?= e($profile['email'] ?? '') ?>" readonly aria-describedby="email-hint">
                <small id="email-hint" class="muted-copy">Email changes are managed by your account administrator.</small>
            </div>
            <div class="form-group">
                <label for="profile-phone">Phone</label>
                <input id="profile-phone" name="phone" type="tel" maxlength="30" value="<?= e($profile['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="profile-address">Address</label>
                <textarea id="profile-address" name="address" rows="3" maxlength="255" required><?= e($profile['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="profile-city">City</label>
                <input id="profile-city" name="city" type="text" maxlength="120" value="<?= e($profile['city'] ?? '') ?>" required>
            </div>
            <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
    </div>
    <div class="col-12 col-lg-5">
        <form class="parent-card parent-form-card" method="post" action="<?= e(parent_action('parent_profile.php')) ?>">
            <input type="hidden" name="action" value="password">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="parent-card-heading"><div><h2>Change password</h2><p>Use at least 8 characters.</p></div></div>
            <div class="form-group">
                <label for="old-password">Current password</label>
                <input id="old-password" name="old_password" type="password" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="new-password">New password</label>
                <input id="new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm new password</label>
                <input id="confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <button class="btn btn-primary" type="submit">Update password</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
