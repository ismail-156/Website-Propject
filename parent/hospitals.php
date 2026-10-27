<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
$pid = get_parent_id($pdo, (int) $_SESSION['user_id']);
$activePage = 'hospitals';
$pageTitle = 'Hospitals';
$search = trim((string) ($_GET['q'] ?? ''));

// Search is parameterized and the display uses the existing hospital column naming.
$sql = "SELECT h.id AS hospital_id, h.hospital_name AS name, h.address, h.city, h.location, h.phone
        FROM hospitals h
        INNER JOIN users u ON u.id = h.user_id AND u.status = 'active'";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE h.hospital_name LIKE :name OR h.city LIKE :city';
    $params = ['name' => '%' . $search . '%', 'city' => '%' . $search . '%'];
}
$sql .= ' ORDER BY h.hospital_name LIMIT 60';
$hospitalStmt = $pdo->prepare($sql);
$hospitalStmt->execute($params);
$hospitals = $hospitalStmt->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/../includes/parent_layout_start.php';
?>
<!-- Hospital finder with a lightweight name/city search. -->
<section class="parent-heading">
    <span class="parent-eyebrow">Care near you</span>
    <h1>Find a hospital</h1>
    <p>Explore vaccination providers and choose a convenient place to visit.</p>
</section>
<form class="parent-search mb-4" method="get" action="<?= e(parent_path('hospitals.php')) ?>" role="search">
    <i class="bi bi-search" aria-hidden="true"></i>
    <label class="visually-hidden" for="hospital-search">Search by hospital name or city</label>
    <input id="hospital-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Search by hospital name or city">
    <button class="btn btn-primary parent-btn-small" type="submit">Search</button>
</form>
<?php if ($hospitals): ?>
    <section class="row g-4" aria-label="Hospitals">
        <?php foreach ($hospitals as $hospital): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <article class="hospital-card">
                    <span class="parent-stat-icon pink mb-3"><i class="bi bi-hospital" aria-hidden="true"></i></span>
                    <h3><?= e($hospital['name']) ?></h3>
                    <div class="hospital-details">
                        <p><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?= e($hospital['address']) ?>, <?= e($hospital['city']) ?></span></p>
                        <p><i class="bi bi-pin-map" aria-hidden="true"></i><span><?= e($hospital['location']) ?></span></p>
                        <p><i class="bi bi-telephone" aria-hidden="true"></i><span><?= e($hospital['phone']) ?></span></p>
                    </div>
                    <div class="hospital-card-actions">
                        <a class="btn btn-primary parent-btn-small" href="<?= e(parent_path('book.php')) ?>?hospital_id=<?= (int) $hospital['hospital_id'] ?>">Book here</a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="parent-card empty-state">
        <span class="empty-art"><i class="bi bi-hospital" aria-hidden="true"></i></span>
        <h3><?= $search !== '' ? 'No matches this time' : 'No hospitals listed yet' ?></h3>
        <p><?= $search !== '' ? 'Try another hospital name or city.' : 'Hospital listings will appear here when providers are added.' ?></p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/parent_layout_end.php'; ?>
