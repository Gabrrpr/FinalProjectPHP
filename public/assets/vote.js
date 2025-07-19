// Voting Interface Interactions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize countdown timer if there's an active election
    initCountdown();
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add visual feedback for vote buttons
    document.querySelectorAll('.vote-button').forEach(button => {
        button.addEventListener('click', function() {
            // Add pulse animation
            this.classList.add('pulse');
            setTimeout(() => this.classList.remove('pulse'), 500);
            
            // Show confirmation dialog
            const candidateName = this.closest('.candidate-card').querySelector('.candidate-name').textContent;
            if (confirm(`Are you sure you want to vote for ${candidateName}?`)) {
                // Submit the form
                this.closest('form').submit();
                // Show success animation
                if (typeof confetti === 'function') {
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
            }
        });
    });
});

// Initialize countdown timer for active elections
function initCountdown() {
    const timerElement = document.getElementById('countdown-timer');
    if (!timerElement) return;
    
    // Get the end time from the first active election (you can modify this based on your data)
    const electionEndElements = document.querySelectorAll('.election-end-time');
    if (electionEndElements.length === 0) return;
    
    // Find the nearest end time
    let nearestEndTime = null;
    electionEndElements.forEach(el => {
        const endTime = new Date(el.dataset.endTime).getTime();
        if (!nearestEndTime || endTime < nearestEndTime) {
            nearestEndTime = endTime;
        }
    });
    
    if (!nearestEndTime) return;
    
    // Update the countdown every second
    const countdownInterval = setInterval(() => {
        const now = new Date().getTime();
        const distance = nearestEndTime - now;
        
        // If the countdown is over, clear the interval
        if (distance < 0) {
            clearInterval(countdownInterval);
            timerElement.textContent = 'Voting Closed';
            return;
        }
        
        // Calculate time remaining
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Display the result
        timerElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

// Add smooth scroll to top button
const scrollToTopBtn = document.createElement('button');
scrollToTopBtn.innerHTML = '↑';
scrollToTopBtn.className = 'scroll-to-top';
document.body.appendChild(scrollToTopBtn);

// Show/hide scroll to top button
window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.add('show');
    } else {
        scrollToTopBtn.classList.remove('show');
    }
});

// Scroll to top on click
scrollToTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
