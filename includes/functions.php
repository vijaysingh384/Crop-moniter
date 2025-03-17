<?php
/**
 * General helper functions for the application
 */

/**
 * Sanitize user input
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if username exists
 * 
 * @param PDO $db Database connection
 * @param string $username Username to check
 * @return bool True if exists, false otherwise
 */
function usernameExists($db, $username) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check if email exists
 * 
 * @param PDO $db Database connection
 * @param string $email Email to check
 * @return bool True if exists, false otherwise
 */
function emailExists($db, $email) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Register a new user
 * 
 * @param PDO $db Database connection
 * @param string $username Username
 * @param string $password Password (will be hashed)
 * @param string $email Email
 * @param string $userType User type (farmer, citizen, admin)
 * @return int|bool User ID if successful, false otherwise
 */
function registerUser($db, $username, $password, $email, $userType) {
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $hashedPassword, $email, $userType]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Login a user
 * 
 * @param PDO $db Database connection
 * @param string $username Username
 * @param string $password Password
 * @return array|bool User data if successful, false otherwise
 */
function loginUser($db, $username, $password) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a farmer profile
 * 
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param array $profileData Profile data (full_name, phone_number, address)
 * @return int|bool Farmer ID if successful, false otherwise
 */
function createFarmerProfile($db, $userId, $profileData) {
    try {
        $stmt = $db->prepare("INSERT INTO farmer_profiles (user_id, full_name, phone_number, address) 
                             VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([
            $userId, 
            $profileData['full_name'], 
            $profileData['phone_number'], 
            $profileData['address']
        ]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Create farmer profile error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a citizen profile
 * 
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param array $profileData Profile data (full_name, aadhaar_number, date_of_birth, address, phone_number)
 * @return int|bool Citizen ID if successful, false otherwise
 */
function createCitizenProfile($db, $userId, $profileData) {
    try {
        $stmt = $db->prepare("INSERT INTO citizen_profiles 
                            (user_id, full_name, aadhaar_number, date_of_birth, phone_number, address) 
                            VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $userId, 
            $profileData['full_name'], 
            $profileData['aadhaar_number'],
            $profileData['date_of_birth'],
            $profileData['phone_number'],
            $profileData['address']
        ]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Create citizen profile error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create an admin profile
 * 
 * @param PDO $db Database connection
 * @param int $userId User ID
 * @param array $profileData Profile data (full_name, department, role)
 * @return int|bool Admin ID if successful, false otherwise
 */
function createAdminProfile($db, $userId, $profileData) {
    try {
        $stmt = $db->prepare("INSERT INTO admin_profiles 
                            (user_id, full_name, department, role) 
                            VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([
            $userId, 
            $profileData['full_name'], 
            $profileData['department'],
            $profileData['role']
        ]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Create admin profile error: " . $e->getMessage());
        return false;
    }
}

/**
 * Register a crop for a farmer
 * 
 * @param PDO $db Database connection
 * @param int $farmerId Farmer ID
 * @param array $cropData Crop data
 * @return int|bool Crop ID if successful, false otherwise
 */
function registerCrop($db, $farmerId, $cropData) {
    try {
        $stmt = $db->prepare("INSERT INTO crops 
                            (farmer_id, crop_name, crop_type, planting_date, field_location, field_size, expected_harvest_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $farmerId, 
            $cropData['crop_name'], 
            $cropData['crop_type'],
            $cropData['planting_date'],
            $cropData['field_location'],
            $cropData['field_size'],
            $cropData['expected_harvest_date']
        ]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Register crop error: " . $e->getMessage());
        return false;
    }
}

/**
 * Upload crop image
 * 
 * @param PDO $db Database connection
 * @param int $cropId Crop ID
 * @param array $file File data ($_FILES['image'])
 * @param array $imageData Additional image data
 * @return int|bool Image ID if successful, false otherwise
 */
function uploadCropImage($db, $cropId, $file, $imageData) {
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $uploadPath = CROP_IMAGES_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            $stmt = $db->prepare("INSERT INTO crop_images 
                               (crop_id, image_path, growth_stage, notes) 
                               VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                $cropId, 
                'uploads/crop_images/' . $filename, 
                $imageData['growth_stage'],
                $imageData['notes']
            ]);
            
            if ($result) {
                return $db->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Upload crop image error: " . $e->getMessage());
            // Remove the uploaded file if database insert fails
            unlink($uploadPath);
        }
    }
    
    return false;
}

/**
 * Get weather forecast
 * 
 * @param string $location Location (city name or coordinates)
 * @return array|bool Weather data if successful, false otherwise
 */
function getWeatherForecast($location) {
    // Check if we have a recent cache for this location
    global $db;
    
    $stmt = $db->prepare("SELECT forecast_data FROM weather_data WHERE location = ? AND valid_until > NOW()");
    $stmt->execute([$location]);
    $cached = $stmt->fetch();
    
    if ($cached) {
        return json_decode($cached['forecast_data'], true);
    }
    
    // No cache or expired, fetch from API
    $url = WEATHER_API_URL . "?q=" . urlencode($location) . "&appid=" . WEATHER_API_KEY . "&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $weatherData = json_decode($response, true);
        
        // Cache the data for 3 hours
        $stmt = $db->prepare("INSERT INTO weather_data (location, forecast_data, valid_until) 
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 3 HOUR))
                           ON DUPLICATE KEY UPDATE 
                           forecast_data = VALUES(forecast_data), 
                           valid_until = VALUES(valid_until),
                           last_updated = NOW()");
        $stmt->execute([$location, $response]);
        
        return $weatherData;
    }
    
    return false;
}

/**
 * Perform basic crop analysis based on uploaded image
 * In a real application, this would use machine learning models
 * Here, we just simulate results
 * 
 * @param int $cropId Crop ID
 * @param int $imageId Image ID
 * @return array Analysis data
 */
function analyzeCropImage($cropId, $imageId) {
    // Simulated analysis - in a real app, this would use AI/ML
    $soilHealthScore = rand(50, 100) / 10;
    $waterNeedsScore = rand(30, 100) / 10;
    $growthRateScore = rand(40, 100) / 10;
    
    $possibleDiseases = ['Healthy', 'Leaf Blight', 'Powdery Mildew', 'Root Rot', 'Nutrient Deficiency'];
    $detectedDisease = $possibleDiseases[array_rand($possibleDiseases)];
    
    $recommendations = [
        'Apply adequate fertilizer to improve soil health.',
        'Ensure proper irrigation to meet water needs.',
        'Monitor for pest activity regularly.',
        'Consider applying fungicide to prevent disease spread.',
        'Maintain optimal spacing between plants for better growth.'
    ];
    
    $selectedRecommendations = [];
    for ($i = 0; $i < 2; $i++) {
        $selectedRecommendations[] = $recommendations[array_rand($recommendations)];
    }
    
    $analysisData = [
        'soil_health_score' => $soilHealthScore,
        'water_needs_score' => $waterNeedsScore,
        'growth_rate_score' => $growthRateScore,
        'disease_detection' => $detectedDisease,
        'recommendations' => implode(' ', $selectedRecommendations)
    ];
    
    // Save analysis to database
    global $db;
    
    $stmt = $db->prepare("INSERT INTO crop_analysis 
                       (crop_id, image_id, soil_health_score, water_needs_score, growth_rate_score, 
                        disease_detection, recommendations) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $cropId,
        $imageId,
        $analysisData['soil_health_score'],
        $analysisData['water_needs_score'],
        $analysisData['growth_rate_score'],
        $analysisData['disease_detection'],
        $analysisData['recommendations']
    ]);
    
    $analysisData['analysis_id'] = $db->lastInsertId();
    
    return $analysisData;
}

/**
 * Check for duplicate pension enrollments
 * 
 * @param PDO $db Database connection
 * @param int $citizenId Citizen ID
 * @param int $schemeId New scheme ID being applied for
 * @return array|bool Duplicate information if found, false otherwise
 */
function checkDuplicatePension($db, $citizenId, $schemeId) {
    // Get current pension enrollments
    $stmt = $db->prepare("SELECT cp.enrollment_id, ps.scheme_name, ps.scheme_code 
                        FROM citizen_pensions cp 
                        JOIN pension_schemes ps ON cp.scheme_id = ps.scheme_id 
                        WHERE cp.citizen_id = ? AND cp.verification_status IN ('Verified', 'Pending')");
    $stmt->execute([$citizenId]);
    $enrollments = $stmt->fetchAll();
    
    if (count($enrollments) > 0) {
        // Get citizen details
        $stmt = $db->prepare("SELECT * FROM citizen_profiles WHERE citizen_id = ?");
        $stmt->execute([$citizenId]);
        $citizen = $stmt->fetch();
        
        // Get new scheme details
        $stmt = $db->prepare("SELECT * FROM pension_schemes WHERE scheme_id = ?");
        $stmt->execute([$schemeId]);
        $newScheme = $stmt->fetch();
        
        // Create conflict report
        $duplicateInfo = [
            'citizen' => $citizen,
            'existing_enrollments' => $enrollments,
            'new_scheme' => $newScheme,
            'enrollment_ids' => array_column($enrollments, 'enrollment_id')
        ];
        
        return $duplicateInfo;
    }
    
    return false;
}

/**
 * Flag duplicate pension case
 * 
 * @param PDO $db Database connection
 * @param int $citizenId Citizen ID
 * @param array $duplicateInfo Duplicate information
 * @return int|bool Flag ID if successful, false otherwise
 */
function flagDuplicatePension($db, $citizenId, $duplicateInfo) {
    try {
        $description = "Citizen is already enrolled in: ";
        foreach ($duplicateInfo['existing_enrollments'] as $enrollment) {
            $description .= $enrollment['scheme_name'] . "(" . $enrollment['scheme_code'] . "), ";
        }
        $description .= "and is attempting to enroll in " . $duplicateInfo['new_scheme']['scheme_name'];
        
        $enrollmentIds = json_encode($duplicateInfo['enrollment_ids']);
        
        $stmt = $db->prepare("INSERT INTO flagged_duplicates 
                           (citizen_id, duplicate_description, flagged_enrollments) 
                           VALUES (?, ?, ?)");
        $result = $stmt->execute([
            $citizenId,
            $description,
            $enrollmentIds
        ]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Flag duplicate pension error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log admin action
 * 
 * @param PDO $db Database connection
 * @param int $adminId Admin user ID
 * @param string $actionType Type of action
 * @param string $affectedEntity Type of entity affected
 * @param int $entityId ID of the affected entity
 * @param string $actionDetails Additional action details
 * @return bool True if successful, false otherwise
 */
function logAdminAction($db, $adminId, $actionType, $affectedEntity, $entityId, $actionDetails = '') {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $db->prepare("INSERT INTO admin_logs 
                           (admin_id, action_type, affected_entity, entity_id, action_details, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $adminId,
            $actionType,
            $affectedEntity,
            $entityId,
            $actionDetails,
            $ipAddress
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Log admin action error: " . $e->getMessage());
        return false;
    }
}
