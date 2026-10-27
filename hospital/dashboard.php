<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
$hid = get_hospital_id($pdo, (int) $_SESSION['user_id']);
$hospitalName = hospital_display_name($pdo, $hid);
$activeNav = 'dashboard';
$pageTitle = 'Hospital overview';
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// Dashboard totals and appointments are isolated to the authenticated hospital ID.
$metrics = [];
foreach ([
    'today' => "SELECT COUNT(*) FROM bookings WHERE hospital_id = :hospital_id AND booking_date = :date AND status IN ('pending', 'approved')",
    'upcoming' => "SELECT COUNT(*) FROM bookings WHERE hospital_id = :hospital_id AND status = 'approved' AND booking_date >= :date",
    'vaccinated' => "SELECT COUNT(*) FROM bookings WHERE hospital_id = :hospital_id AND status = 'vaccinated' AND booking_date >= :date",
    'pending' => "SELECT COUNT(*) FROM bookings WHERE hospital_id = :hospital_id AND status = 'pending'",
] as $key => $query) {
    $stmt = $pdo->prepare($query);
    $params = ['hospital_id' => $hid];
    if (str_contains($query, ':date')) {
        $params['date'] = $key === 'vaccinated' ? $monthStart : $today;
    }
    $stmt->execute($params);
    $metrics[$key] = (int) $stmt->fetchColumn();
}

$appointmentsStmt = $pdo->prepare(
        "SELECT b.id AS booking_id, b.booking_date, b.booking_time, b.status, c.name AS child_name,
            v.name AS vaccine_name, p.phone AS parent_phone
     FROM bookings b
     INNER JOIN children c ON c.id = b.child_id
     INNER JOIN parents p ON p.id = b.parent_id
     INNER JOIN vaccines v ON v.id = b.vaccine_id
         WHERE b.hospital_id = :hospital_id AND b.booking_date >= :start_date
             AND b.status IN ('pending', 'approved', 'vaccinated', 'not_vaccinated')
         ORDER BY b.booking_date ASC, b.booking_time ASC LIMIT 8"
);
$appointmentsStmt->execute(['hospital_id' => $hid, 'start_date' => $today]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/hospital_layout_start.php';
?>
<!-- Hospital overview, daily workload, and immediate booking decisions. -->
<section class="hospital-page">
    <div class="page-header">
        <div>
            <span class="parent-eyebrow">Hospital workspace</span>
            <h1 class="page-title">Today’s <span class="highlight">care plan</span><span class="highlight alt">.</span></h1>
            <p class="page-subtitle">View admin-approved appointments and record each visit outcome.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(hospital_path('appointments.php')) ?>"><i class="bi bi-calendar2-week me-2" aria-hidden="true"></i>All appointments</a>
    </div>

    <!-- Four operational totals summarize the current hospital's workload. -->
    <div class="stats-grid" aria-label="Hospital appointment overview">
        <?php foreach ([
            ['today', 'Today’s appointments', 'bi-calendar2-heart', 'orange'],
            ['upcoming', 'Upcoming approved', 'bi-calendar-check', 'green'],
            ['vaccinated', 'Vaccinated this month', 'bi-shield-check', 'pink'],
            ['pending', 'Awaiting admin approval', 'bi-inbox', 'purple'],
        ] as [$key, $label, $icon, $tone]): ?>
            <article class="stat-card <?= e($tone) ?>">
                <div class="stat-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></div>
                <div><p class="stat-value"><?= (int) $metrics[$key] ?></p><p class="stat-label"><?= e($label) ?></p></div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Upcoming assigned bookings remain visible with their current status. -->
    <section class="hospital-panel">
        <div class="page-header">
            <div><h2 class="hospital-section-title">Upcoming appointments</h2><p class="page-subtitle">Scheduled visits from today onward.</p></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Child</th><th>Vaccine</th><th>Date</th><th>Time</th><th>Parent phone</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!$appointments): ?>
                        <tr><td colspan="7" class="empty-table-cell">No upcoming appointments are scheduled.</td></tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><strong><?= e($row['child_name']) ?></strong></td>
                                <td><?= e($row['vaccine_name']) ?></td>
                                <td><?= e(date('M j, Y', strtotime($row['booking_date']))) ?></td>
                                <td><?= e(date('g:i A', strtotime($row['booking_time']))) ?></td>
                                <td><?= e($row['parent_phone']) ?></td>
                                <td><span class="status-badge status-<?= e($row['status']) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'approved'): ?>
                                        <div class="inline-actions">
                                            <?php foreach (['vaccinated' => 'Mark vaccinated', 'not_vaccinated' => 'Not vaccinated'] as $result => $label): ?>
                                                <form class="inline-form" method="post" action="<?= e(hospital_action('hospital_update_status.php')) ?>">
                                                    <input type="hidden" name="booking_id" value="<?= (int) $row['booking_id'] ?>">
                                                    <input type="hidden" name="result" value="<?= e($result) ?>">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <button class="btn btn-small <?= $result === 'vaccinated' ? 'btn-success' : 'btn-danger' ?>" type="submit"><?= e($label) ?></button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <span class="status-badge status-pending">Awaiting admin</span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php require __DIR__ . '/../includes/hospital_layout_end.php'; ?>
