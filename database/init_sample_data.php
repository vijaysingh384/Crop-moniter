<?php
// Sample data initialization script
include_once('../includes/config.php');

// Clear existing data (be careful with this in production!)
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// Get existing tables
$stmt = $db->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Tables to clear if they exist
$tables = ['users', 'farmer_profiles', 'citizen_profiles', 'crops', 'crop_images', 'crop_analysis', 'pension_schemes', 'pension_claims', 'admin_profiles'];

foreach ($tables as $table) {
    if (in_array($table, $existingTables)) {
        $db->exec("TRUNCATE TABLE {$table}");
        echo "Truncated table: {$table}<br/>";
    } else {
        echo "Skipped table (doesn't exist): {$table}<br/>";
    }
}

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

// Create admin user
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
$stmt->execute(['admin', $adminPassword, 'admin@example.com', 'admin']);
$adminUserId = $db->lastInsertId();

// Create admin profile
$stmt = $db->prepare("INSERT INTO admin_profiles (user_id, full_name, department, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$adminUserId, 'Admin User', 'IT Department', 'System Administrator']);

// Create a farmer user
$farmerPassword = password_hash('farmer123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
$stmt->execute(['farmer', $farmerPassword, 'farmer@example.com', 'farmer']);
$farmerUserId = $db->lastInsertId();

// Create farmer profile
$stmt = $db->prepare("INSERT INTO farmer_profiles (user_id, full_name, phone_number, address, farm_latitude, farm_longitude, location_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$farmerUserId, 'Sample Farmer', '9876543210', '123 Farm Road, Rural District', 28.6139, 77.2090, 'Sample Farm']);
$farmerId = $db->lastInsertId();

// Create some crops for the farmer
$cropTypes = ['Rice', 'Wheat', 'Corn'];
$cropIds = [];

foreach ($cropTypes as $index => $cropType) {
    // Calculate dates
    $plantingDate = date('Y-m-d', strtotime("-{$index} month"));
    $harvestDate = date('Y-m-d', strtotime("+{$index} month"));
    
    $stmt = $db->prepare("
        INSERT INTO crops (farmer_id, crop_name, crop_type, planting_date, field_location, field_size, expected_harvest_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$farmerId, $cropType, $cropType, $plantingDate, "Field {$index}", rand(1, 10), $harvestDate]);
    $cropIds[] = $db->lastInsertId();
}

// Create crop images and analysis
foreach ($cropIds as $cropId) {
    // Create sample image
    $imagePath = "uploads/crop_images/sample_{$cropId}.jpg";
    $stmt = $db->prepare("
        INSERT INTO crop_images (crop_id, image_path, growth_stage, notes) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$cropId, $imagePath, 'Vegetative', 'Sample crop image']);
    $imageId = $db->lastInsertId();
    
    // Create sample analysis
    $stmt = $db->prepare("
        INSERT INTO crop_analysis (crop_id, image_id, soil_health_score, water_needs_score, growth_rate_score, disease_detection, recommendations) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $soilHealth = rand(5, 9);
    $waterNeeds = rand(5, 9);
    $growthRate = rand(5, 9);
    $diseaseStatus = (rand(0, 5) > 4) ? 'Leaf Spot Detected' : 'Healthy';
    $recommendations = "Maintain current irrigation schedule. " . 
                       "Consider applying organic fertilizer to improve soil health. " .
                       "Monitor for pest activity regularly.";
    
    $stmt->execute([$cropId, $imageId, $soilHealth, $waterNeeds, $growthRate, $diseaseStatus, $recommendations]);
}

// Create a citizen user
$citizenPassword = password_hash('citizen123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
$stmt->execute(['citizen', $citizenPassword, 'citizen@example.com', 'citizen']);
$citizenUserId = $db->lastInsertId();

// Create citizen profile
$stmt = $db->prepare("
    INSERT INTO citizen_profiles (user_id, full_name, aadhaar_number, phone_number, address, date_of_birth) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$citizenUserId, 'Sample Citizen', '123456789012', '9876543210', '456 Citizen St, Urban District', '1970-01-01']);
$citizenId = $db->lastInsertId();

// Create pension schemes
$pensionSchemes = [
    ['National Pension System (NPS)', 'Central Government', 'Retirement pension for government employees'],
    ['National Social Assistance Program (NSAP)', 'Central Government', 'Social assistance for elderly, widows, and disabled persons'],
    ['Pradhan Mantri Shram Yogi Maan-dhan (PMSYM)', 'Central Government', 'Pension scheme for unorganized sector workers'],
    ['Employee Pension Scheme (EPS)', 'Central Government', 'Pension for organized sector employees'],
    ['State Pension Scheme', 'State Government', 'State-specific pension scheme']
];

foreach ($pensionSchemes as $scheme) {
    $stmt = $db->prepare("
        INSERT INTO pension_schemes (scheme_name, scheme_provider, description) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute($scheme);
}

// Success message
echo "Sample data initialized successfully!";
echo "<br/><br/>";
echo "Login credentials:<br/>";
echo "Admin: username=admin, password=admin123<br/>";
echo "Farmer: username=farmer, password=farmer123<br/>";
echo "Citizen: username=citizen, password=citizen123<br/>";
?>
