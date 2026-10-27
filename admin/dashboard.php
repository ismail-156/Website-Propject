<?php
// Authenticate every administrator page before reading protected records.
                require_once __DIR__ . '/../includes/auth.php';
                require_role('admin');
                require_once __DIR__ . '/../includes/admin_helpers.php';
                mark_missed($pdo);
                $activeAdminPage = 'dashboard';
                $pageTitle = 'Admin overview';

                // Load the top-line counts using the existing user and vaccination tables.
                $stats = [
                    'parents' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'parent'")->fetchColumn(),
                    'children' => (int) $pdo->query('SELECT COUNT(*) FROM children')->fetchColumn(),
                    'hospitals' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'hospital'")->fetchColumn(),
                    'requests' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
                ];
                $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM child_vaccinations WHERE status = 'vaccinated' AND vaccinated_on = :today");
                $todayStmt->execute(['today' => date('Y-m-d')]);
                $stats['today'] = (int) $todayStmt->fetchColumn();

                // Fill missing chart months with zero so the trend remains continuous.
                $monthStart = new DateTimeImmutable('first day of -5 months');
                $monthLabels = [];
                $monthValues = [];
                for ($offset = 0; $offset < 6; $offset++) {
                    $month = $monthStart->modify('+' . $offset . ' months');
                    $monthLabels[] = $month->format('M');
                    $monthValues[$month->format('Y-m')] = 0;
                }
                $trendStmt = $pdo->prepare(
                    "SELECT DATE_FORMAT(vaccinated_on, '%Y-%m') AS month_key, COUNT(*) AS total
                     FROM child_vaccinations
                     WHERE status = 'vaccinated' AND vaccinated_on >= :start_date
                     GROUP BY month_key ORDER BY month_key"
                );
                $trendStmt->execute(['start_date' => $monthStart->format('Y-m-01')]);
                foreach ($trendStmt->fetchAll() as $trend) {
                    if (array_key_exists($trend['month_key'], $monthValues)) $monthValues[$trend['month_key']] = (int) $trend['total'];
                }
                $chartValues = array_values($monthValues);

                // Keep the dashboard queues small and focused on actionable records.
                $pendingStmt = $pdo->query(
                    "SELECT b.id AS booking_id, b.booking_date, b.booking_time, c.name AS child_name,
                            pUser.name AS parent_name, v.name AS vaccine_name, h.hospital_name
                     FROM bookings b
                     INNER JOIN children c ON c.id = b.child_id
                     INNER JOIN parents p ON p.id = b.parent_id
                     INNER JOIN users pUser ON pUser.id = p.user_id
                     INNER JOIN vaccines v ON v.id = b.vaccine_id
                     INNER JOIN hospitals h ON h.id = b.hospital_id
                     WHERE b.status = 'pending' ORDER BY b.created_at DESC LIMIT 5"
                );
                $pendingBookings = $pendingStmt->fetchAll();
                $hospitalStmt = $pdo->query(
                    "SELECT h.id AS hospital_id, h.hospital_name, h.city, u.email
                     FROM hospitals h INNER JOIN users u ON u.id = h.user_id
                     WHERE u.role = 'hospital' AND u.status = 'pending'
                     ORDER BY u.created_at LIMIT 5"
                );
                $pendingHospitals = $hospitalStmt->fetchAll();
                require __DIR__ . '/../includes/admin_layout_start.php';
                ?>
                <!-- Dashboard heading uses the site's signature two-tone treatment. -->
                <section class="admin-heading">
                    <div><span class="admin-eyebrow">Administration</span><h1>Admin <span class="highlight">Overview</span><span class="highlight alt">.</span></h1><p>Your system at a glance, with the items that need attention today.</p></div>
                    <a class="btn btn-primary" href="<?= e(appUrl('admin/reports.php')) ?>"><i class="bi bi-bar-chart-line me-2" aria-hidden="true"></i>View reports</a>
                </section>

                <!-- Key totals use compact pastel icon tiles for quick scanning. -->
                <section class="admin-stats row g-3" aria-label="System totals">
                    <?php foreach ([
                        ['Parents', $stats['parents'], 'bi-people', 'purple'],
                        ['Children', $stats['children'], 'bi-person-hearts', 'pink'],
                        ['Hospitals', $stats['hospitals'], 'bi-hospital', 'green'],
                        ['Pending requests', $stats['requests'], 'bi-inbox', 'orange'],
                        ['Vaccinated today', $stats['today'], 'bi-shield-check', 'green'],
                    ] as [$label, $value, $icon, $tone]): ?>
                        <div class="col-6 col-xl"><article class="admin-stat-card <?= e($tone) ?>"><span class="admin-stat-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span><div><strong><?= number_format((int) $value) ?></strong><span><?= e($label) ?></span></div></article></div>
                    <?php endforeach; ?>
                </section>

                <!-- Monthly vaccination totals share the existing purple and mint palette. -->
                <section class="row g-4 mt-1">
                    <div class="col-12 col-xl-7"><article class="admin-panel chart-panel"><div class="panel-heading"><div><span class="admin-eyebrow">Progress</span><h2>Vaccinations over time</h2></div><span class="panel-icon"><i class="bi bi-activity" aria-hidden="true"></i></span></div><div class="chart-frame"><canvas id="admin-vaccination-chart" aria-label="Vaccinations per month" role="img"></canvas></div></article></div>
                    <div class="col-12 col-xl-5"><article class="admin-panel queue-panel"><div class="panel-heading"><div><span class="admin-eyebrow">Needs review</span><h2>Pending requests</h2></div><a class="panel-link" href="<?= e(appUrl('admin/requests.php')) ?>">All requests <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
                        <?php if ($pendingBookings): ?>
                            <div class="compact-list">
                                <?php foreach ($pendingBookings as $booking): ?>
                                    <a class="compact-row" href="<?= e(appUrl('admin/requests.php')) ?>"><span class="compact-avatar"><i class="bi bi-person" aria-hidden="true"></i></span><span class="compact-main"><strong><?= e($booking['child_name']) ?> <small>· <?= e($booking['vaccine_name']) ?></small></strong><span><?= e($booking['parent_name']) ?> · <?= e($booking['hospital_name']) ?></span></span><span class="status-badge status-pending">Pending</span></a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="admin-empty-copy">
                                <span class="empty-art" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                                <div><h3>Queue clear</h3><p>No pending requests right now.</p></div>
                            </div>
                        <?php endif; ?>
                    </article></div>
                </section>

                <!-- Pending hospital applications are surfaced separately from bookings. -->
                <section class="admin-panel mt-4"><div class="panel-heading"><div><span class="admin-eyebrow">Facility access</span><h2>Hospitals awaiting approval</h2></div><a class="panel-link" href="<?= e(appUrl('admin/hospitals.php')) ?>">Manage hospitals <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
                    <?php if ($pendingHospitals): ?>
                        <div class="table-responsive"><table class="admin-table"><thead><tr><th>Hospital</th><th>City</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>
                            <?php foreach ($pendingHospitals as $hospital): ?><tr><td><strong><?= e($hospital['hospital_name']) ?></strong></td><td><?= e($hospital['city']) ?></td><td><?= e($hospital['email']) ?></td><td><span class="status-badge status-pending">Pending</span></td><td><a class="btn btn-secondary btn-small" href="<?= e(appUrl('admin/hospitals.php')) ?>">Review</a></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?>
                        <div class="admin-empty-copy">
                            <span class="empty-art" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                            <div><h3>All facilities are up to date</h3><p>No facilities are waiting for approval.</p></div>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Chart.js data is encoded safely from server-side result arrays. -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const canvas = document.getElementById('admin-vaccination-chart');
                    if (!canvas || !window.Chart) return;
                    const styles = getComputedStyle(document.documentElement);
                    new Chart(canvas, {
                        type: 'bar',
                        data: { labels: <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, datasets: [{ label: 'Vaccinated', data: <?= json_encode($chartValues) ?>, backgroundColor: styles.getPropertyValue('--purple').trim(), hoverBackgroundColor: styles.getPropertyValue('--purple-dark').trim(), borderRadius: 12, borderSkipped: false, maxBarThickness: 42 }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#fff', titleColor: '#1d2440', bodyColor: '#5a647d', borderColor: 'rgba(88,101,170,.18)', borderWidth: 1, padding: 12, displayColors: false, cornerRadius: 12 } }, scales: { x: { grid: { display: false }, border: { display: false }, ticks: { color: '#5a647d' } }, y: { beginAtZero: true, grid: { color: 'rgba(88,101,170,.08)', drawTicks: false }, border: { display: false }, ticks: { precision: 0, color: '#5a647d', padding: 10 } } } }
                    });
                });
                </script>
                <?php require __DIR__ . '/../includes/admin_layout_end.php'; ?>
