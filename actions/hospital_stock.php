<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
require_hospital_post();
$vaccineId = filter_input(INPUT_POST, 'vaccine_id', FILTER_VALIDATE_INT) ?: 0;
$inStock = (string) ($_POST['in_stock'] ?? '');
if ($vaccineId < 1 || !in_array($inStock, ['yes', 'no'], true)) {
    flash('error', 'Choose a valid vaccine availability status.');
    redirect(hospital_path('stock.php'));
}

// Only listed globally available vaccines can be toggled at this facility.
$vaccineCheck = $pdo->prepare("SELECT id FROM vaccines WHERE id = :vaccine_id AND availability = 'available'");
$vaccineCheck->execute(['vaccine_id' => $vaccineId]);
if (!$vaccineCheck->fetchColumn()) {
    flash('error', 'This vaccine is not available for facility stock updates.');
    redirect(hospital_path('stock.php'));
}

$save = $pdo->prepare(
    'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, in_stock)
     VALUES (:hospital_id, :vaccine_id, :in_stock)
     ON DUPLICATE KEY UPDATE in_stock = VALUES(in_stock)'
);
$save->execute(['hospital_id' => $hid, 'vaccine_id' => $vaccineId, 'in_stock' => $inStock]);
flash('success', 'Vaccine availability updated.');
redirect(hospital_path('stock.php'));
