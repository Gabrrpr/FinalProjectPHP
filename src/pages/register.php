<?php
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate inputs
    if (!$username || !$password || !in_array($role, ['admin','voter'])) {
        $errors[] = 'All fields are required and role must be valid.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 chars, letters/numbers/underscore only.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $db = get_db();
        // Check if username exists
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $errors[] = 'Username already taken.';
        } else {
            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $hash, $role);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<main>
    <div class="login-container">
        <h2>Create Your Account</h2>
        <p class="description">Create an account to start voting online with <strong>PoliSys</strong> in just a few steps.</p>

        <?php if (!empty($success)): ?>
            <div class="message-success">
                Registration successful! You may now <a href="login">login</a>.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <ul style="color: red; margin-bottom: 1em;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="register">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="role">Select Role</label>
            <select name="role" id="role" required>
                <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>-- Select a role --</option>
                <option value="voter" <?= ($_POST['role'] ?? '') === 'voter' ? 'selected' : '' ?>>Voter</option>
                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit">Register</button>
        </form>

        <a href="login" class="register-link">Already have an account? Login here</a>
    </div>
</main>

<?php
require_once __DIR__ . '/../../templates/footer.php';
