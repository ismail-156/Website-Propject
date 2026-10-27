<?php
require_once __DIR__ . '/includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
        $errors[] = 'Full name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 150) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        $errors[] = 'Enter a valid phone number.';
    }

    if ($address === '' || mb_strlen($address, 'UTF-8') > 255) {
        $errors[] = 'Address is required.';
    }

    if ($city === '' || mb_strlen($city, 'UTF-8') > 100) {
        $errors[] = 'City is required.';
    }

    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])
        || !hash_equals(ensureCsrfToken(), $_POST['csrf_token'])) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }

    $pdo = db();
    if (empty($errors) && !$pdo instanceof PDO) {
        $errors[] = 'The database is unavailable. Please try again later.';
    }

    if (empty($errors) && $pdo instanceof PDO) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);

        if ($existing->fetch()) {
            $errors[] = 'This email is already registered.';
        }
    }

    if (empty($errors) && $pdo instanceof PDO) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'parent',
                'status' => 'active',
            ]);

            $userId = (int) $pdo->lastInsertId();

            $parentStmt = $pdo->prepare('INSERT INTO parents (user_id, phone, address, city) VALUES (:user_id, :phone, :address, :city)');
            $parentStmt->execute([
                'user_id' => $userId,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
            ]);

            $pdo->commit();
            flash('success', 'Parent registration successful. Please log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Parent registration failed: ' . $e->getMessage());
            $errors[] = $e->getCode() === '23000'
                ? 'This email is already registered. Please use another email or log in.'
                : 'We could not save your registration. Please try again or contact support if the problem continues.';
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="auth-page">
    <div class="container auth-shell">
        <div class="auth-card">
            <a href="index.php" class="back-link">← Back</a>
            <div class="auth-header">
                <span class="eyebrow">Parent Registration</span>
                <h1>Create your parent account</h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(ensureCsrfToken()); ?>">

                <div class="form-row two-col">
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row two-col">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" minlength="8" required>
                            <button type="button" class="toggle-password" data-target="password">Show</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                            <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                        </div>
                    </div>
                </div>

                <div class="form-row two-col">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+92 300 1234567" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary full-width">Register</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
