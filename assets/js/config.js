/**
 * Global JavaScript Configuration
 * Loaded before all other scripts
 */

// Only declare if not already declared
if (typeof API_BASE === 'undefined') {
    var API_BASE = 'http://localhost/connecthub/api';
}

if (typeof APP_URL === 'undefined') {
    var APP_URL = 'http://localhost/connecthub';
}