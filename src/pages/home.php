<?php
// Home page
require_once __DIR__ . '/../../templates/header.php';
?>
<main class="home-main">
    <div class="welcome-box">
        <h1 id="welcome-title"></h1>
        <h2 style="text-align: center; font-size: 3rem; font-weight: 600; color:rgb(181, 181, 36); margin: 1.5rem 0 1rem 0;">Welcome to PoliSys!</h2>
        <p class="subtitle">PoliSys is an Automated Electronic Voting System made to make voting easier, faster, and more secure in the Philippines. <br><br>It takes away the hassle of manual counting and long lines by letting users vote online with just a few clicks. Every vote is safely recorded and counted, all in one smooth process.</p>
        <div class="home-actions">
            <!-- Use these in your navigation/menu -->
            <a href="/FinalProject/public/login">Login</a>
            <a href="/FinalProject/public/register">Sign Up</a>
        </div>
    </div>  
</main>
<script>
// Debug: Check if script is running
console.log('Script loaded');

function initTypingEffect() {
    const titleElement = document.getElementById("welcome-title");
    
    // Debug: Check if element exists
    if (!titleElement) {
        console.error('Error: welcome-title element not found');
        return;
    }
    
    console.log('Element found:', titleElement);
    
    const text = "Welcome to PoliSys!";
    let i = 0;
    
    // Make sure element is visible
    titleElement.style.visibility = 'visible';
    titleElement.style.opacity = '1';
    titleElement.textContent = '';
    
    function typeWriter() {
        if (i < text.length) {
            titleElement.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        }
    }
    
    // Start the typing effect
    typeWriter();
}

// Try both DOMContentLoaded and window.onload to ensure compatibility
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTypingEffect);
} else {
    // DOMContentLoaded has already fired
    initTypingEffect();
}
</script>
<?php
require_once __DIR__ . '/../../templates/footer.php';