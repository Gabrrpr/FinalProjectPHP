<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($username) || empty($password)) {
        $errors[] = 'Both username and password are required.';
    }

    if (empty($errors)) {
        $db = get_db();
        $stmt = $db->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, start a new session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: /FinalProject/public/admin');
            } else {
                header('Location: /FinalProject/public/dashboard');
            }
            exit();
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

// Now include the header after all processing is done
$GLOBALS['additional_css'] = ['/FinalProject/public/assets/auth.css'];
require_once __DIR__ . '/../../templates/header.php';
?>

<main class="auth-page">
    <div class="login-container">
        <div class="auth-header">
            <h1>PoliSys</h1>
        </div>
        <h2>Access Your Account</h2>
        <p class="description">Login to your <strong>PoliSys</strong> account and cast your vote securely and easily.</p>

        <?php if ($errors): ?>
            <ul style="color: red; margin-bottom: 1em;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="login">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Login</button>
        </form>

        <a href="register" class="register-link">Don’t have an account? Register here</a>
    </div>
</main>

<?php
require_once __DIR__ . '/../../templates/footer.php';