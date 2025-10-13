<?php
// ==================== CONFIGURATION CONSTANTS ====================

// Database configuration
define('DB_HOST', $_ENV['MYSQL_HOST']);
define('DB_USER', $_ENV['MYSQL_USER']);
define('DB_PASS', $_ENV['MYSQL_PASSWORD']);
define('DB_NAME', $_ENV['MYSQL_DATABASE']);
define('DB_CHARSET', 'utf8mb4');

// Upload configuration
define('UPLOAD_DIR', getcwd() . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
define('ALLOWED_MIME_TYPES', ['image/jpeg']);
?>
