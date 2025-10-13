<?php
// ==================== CSRF PROTECTION ====================

/**
 * Generate CSRF token for form security
 * 
 * @return string CSRF token
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token to prevent cross-site request forgery
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>