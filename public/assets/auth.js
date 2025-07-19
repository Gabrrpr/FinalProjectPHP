document.addEventListener('DOMContentLoaded', function() {
    // Initialize material design inputs
    initMaterialInputs();
    
    // Initialize password visibility toggles
    initPasswordToggles();
    
    // Initialize form validation
    initFormValidation();
    
    // Add ripple effect to buttons
    initRippleEffects();
    
    // Add floating label effect
    initFloatingLabels();
    
    // Add character counter for text inputs
    initCharacterCounters();
    
    // Add focus/blur effects
    initFocusEffects();
});

function initMaterialInputs() {
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        // Add material design effect
        if (input.value) {
            input.parentElement.classList.add('has-value');
        }
        
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('is-focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('is-focused');
            if (this.value) {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
        
        // Add input event for real-time validation
        input.addEventListener('input', function() {
            if (this.type === 'password') {
                updatePasswordStrength(this.value);
            }
            validateField(this);
        });
    });
}

function initPasswordToggles() {
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    
    togglePasswordBtns.forEach(btn => {
        // Find the password input within the same input-group
        const inputGroup = btn.closest('.input-group');
        if (!inputGroup) return;
        
        const input = inputGroup.querySelector('input[type="password"], input[type="text"]');
        if (!input) return;
        
        // Create eye icon container
        const iconContainer = document.createElement('span');
        iconContainer.className = 'password-toggle-icon';
        iconContainer.innerHTML = `
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
        
        // Replace button content with the icon
        btn.innerHTML = '';
        btn.appendChild(iconContainer);
        
        // Toggle functionality
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = input.type === 'password';
            
            // Toggle input type
            input.type = isPassword ? 'text' : 'password';
            
            // Toggle icon
            iconContainer.innerHTML = isPassword ? `
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
            ` : `
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
            
            // Add animation
            iconContainer.classList.add('animate-toggle');
            setTimeout(() => iconContainer.classList.remove('animate-toggle'), 200);
        });
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('form.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show all error messages
                const invalidFields = this.querySelectorAll(':invalid');
                invalidFields.forEach(field => {
                    validateField(field);
                });
            }
            
            this.classList.add('was-validated');
        });
    });
}

function validateField(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    if (field.checkValidity()) {
        formGroup.classList.remove('is-invalid');
        formGroup.classList.add('is-valid');
        
        // Add checkmark animation
        const checkmark = formGroup.querySelector('.checkmark') || createCheckmark();
        if (!formGroup.contains(checkmark)) {
            formGroup.appendChild(checkmark);
            setTimeout(() => checkmark.classList.add('show'), 10);
        }
    } else if (field.value) {
        formGroup.classList.add('is-invalid');
        formGroup.classList.remove('is-valid');
    } else {
        formGroup.classList.remove('is-valid', 'is-invalid');
    }
}

function updatePasswordStrength(password) {
    if (!password) {
        password = '';
    }
    
    const strengthMeter = document.querySelector('.strength-meter-fill');
    const strengthText = document.querySelector('.strength-text span');
    
    if (!strengthMeter || !strengthText) return;
    
    // Reset strength meter
    let strength = 0;
    let text = 'Very Weak';
    
    // Length check
    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;
    
    // Contains number
    if (/\d/.test(password)) strength += 1;
    
    // Contains special character
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    // Contains both lower and uppercase
    if (/(?=.*[a-z])(?=.*[A-Z])/.test(password)) strength += 1;
    
    // Update UI
    const width = (strength / 5) * 100;
    let color = '#dc3545'; // red
    
    if (strength >= 4) {
        color = '#28a745'; // green
        text = 'Strong';
    } else if (strength >= 3) {
        color = '#17a2b8'; // teal
        text = 'Good';
    } else if (strength >= 2) {
        color = '#ffc107'; // yellow
        text = 'Fair';
    } else if (strength >= 1) {
        color = '#fd7e14'; // orange
        text = 'Weak';
    }
    
    strengthMeter.style.width = `${width}%`;
    strengthMeter.style.backgroundColor = color;
    strengthMeter.setAttribute('data-strength', strength);
    strengthText.textContent = text;
    strengthText.style.color = color;
}

function initRippleEffects() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple element
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            // Get button position and size
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            
            // Position ripple
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            
            // Set ripple size and position
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            // Add ripple to button
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

function initFloatingLabels() {
    const floatLabels = document.querySelectorAll('.float-label');
    
    floatLabels.forEach(container => {
        const input = container.querySelector('input, textarea, select');
        if (!input) return;
        
        // Check if input has value on load
        if (input.value) {
            container.classList.add('has-value');
        }
        
        input.addEventListener('focus', () => {
            container.classList.add('is-focused');
        });
        
        input.addEventListener('blur', () => {
            container.classList.remove('is-focused');
            if (input.value) {
                container.classList.add('has-value');
            } else {
                container.classList.remove('has-value');
            }
        });
    });
}

function initCharacterCounters() {
    const counters = document.querySelectorAll('[data-char-counter]');
    
    counters.forEach(counter => {
        const input = counter.querySelector('input, textarea');
        const maxLength = input.getAttribute('maxlength');
        
        if (!maxLength) return;
        
        const counterElement = document.createElement('div');
        counterElement.className = 'char-counter';
        counterElement.textContent = `0/${maxLength}`;
        
        counter.appendChild(counterElement);
        
        input.addEventListener('input', () => {
            const currentLength = input.value.length;
            counterElement.textContent = `${currentLength}/${maxLength}`;
            
            // Update counter color based on length
            if (currentLength > maxLength * 0.9) {
                counterElement.style.color = '#e53e3e';
            } else if (currentLength > maxLength * 0.75) {
                counterElement.style.color = '#f6ad55';
            } else {
                counterElement.style.color = '#718096';
            }
        });
    });
}

function initFocusEffects() {
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        // Add focus class on focus
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        // Remove focus class on blur if empty
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if input has value on page load
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
}

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
