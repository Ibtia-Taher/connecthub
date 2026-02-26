/**
 * Profile Edit Handler with OpenStreetMap
 * Fixed version with all features working
 */

let map;
let marker;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing...');
    
    initializeDatepicker();
    initializeOpenStreetMap();
    initializeFormSubmission();
    initializeBioCounter();
    initializeRemoveAvatar();
    initializeCancelButton();
    
    console.log('All components initialized');
});

/**
 * Initialize bio character counter
 */
function initializeBioCounter() {
    const bioTextarea = document.getElementById('bio');
    const bioCount = document.getElementById('bioCount');
    
    if (bioTextarea && bioCount) {
        // Set initial count
        bioCount.textContent = bioTextarea.value.length;
        
        // Update on input
        bioTextarea.addEventListener('input', function() {
            bioCount.textContent = this.value.length;
        });
    }
}

/**
 * Initialize remove avatar button
 */
function initializeRemoveAvatar() {
    const removeBtn = document.getElementById('removeAvatarBtn');
    
    if (removeBtn) {
        removeBtn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to remove your profile picture and reset to default?')) {
                return;
            }
            
            // Disable button during request
            removeBtn.disabled = true;
            removeBtn.textContent = 'Resetting...';
            
            try {
                const response = await fetch(window.API_BASE + '/users/reset-avatar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update preview to default avatar
                    const defaultAvatar = data.data.url;
                    const avatarPreview = document.getElementById('avatarPreview');
                    avatarPreview.src = defaultAvatar;
                    avatarPreview.dataset.original = defaultAvatar;
                    
                    showMessage('Profile picture reset to default successfully!', 'success');
                    
                    // Hide remove button since we're now using default
                    removeBtn.style.display = 'none';
                } else {
                    showMessage(data.message || 'Failed to reset avatar', 'error');
                    removeBtn.disabled = false;
                    removeBtn.textContent = 'Remove Current Picture';
                }
            } catch (error) {
                console.error('Error removing avatar:', error);
                showMessage('Error resetting avatar. Please try again.', 'error');
                removeBtn.disabled = false;
                removeBtn.textContent = 'Remove Current Picture';
            }
        });
    }
}

/**
 * Initialize cancel button
 */
function initializeCancelButton() {
    const cancelBtn = document.getElementById('cancelBtn');
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            window.location.href = 'profile.php';
        });
    }
}

/**
 * Initialize Flatpickr datepicker
 */
function initializeDatepicker() {
    const dobInput = document.getElementById('dob');
    
    if (dobInput && typeof flatpickr !== 'undefined') {
        console.log('Initializing datepicker...');
        
        // Calculate max date (13 years ago)
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() - 13);
        
        flatpickr(dobInput, {
            dateFormat: 'Y-m-d',
            maxDate: maxDate,
            defaultDate: dobInput.value || null,
            allowInput: true,
            onChange: function(selectedDates, dateStr) {
                console.log('Date selected:', dateStr);
                
                // Validate age
                if (selectedDates.length > 0) {
                    const birthDate = selectedDates[0];
                    const today = new Date();
                    const age = today.getFullYear() - birthDate.getFullYear();
                    
                    if (age < 13) {
                        showMessage('You must be at least 13 years old', 'error');
                        dobInput.value = '';
                    }
                }
            }
        });
        
        console.log('Datepicker initialized successfully');
    } else {
        console.error('Flatpickr not loaded or DOB input not found');
    }
}

/**
 * Initialize OpenStreetMap
 */
function initializeOpenStreetMap() {
    const mapElement = document.getElementById('map');
    
    if (!mapElement) {
        console.error('Map element not found');
        return;
    }
    
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded');
        return;
    }
    
    console.log('Initializing map...');
    
    // Default location (Chittagong, Bangladesh)
    let lat = 22.3569;
    let lng = 91.7832;
    
    // Get stored location if exists
    const storedLat = document.getElementById('latitude').value;
    const storedLng = document.getElementById('longitude').value;
    
    if (storedLat && storedLng) {
        lat = parseFloat(storedLat);
        lng = parseFloat(storedLng);
    }
    
    console.log('Map center:', lat, lng);
    
    // Initialize map
    map = L.map('map').setView([lat, lng], 12);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add draggable marker
    marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);
    
    // Update coordinates when marker is dragged
    marker.on('dragend', function(event) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
        reverseGeocode(position.lat, position.lng);
    });
    
    // Click on map to set location
    map.on('click', function(e) {
        const clickLat = e.latlng.lat;
        const clickLng = e.latlng.lng;
        
        marker.setLatLng([clickLat, clickLng]);
        updateCoordinates(clickLat, clickLng);
        reverseGeocode(clickLat, clickLng);
    });
    
    console.log('Map initialized successfully');
    
    // Search button
    const searchBtn = document.getElementById('searchLocationBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', searchLocation);
    }
    
    // Enter key to search
    const locationInput = document.getElementById('location');
    if (locationInput) {
        locationInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchLocation();
            }
        });
    }
}

