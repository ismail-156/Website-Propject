<?php
// Require an authenticated administrator before accepting catalog changes.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Reject non-POST and invalid-CSRF requests before reading submitted values.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash('error', 'Your session expired or the request was invalid. Please try again.');
    redirect(appUrl('admin/vaccines.php'));
}

$action = (string) ($_POST['action'] ?? '');
$vaccineId = filter_input(INPUT_POST, 'vaccine_id', FILTER_VALIDATE_INT) ?: 0;

// Availability changes accept the form's explicit state or invert the stored state.
if ($action === 'toggle') {
    if ($vaccineId < 1) {
        flash('error', 'Choose a valid vaccine.');
        redirect(appUrl('admin/vaccines.php'));
    }

    $availability = (string) ($_POST['availability'] ?? '');
    if (!in_array($availability, ['available', 'unavailable'], true)) {
        $currentStmt = $pdo->prepare('SELECT availability FROM vaccines WHERE id = :id LIMIT 1');
        $currentStmt->execute(['id' => $vaccineId]);
        $currentAvailability = $currentStmt->fetchColumn();
        if ($currentAvailability === false) {
            flash('error', 'That vaccine could not be found.');
            redirect(appUrl('admin/vaccines.php'));
        }
        $availability = $currentAvailability === 'available' ? 'unavailable' : 'available';
    }

    $toggleStmt = $pdo->prepare('UPDATE vaccines SET availability = :availability WHERE id = :id');
    $toggleStmt->execute(['availability' => $availability, 'id' => $vaccineId]);
    flash('success', 'Vaccine availability updated.');
    redirect(appUrl('admin/vaccines.php'));
}

// Validate and persist vaccine fields for both insert and edit requests.
if ($action !== 'save') {
    flash('error', 'Choose a valid vaccine action.');
    redirect(appUrl('admin/vaccines.php'));
}
$name = trim((string) ($_POST['name'] ?? ''));
$diseasePrevented = trim((string) ($_POST['disease_prevented'] ?? ''));
$ageUnit = (string) ($_POST['recommended_age_unit'] ?? 'days');
$recommendedAgeDays = false;
if (array_key_exists('recommended_age_value', $_POST)) {
    $ageValue = filter_var($_POST['recommended_age_value'], FILTER_VALIDATE_FLOAT);
    if ($ageValue !== false && is_finite((float) $ageValue) && (float) $ageValue >= 0
        && in_array($ageUnit, ['days', 'years'], true)) {
        $recommendedAgeDays = (int) round((float) $ageValue * ($ageUnit === 'years' ? 365.25 : 1));
    }
} else {
    $recommendedAgeDays = filter_var($_POST['recommended_age_days'] ?? null, FILTER_VALIDATE_INT);
}
$description = trim((string) ($_POST['description'] ?? ''));
$availability = (string) ($_POST['availability'] ?? 'available');
if ($name === '' || strlen($name) > 150 || $diseasePrevented === '' || strlen($diseasePrevented) > 150
    || $recommendedAgeDays === false || $recommendedAgeDays < 0 || $recommendedAgeDays > 36525
    || !in_array($availability, ['available', 'unavailable'], true)) {
    flash('error', 'Enter valid vaccine details and availability.');
    redirect(appUrl('admin/vaccine_form.php') . ($vaccineId > 0 ? '?id=' . $vaccineId : ''));
}

// Older installed schemas do not include description, so only write it when supported.
$descriptionColumnStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vaccines' AND COLUMN_NAME = 'description'"
);
$descriptionColumnStmt->execute();
$hasDescriptionColumn = (int) $descriptionColumnStmt->fetchColumn() > 0;

try {
    if ($vaccineId > 0) {
        $existsStmt = $pdo->prepare('SELECT id FROM vaccines WHERE id = :id LIMIT 1');
        $existsStmt->execute(['id' => $vaccineId]);
        if (!$existsStmt->fetchColumn()) {
            flash('error', 'That vaccine could not be found.');
            redirect(appUrl('admin/vaccines.php'));
        }
        $updateSql = 'UPDATE vaccines SET name = :name, disease_prevented = :disease_prevented, recommended_age_days = :recommended_age_days, availability = :availability';
        if ($hasDescriptionColumn) $updateSql .= ', description = :description';
        $updateSql .= ' WHERE id = :id';
        $updateStmt = $pdo->prepare($updateSql);
        $values = ['name' => $name, 'disease_prevented' => $diseasePrevented, 'recommended_age_days' => $recommendedAgeDays, 'availability' => $availability, 'id' => $vaccineId];
        if ($hasDescriptionColumn) $values['description'] = $description;
        $updateStmt->execute($values);
    } else {
        $insertSql = 'INSERT INTO vaccines (name, disease_prevented, recommended_age_days, availability) VALUES (:name, :disease_prevented, :recommended_age_days, :availability)';
        if ($hasDescriptionColumn) {
            $insertSql = 'INSERT INTO vaccines (name, disease_prevented, recommended_age_days, description, availability) VALUES (:name, :disease_prevented, :recommended_age_days, :description, :availability)';
        }
        $insertStmt = $pdo->prepare($insertSql);
        $values = ['name' => $name, 'disease_prevented' => $diseasePrevented, 'recommended_age_days' => $recommendedAgeDays, 'availability' => $availability];
        if ($hasDescriptionColumn) $values['description'] = $description;
        $insertStmt->execute($values);
    }
    flash('success', $hasDescriptionColumn || $description === ''
        ? 'Vaccine saved.'
        : 'Vaccine saved. The current database schema does not store descriptions.');
} catch (PDOException $exception) {
    error_log('Admin vaccine save failed: ' . $exception->getMessage());
    flash('error', 'Unable to save this vaccine right now. Please try again.');
    redirect(appUrl('admin/vaccine_form.php') . ($vaccineId > 0 ? '?id=' . $vaccineId : ''));
}
redirect(appUrl('admin/vaccines.php'));