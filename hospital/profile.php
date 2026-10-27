<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
$hospitalName = hospital_display_name($pdo, $hid);
$activeNav = 'profile';
$pageTitle = 'Hospital profile';

// This query binds the facility to the authenticated hospital account.
$profileStmt = $pdo->prepare(
    'SELECT h.hospital_name, h.address, h.city, h.location, h.phone, u.email
     FROM hospitals h INNER JOIN users u ON u.id = h.user_id
     WHERE h.id = :hospital_id AND u.id = :user_id LIMIT 1'
);
$profileStmt->execute(['hospital_id' => $hid, 'user_id' => (int) $_SESSION['user_id']]);
$hospital = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

require __DIR__ . '/../includes/hospital_layout_start.php';
?>
<!-- Facility profile and password forms post to their authenticated action handler. -->
<section class="hospital-page">
    <div class="page-header"><div><span class="parent-eyebrow">Facility settings</span><h1 class="page-title">Hospital profile</h1><p class="page-subtitle">Keep your location and contact information current for families.</p></div></div>
    <div class="hospital-settings-grid">
        <section class="hospital-panel">
            <h2 class="hospital-section-title">Facility details</h2>
            <form method="post" action="<?= e(hospital_action('hospital_profile.php')) ?>" class="form-grid">
                <input type="hidden" name="action" value="profile">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="form-group"><label for="hospital-name">Hospital name</label><input id="hospital-name" type="text" name="hospital_name" maxlength="150" value="<?= e($hospital['hospital_name'] ?? '') ?>" required></div>
                <div class="form-group"><label for="hospital-email">Account email</label><input id="hospital-email" type="email" value="<?= e($hospital['email'] ?? '') ?>" readonly></div>
                <div class="form-group"><label for="hospital-address">Address</label><input id="hospital-address" type="text" name="address" maxlength="255" value="<?= e($hospital['address'] ?? '') ?>" required></div>
                <div class="two-col">
                    <div class="form-group"><label for="hospital-city">City</label><input id="hospital-city" type="text" name="city" maxlength="100" value="<?= e($hospital['city'] ?? '') ?>" required></div>
                    <div class="form-group"><label for="hospital-location">Area / location</label><input id="hospital-location" type="text" name="location" maxlength="150" value="<?= e($hospital['location'] ?? '') ?>" required></div>
                </div>
                <div class="form-group"><label for="hospital-phone">Phone</label><input id="hospital-phone" type="tel" name="phone" maxlength="20" value="<?= e($hospital['phone'] ?? '') ?>" required></div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Save facility details</button></div>
            </form>
        </section>
        <section class="hospital-panel">
            <h2 class="hospital-section-title">Change password</h2>
            <form method="post" action="<?= e(hospital_action('hospital_profile.php')) ?>" class="form-grid">
                <input type="hidden" name="action" value="password">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="form-group"><label for="old-password">Current password</label><input id="old-password" type="password" name="old_password" autocomplete="current-password" required></div>
                <div class="form-group"><label for="new-password">New password</label><input id="new-password" type="password" name="new_password" minlength="8" autocomplete="new-password" required></div>
                <div class="form-group"><label for="confirm-password">Confirm new password</label><input id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></div>
                <div class="form-actions"><button class="btn btn-secondary" type="submit">Update password</button></div>
            </form>
        </section>
    </div>
</section>
<?php require __DIR__ . '/../includes/hospital_layout_end.php'; ?>