/**
 * Search for location using PHP proxy
 */
async function searchLocation() {
    const locationInput = document.getElementById('location');
    const searchQuery = locationInput.value.trim();
    
    if (!searchQuery) {
        showMessage('Please enter a location to search', 'error');
        return;
    }
    
    console.log('Searching for:', searchQuery);
    showMessage('Searching...', 'success');
    
    try {
        // Use our PHP proxy to avoid CORS issues
        const response = await fetch(
            window.API_BASE + `/location/geocode.php?q=${encodeURIComponent(searchQuery)}`
        );
        
        const data = await response.json();
        
        if (data.error) {
            showMessage(data.error, 'error');
            return;
        }
        
        console.log('Search results:', data);
        
        if (data.length > 0) {
            const result = data[0];
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);
            
            console.log('Found location:', lat, lng);
            
            // Update map
            map.setView([lat, lng], 14);
            marker.setLatLng([lat, lng]);
            
            // Update form
            updateCoordinates(lat, lng);
            locationInput.value = result.display_name;
            
            showMessage('Location found!', 'success');
        } else {
            showMessage('Location not found. Try: "Chittagong, Bangladesh" or "Dhaka"', 'error');
        }
    } catch (error) {
        console.error('Geocoding error:', error);
        showMessage('Error searching location. Please try again.', 'error');
    }
}

/**
 * Reverse geocode using PHP proxy
 */
async function reverseGeocode(lat, lng) {
    try {
        // Small delay to avoid rate limiting
        await new Promise(resolve => setTimeout(resolve, 500));
        
        const response = await fetch(
            window.API_BASE + `/location/reverse-geocode.php?lat=${lat}&lon=${lng}`
        );
        
        const data = await response.json();
        
        if (data.error) {
            console.warn('Reverse geocoding not available:', data.error);
            // Still update with coordinates
            document.getElementById('location').value = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
            return;
        }
        
        if (data.display_name) {
            document.getElementById('location').value = data.display_name;
        }
    } catch (error) {
        console.warn('Reverse geocoding failed, showing coordinates instead:', error);
        // Fallback to showing coordinates
        document.getElementById('location').value = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
    }
}

/**
 * Update coordinates
 */
function updateCoordinates(lat, lng) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    console.log('Coordinates updated:', lat, lng);
}

/**
 * Initialize form submission
 */
function initializeFormSubmission() {
    const form = document.getElementById('editProfileForm');
    
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log('Form submitted');
        
        const saveBtn = document.getElementById('saveBtn');
        
        // Get form data
        const formData = {
            bio: document.getElementById('bio').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            date_of_birth: document.getElementById('dob').value,
            location: document.getElementById('location').value.trim(),
            latitude: document.getElementById('latitude').value,
            longitude: document.getElementById('longitude').value
        };
        
        console.log('Form data:', formData);
        
        // Validate phone number
        if (formData.phone && !/^[0-9+\-\s()]{10,20}$/.test(formData.phone)) {
            showMessage('Invalid phone number format', 'error');
            return;
        }
        
        // Disable button
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        try {
            const response = await fetch(window.API_BASE + '/users/update-profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                showMessage('Server error. Check console for details.', 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
                return;
            }
            
            console.log('Response data:', data);
            
            if (data.success) {
                showMessage(data.message, 'success');
                
                // Redirect to profile after 2 seconds
                setTimeout(() => {
                    window.location.href = 'profile.php';
                }, 2000);
            } else {
                showMessage(data.message, 'error');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        } catch (error) {
            console.error('Update error:', error);
            showMessage('Error updating profile. Please try again.', 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    });
    
    console.log('Form submission handler attached');
}