<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'crop_monitoring_system');

// Application settings
define('APP_NAME', 'Government Services Portal');
define('APP_URL', 'http://localhost/Crop');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('CROP_IMAGES_DIR', UPLOAD_DIR . 'crop_images/');

// Ensure upload directories exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(CROP_IMAGES_DIR)) {
    mkdir(CROP_IMAGES_DIR, 0777, true);
}

// Weather API Configuration (example - replace with your API key)
define('WEATHER_API_KEY', 'your_api_key_here');
define('WEATHER_API_URL', 'https://api.openweathermap.org/data/2.5/forecast');

// Connect to the database
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
