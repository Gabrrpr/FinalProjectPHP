<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
require_once __DIR__ . '/../../templates/header.php';
?>


<main>
    <div class="dashboard-container">
        <div class="dashboard-avatar">
            <img src="/FinalProject/public/assets/img/user-512.jpg" alt="Profile Avatar">
        </div>
        <div class="dashboard-details">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <p>You’re logged in as an <strong>admin</strong>. From here, you can create and manage elections, oversee user activity, and review results.</p>
                <p>Use the menu above to navigate through the system’s admin tools.</p>

            <?php elseif ($_SESSION['role'] === 'voter'): ?>
                <p>You’re logged in as a <strong>voter</strong>. You’ll be able to view available elections and cast your vote through the navigation menu.</p>
                <p>Make sure to check the elections section to see what’s currently available for you.</p>
            <?php endif; ?>

            <div class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../../templates/footer.php';