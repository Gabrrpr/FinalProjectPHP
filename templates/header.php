<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automated Electronic Voting System</title>
    <link rel="stylesheet" href="/FinalProject/public/assets/style.css?v=2">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo-container">
            <img src="/FinalProject/public/assets/img/Logo.png" alt="PoliSys Logo" class="site-logo">
            <div class="marquee-container">
                <div class="marquee">
                    <span>• Welcome to PoliSys - Your Secure Voting Platform • Cast Your Vote Securely • Election Results in Real-Time •</span>
                </div>
            </div>
        </div>
        <nav>
            <?php if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'admin.php'): ?>
                <a href="/FinalProject/public/">Home</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/FinalProject/public/dashboard">Dashboard</a>
                <a href="/FinalProject/public/vote">Vote</a>
                <a href="/FinalProject/public/results">Results</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="/FinalProject/public/admin">Admin</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/FinalProject/public/logout">Logout</a>
            <?php else: ?>
                <a href="/FinalProject/public/login">Login</a>
                <a href="/FinalProject/public/register">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
    <hr>
</header>

<style>
/* Header Layout */
header {
    padding: 5px 0 0 0;
    min-height: auto;
    background: #fff4d2;
    background: radial-gradient(circle farthest-side at center center, #fff4d2 0%, #ffce6c 80%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-content {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.logo-container {
    text-align: center;
    padding: 5px 0 0 0;
}

.marquee-container {
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
    background: rgba(0,0,0,0.05);
    margin: 3px 0 5px 0;
    padding: 3px 0;
    border-radius: 4px;
}

.marquee {
    display: inline-block;
    padding-left: 100%;
    animation: marquee 25s linear infinite;
}

.marquee span {
    display: inline-block;
    padding-right: 100%;
    color: #036a5f;
    font-size: 0.85em;
    font-weight: 500;
}

@keyframes marquee {
    from { transform: translateX(0); }
    to { transform: translateX(-100%); }
}

.site-logo {
    max-width: 120px;
    height: auto;
    display: block;
    margin: 0 auto;
}

/* Navigation */
header nav {
    padding: 8px 0;
    text-align: center;
}

nav a {
    background: #036a5f;
    background: linear-gradient(90deg,#036a5f 0%, #00a293 80%);
    color: #fff4d2;
    padding: 0.3em 1em;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    border: 1.5px solid #f3be56;
    box-shadow: 0 2px 8px rgba(1,68,33,0.15);
    transition: all 0.2s ease;
    font-size: 0.9em;
    display: inline-block;
    margin: 0 3px;
}

nav a:hover {
    background: #fff4d2;
    color: #036a5f;
    border: 1.5px solid #036a5f;
    transform: translateY(-1px);
}

/* Hide the h1 text but keep it for accessibility */
h1 {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
<main>
