<?php
/**
 * mysqli Extension Test Script
 * This script checks if mysqli is properly installed and configured
 */

echo "<h2>mysqli Extension Test</h2>";

// Check if mysqli class exists
if (class_exists('mysqli')) {
    echo "<p style='color: green; font-weight: bold;'>✓ mysqli class is available!</p>";
    
    // Test connection
    $conn = @new mysqli($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);
    
    if ($conn->connect_error) {
        echo "<p style='color: orange;'>⚠ mysqli is installed but connection failed: " . $conn->connect_error . "</p>";
        echo "<p>This is normal if MySQL server is not running. Start MySQL from XAMPP Control Panel.</p>";
    } else {
        echo "<p style='color: green;'>✓ Successfully connected to MySQL server!</p>";
        echo "<p>mysqli version: " . $conn->server_info . "</p>";
        $conn->close();
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ mysqli class is NOT available</p>";
    echo "<p>Please follow the steps to enable mysqli extension.</p>";
}

// Show PHP version
echo "<hr>";
echo "<h3>PHP Configuration</h3>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Show loaded extensions
echo "<p><strong>Loaded Extensions:</strong></p>";
echo "<ul>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    // if (stripos($ext, 'mysql') !== false || stripos($ext, 'pdo') !== false) {
        echo "<li style='color: green; font-weight: bold;'>$ext</li>";
    // }
}
echo "</ul>";

// Check if mysqli is in the list
if (in_array('mysqli', $extensions)) {
    echo "<p style='color: green;'>✓ mysqli is in the loaded extensions list</p>";
} else {
    echo "<p style='color: red;'>✗ mysqli is NOT in the loaded extensions list</p>";
}

// Show php.ini location
echo "<hr>";
echo "<h3>PHP Configuration File</h3>";
echo "<p><strong>Loaded php.ini:</strong> " . php_ini_loaded_file() . "</p>";

// Check for additional ini files
$additional_ini = php_ini_scanned_files();
if ($additional_ini) {
    echo "<p><strong>Additional .ini files:</strong><br>";
    echo nl2br($additional_ini) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go back to Customer Form</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2, h3 {
        color: #333;
    }
    a {
        display: inline-block;
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 10px;
    }
    a:hover {
        background: #5568d3;
    }
    ul {
        column-count: 3;
        list-style-type: none;
    }
</style>