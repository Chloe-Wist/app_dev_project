<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * Provides CSRF token generation and validation
 * for forms and state-changing requests.
 */

// Gets or generates a CSRF token for the current session
function get_csrf_token() {
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

// Validates a submitted CSRF token
function validate_csrf_token($submitted_token) {
    if (!isset($_SESSION["csrf_token"]) ||
        !is_string($submitted_token) ||
        !hash_equals($_SESSION["csrf_token"], $submitted_token)) {
        http_response_code(403);
        exit("Invalid CSRF token.");
    }
}