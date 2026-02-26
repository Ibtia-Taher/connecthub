/**
 * Profile Edit Handler
 * Handles profile form submission, datepicker, and Google Maps
 */

const API_BASE = 'http://localhost/connecthub/api';
let map;
let marker;
let autocomplete;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentProfileData();
    initializeDatepicker();
    initializeGoogleMaps();
    initializeFormSubmission();
});

/**
 * Load current user profile data
 */
async function loadCurrentProfileData() {
    try {
        const response = await fetch(`${API_BASE}/users/get-profile.php?user_id=${getCurrentUserIdFromSession()}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data.user;
            
            // Populate form fields
            document.getElementById('bio').value = user.bio || '';
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('dob').value = user.date_of_birth || '';
            document.getElementById('location').value = user.location || '';
            document.getElementById('latitude').value = user.latitude || '';
            document.getElementById('longitude').value = user.longitude || '';
            
            // Update map if coordinates exist
            if (user.latitude && user.longitude) {
                updateMapLocation(parseFloat(user.latitude), parseFloat(user.longitude));
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

/**
 * Get current user ID from session
 * This is a helper - in production, get from PHP session
 */
function getCurrentUserIdFromSession() {
    // For now, we'll extract from current page context
    // In edit-profile.php, we have access to PHP session
    // This is just a placeholder for the JavaScript
    return window.currentUserId || 1; // Will be set by PHP
}

/**
 * Initialize Flatpickr datepicker
 */
function initializeDatepicker() {
    const dobInput = document.getElementById('dob');
    
    if (dobInput) {
        flatpickr(dobInput, {
            dateFormat: 'Y-m-d',
            maxDate: new Date().fp_incr(-13*365), // Must be 13+ years old
            defaultDate: dobInput.value || null,
            yearSelectorType: 'dropdown',
            monthSelectorType: 'dropdown',
            onChange: function(selectedDates, dateStr) {
                // Validate age
                const birthDate = new Date(dateStr);
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                
                if (age < 13) {
                    showMessage('You must be at least 13 years old', 'error');
                    dobInput.value = '';
                }
            }
        });
    }
}

/**
 * Initialize Google Maps
 */

function initializeGoogleMaps() {
    const mapElement = document.getElementById('map');
    const locationInput = document.getElementById('location');
    // Fallback if Google Maps not available
    if (typeof google === 'undefined') {
        console.warn('Google Maps not available - using simple input');
        mapElement.style.display = 'none';
        mapElement.previousElementSibling.style.display = 'none'; // Hide "Start typing" text
        return;
    }
    else if (!mapElement || typeof google === 'undefined') {
        console.warn('Google Maps not available');
        return;
    }
    
    // Default location (Chittagong, Bangladesh)
    const defaultLocation = { lat: 22.3569, lng: 91.7832 };
    
    // Get stored location or use default
    const lat = parseFloat(document.getElementById('latitude').value) || defaultLocation.lat;
    const lng = parseFloat(document.getElementById('longitude').value) || defaultLocation.lng;
    
    // Initialize map
    map = new google.maps.Map(mapElement, {
        center: { lat, lng },
        zoom: 12,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });
    
    // Add marker
    marker = new google.maps.Marker({
        position: { lat, lng },
        map: map,
        draggable: true,
        title: 'Your Location'
    });
    
    // Update coordinates when marker is dragged
    marker.addListener('dragend', function(event) {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Reverse geocode to get address
        reverseGeocode(lat, lng);
    });
    
    // Initialize autocomplete
    autocomplete = new google.maps.places.Autocomplete(locationInput, {
        types: ['geocode', 'establishment']
    });
    
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        
        if (!place.geometry) {
            showMessage('No location found for this search', 'error');
            return;
        }
        
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();
        
        // Update map and marker
        updateMapLocation(lat, lng);
        
        // Update hidden fields
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Update location field with formatted address
        locationInput.value = place.formatted_address || place.name;
    });
}

/**
 * Update map location and marker
 */
function updateMapLocation(lat, lng) {
    if (!map || !marker) return;
    
    const location = { lat, lng };
    
    map.setCenter(location);
    map.setZoom(14);
    marker.setPosition(location);
}

/**
 * Reverse geocode coordinates to address
 */
function reverseGeocode(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = { lat, lng };
    
    geocoder.geocode({ location: latlng }, function(results, status) {
        if (status === 'OK' && results[0]) {
            document.getElementById('location').value = results[0].formatted_address;
        }
    });
}

/**
 * Initialize form submission
 */
function initializeFormSubmission() {
    const form = document.getElementById('editProfileForm');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
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
            
            // Validate phone number
            if (formData.phone && !/^[0-9+\-\s()]{10,20}$/.test(formData.phone)) {
                showMessage('Invalid phone number format', 'error');
                return;
            }
            
            // Disable button
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            
            try {
                const response = await fetch(`${API_BASE}/users/update-profile.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
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
    }
}