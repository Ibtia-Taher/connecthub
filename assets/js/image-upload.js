/**
 * Image Upload Handler
 * Handles avatar preview and upload
 */

const avatarInput = document.getElementById('avatarInput');
const avatarPreview = document.getElementById('avatarPreview');

let selectedAvatarFile = null;

// Handle file selection
if (avatarInput) {
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (!file) {
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please use JPG, PNG, GIF, or WEBP.');
            avatarInput.value = '';
            return;
        }
        
        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            alert('File too large. Maximum size is 5MB.');
            avatarInput.value = '';
            return;
        }
        
        // Store file for later upload
        selectedAvatarFile = file;
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            avatarPreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
        
        // Automatically upload avatar
        uploadAvatar(file);
    });
}

/**
 * Upload avatar to server
 */
async function uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    
    try {
        // Show loading state
        const label = document.querySelector('.file-input-label');
        const originalText = label.textContent;
        label.textContent = 'Uploading...';
        label.style.pointerEvents = 'none';
        
        const response = await fetch(window.API_BASE + '/users/upload-avatar.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Profile picture updated successfully!', 'success');
            
            // Update preview with server URL
            avatarPreview.src = data.data.url;
            avatarPreview.dataset.original = data.data.url;
            
            // Show remove button since we now have a custom avatar
            const removeBtn = document.getElementById('removeAvatarBtn');
            if (removeBtn) {
                removeBtn.style.display = 'inline-block';
            }
            
            // Clear file input
            avatarInput.value = '';
            selectedAvatarFile = null;
        } else {
            showMessage(data.message, 'error');
            // Revert preview
            avatarPreview.src = avatarPreview.dataset.original || avatarPreview.src;
        }
        
        // Restore button
        label.textContent = originalText;
        label.style.pointerEvents = 'auto';
        
    } catch (error) {
        console.error('Upload error:', error);
        showMessage('Error uploading image. Please try again.', 'error');
        
        // Restore button
        const label = document.querySelector('.file-input-label');
        label.textContent = 'Choose New Picture';
        label.style.pointerEvents = 'auto';
    }
}

/**
 * Show message to user
 */
function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) {
        console.log('Message div not found');
        return;
    }
    
    messageDiv.textContent = text;
    messageDiv.className = 'message ' + type;
    messageDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}