<?php
/**
 * DiabetaCare - Logout Handler
 */

// Call logout API if authenticated
if (isAuthenticated()) {
    api()->logout();
}

// Clear local session
clearAuth();

// Redirect to login
redirect('/login');
