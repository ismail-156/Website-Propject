<?php
// Authenticate before streaming any report data as a downloadable CSV.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$report = (string) ($_GET['report'] ?? 'dates');
if (!in_array($report, ['dates', 'children', 'vaccines'], true)) $report = 'dates';
$fromDate = (string) ($_GET['from'] ?? date('Y-m-01'));
$toDate = (string) ($_GET['to'] ?? date('Y-m-d'));
$childId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT) ?: 0;

// Reuse each screen's query shape so exports match the visible report columns.
if ($report === 'dates') {
    $stmt = $pdo->prepare("SELECT cv.vaccinated_on AS `Date`, c.name AS `Child`, v.name AS `Vaccine`, COALESCE(h.hospital_name, 'Not recorded') AS `Hospital` FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id INNER JOIN vaccines v ON v.id = cv.vaccine_id LEFT JOIN bookings b ON b.child_id = c.id AND b.vaccine_id = v.id AND b.status = 'vaccinated' LEFT JOIN hospitals h ON h.id = b.hospital_id WHERE cv.status = 'vaccinated' AND cv.vaccinated_on BETWEEN :from_date AND :to_date GROUP BY cv.cv_id ORDER BY cv.vaccinated_on DESC, c.name");
    $stmt->execute(['from_date' => $fromDate, 'to_date' => $toDate]);
} elseif ($report === 'children') {
    $stmt = $pdo->prepare("SELECT c.name AS `Child`, v.name AS `Vaccine`, v.disease_prevented AS `Disease prevented`, cv.vaccinated_on AS `Vaccinated on`, cv.status AS `Status` FROM child_vaccinations cv INNER JOIN children c ON c.id = cv.child_id INNER JOIN vaccines v ON v.id = cv.vaccine_id WHERE (:all_children = 0 OR c.id = :selected_child) ORDER BY c.name, cv.due_date");
    $stmt->execute(['all_children' => $childId, 'selected_child' => $childId]);
} else {
    $stmt = $pdo->query("SELECT v.name AS `Vaccine`, COUNT(cv.cv_id) AS `Total doses`, SUM(CASE WHEN cv.status = 'vaccinated' THEN 1 ELSE 0 END) AS `Vaccinated`, SUM(CASE WHEN cv.status = 'pending' THEN 1 ELSE 0 END) AS `Pending`, SUM(CASE WHEN cv.status = 'missed' THEN 1 ELSE 0 END) AS `Missed` FROM vaccines v LEFT JOIN child_vaccinations cv ON cv.vaccine_id = v.id GROUP BY v.id, v.name ORDER BY v.name");
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$filename = 'vaxcare-' . $report . '-' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
$output = fopen('php://output', 'wb');
if ($rows) {
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($output, array_values($row));
} else {
    fputcsv($output, ['No report data']);
}
fclose($output);
exit;