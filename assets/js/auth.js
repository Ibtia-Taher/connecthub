/**
 * Authentication JavaScript
 * Handles registration, login, and real-time validations
 */

// Configuration
const API_BASE = 'http://localhost/connecthub/api';

// Real-time username availability check
let usernameTimeout;
const usernameInput = document.getElementById('username');
const usernameStatus = document.getElementById('usernameStatus');

if (usernameInput) {
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(usernameTimeout);
        
        // Reset status
        usernameStatus.textContent = '';
        usernameStatus.className = 'field-status';
        
        // Validate format first
        if (username.length < 3) {
            return;
        }
        
        if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
            usernameStatus.textContent = 'Invalid format (letters, numbers, underscore only)';
            usernameStatus.classList.add('unavailable');
            return;
        }
        
        // Show checking status
        usernameStatus.textContent = 'Checking...';
        usernameStatus.classList.add('checking');
        
        // Debounce: wait 500ms after user stops typing
        usernameTimeout = setTimeout(() => {
            checkUsernameAvailability(username);
        }, 500);
    });
}

/**
 * Check username availability via AJAX
 */
async function checkUsernameAvailability(username) {
    try {
        const response = await fetch(`${API_BASE}/auth/check-username.php?username=${encodeURIComponent(username)}`);
        const data = await response.json();
        
        if (data.success) {
            usernameStatus.textContent = '✓ Username available';
            usernameStatus.className = 'field-status available';
        } else {
            usernameStatus.textContent = '✗ Username taken';
            usernameStatus.className = 'field-status unavailable';
        }
    } catch (error) {
        console.error('Error checking username:', error);
        usernameStatus.textContent = 'Error checking availability';
        usernameStatus.className = 'field-status unavailable';
    }
}

// Password strength indicator
const passwordInput = document.getElementById('password');
const passwordStrength = document.getElementById('passwordStrength');

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        passwordStrength.textContent = strength.text;
        passwordStrength.className = 'field-status ' + strength.class;
    });
}

/**
 * Calculate password strength
 */
function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    if (score <= 2) return { text: 'Weak password', class: 'unavailable' };
    if (score <= 4) return { text: 'Medium strength', class: 'checking' };
    return { text: 'Strong password', class: 'available' };
}

// Registration form submission
const registerForm = document.getElementById('registerForm');

if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('message');
        
        // Get form data
        const formData = {
            username: document.getElementById('username').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            password: document.getElementById('password').value,
            dob: document.getElementById('dob').value
        };
        
        // Validate password confirmation
        const confirmPassword = document.getElementById('confirmPassword').value;
        if (formData.password !== confirmPassword) {
            showMessage('Passwords do not match', 'error');
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Account...';
        
        try {
            const response = await fetch(`${API_BASE}/auth/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(data.message, 'success');
                
                // Redirect to OTP verification page after 2 seconds
                setTimeout(() => {
                    window.location.href = `verify-otp.php?user_id=${data.data.user_id}&email=${encodeURIComponent(data.data.email)}`;
                }, 2000);
            } else {
                showMessage(data.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Account';
            }
        } catch (error) {
            console.error('Registration error:', error);
            showMessage('An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
    });
}

/**
 * Show message to user
 */
function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = text;
    messageDiv.className = 'message ' + type;
    messageDiv.style.display = 'block';
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}