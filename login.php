<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$pdo = db();
$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginError = 'Please enter both email and password.';
    } elseif ($pdo) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $loginError = 'Invalid email or password.';
        } elseif (!password_verify($password, $user['password'])) {
            $loginError = 'Invalid email or password.';
        } elseif ($user['status'] === 'blocked') {
            $loginError = 'Your account has been blocked. Please contact the administrator.';
        } elseif ($user['status'] === 'pending') {
            $loginError = 'Your account is awaiting approval.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = $user['role'];

            $redirect = match ($user['role']) {
                'admin' => 'admin/dashboard.php',
                'parent' => 'parent/dashboard.php',
                'hospital' => 'hospital/dashboard.php',
                default => 'index.php',
            };

            redirect($redirect);
        }
    } else {
        $loginError = 'Database connection is not available at the moment. Please try again later.';
    }
}
?>

<main class="auth-page">
    <div class="container auth-shell">
        <div class="auth-card">
            <a href="index.php" class="back-link">← Back</a>
            <div class="auth-header">
                <span class="eyebrow">Welcome back</span>
                <h1>Login to your account</h1>
            </div>

            <?php if ($loginError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(ensureCsrfToken()); ?>">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" data-target="password">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary full-width">Login</button>
            </form>

            <p class="auth-switch">
                Need an account?
                <a href="register_parent.php">Register as Parent</a>
                or <a href="register_hospital.php">Register Hospital</a>
            </p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
