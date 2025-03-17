<?php
// Upload Crop Data Page

// Get farmer ID
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT farmer_id FROM farmer_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$farmer = $stmt->fetch();

if (!$farmer) {
    echo '<div class="alert alert-warning">Your farmer profile is not complete. Please update your profile.</div>';
    // In a real app, redirect to profile completion page
} else {
    $farmerId = $farmer['farmer_id'];
    
    // Check if we're uploading data for an existing crop
    $cropId = isset($_GET['crop_id']) ? (int)$_GET['crop_id'] : null;
    $uploadType = isset($_GET['type']) && $_GET['type'] === 'image' ? 'image' : 'crop';
    
    // If crop_id is provided, verify it belongs to this farmer
    if ($cropId) {
        $stmt = $db->prepare("SELECT * FROM crops WHERE crop_id = ? AND farmer_id = ?");
        $stmt->execute([$cropId, $farmerId]);
        $crop = $stmt->fetch();
        
        if (!$crop) {
            $cropId = null; // Reset if not found or not owned by this farmer
        }
    }
    
    // Handle crop registration form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_crop'])) {
        $cropName = sanitize($_POST['crop_name']);
        $cropType = sanitize($_POST['crop_type']);
        $plantingDate = sanitize($_POST['planting_date']);
        $fieldLocation = sanitize($_POST['field_location']);
        $fieldSize = (float)$_POST['field_size'];
        $expectedHarvestDate = sanitize($_POST['expected_harvest_date']);
        
        // Validate inputs
        $errors = [];
        
        if (empty($cropName)) {
            $errors[] = "Crop name is required";
        }
        
        if (empty($cropType)) {
            $errors[] = "Crop type is required";
        }
        
        if (empty($plantingDate)) {
            $errors[] = "Planting date is required";
        }
        
        if (empty($expectedHarvestDate)) {
            $errors[] = "Expected harvest date is required";
        }
        
        // If no errors, register the crop
        if (empty($errors)) {
            $cropData = [
                'crop_name' => $cropName,
                'crop_type' => $cropType,
                'planting_date' => $plantingDate,
                'field_location' => $fieldLocation,
                'field_size' => $fieldSize,
                'expected_harvest_date' => $expectedHarvestDate
            ];
            
            $newCropId = registerCrop($db, $farmerId, $cropData);
            
            if ($newCropId) {
                $successMessage = "Crop registered successfully!";
                $cropId = $newCropId;
                $uploadType = 'image'; // Switch to image upload form after crop registration
            } else {
                $errors[] = "Failed to register crop";
            }
        }
    }
    
    // Handle crop image upload form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
        $targetCropId = (int)$_POST['crop_id'];
        $growthStage = sanitize($_POST['growth_stage']);
        $notes = sanitize($_POST['notes']);
        
        // Validate inputs
        $errors = [];
        
        if ($targetCropId <= 0) {
            $errors[] = "Please select a crop";
        }
        
        if (empty($growthStage)) {
            $errors[] = "Growth stage is required";
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['crop_image']) || $_FILES['crop_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Please select an image to upload";
        } else {
            // Verify it's an image
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['crop_image']['type'], $allowedTypes)) {
                $errors[] = "Only JPEG and PNG images are allowed";
            }
        }
        
        // Verify the crop belongs to this farmer
        $stmt = $db->prepare("SELECT COUNT(*) FROM crops WHERE crop_id = ? AND farmer_id = ?");
        $stmt->execute([$targetCropId, $farmerId]);
        if ((int)$stmt->fetchColumn() === 0) {
            $errors[] = "Invalid crop selected";
        }
        
        // If no errors, upload the image
        if (empty($errors)) {
            $imageData = [
                'growth_stage' => $growthStage,
                'notes' => $notes
            ];
            
            $imageId = uploadCropImage($db, $targetCropId, $_FILES['crop_image'], $imageData);
            
            if ($imageId) {
                // Perform analysis on the uploaded image
                $analysisData = analyzeCropImage($targetCropId, $imageId);
                
                $successMessage = "Image uploaded successfully and analysis completed!";
                
                // Redirect to analysis details page
                header("Location: index.php?page=analysis-details&analysis_id=" . $analysisData['analysis_id']);
                exit;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Get all crops for this farmer (for dropdown)
    $stmt = $db->prepare("SELECT * FROM crops WHERE farmer_id = ? ORDER BY crop_name");
    $stmt->execute([$farmerId]);
    $farmerCrops = $stmt->fetchAll();
}
?>

<div class="upload-container">
    <h1 class="page-title">
        <?php 
        if ($uploadType === 'image') {
            echo "Upload Crop Image";
        } else {
            echo "Register New Crop";
        }
        ?>
    </h1>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <p><?php echo $successMessage; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($farmerId)): ?>
        <div class="upload-tabs">
            <a href="?page=upload-crop" class="tab <?php echo $uploadType === 'crop' ? 'active' : ''; ?>">Register New Crop</a>
            <a href="?page=upload-crop&type=image" class="tab <?php echo $uploadType === 'image' ? 'active' : ''; ?>">Upload Crop Image</a>
        </div>
        
        <?php if ($uploadType === 'crop'): ?>
            <!-- Crop Registration Form -->
            <div class="card">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="crop_name">Crop Name</label>
                        <input type="text" id="crop_name" name="crop_name" placeholder="e.g., Wheat, Rice, Corn" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="crop_type">Crop Type</label>
                        <select id="crop_type" name="crop_type" required>
                            <option value="">Select Crop Type</option>
                            <option value="Cereal">Cereal</option>
                            <option value="Pulse">Pulse</option>
                            <option value="Oilseed">Oilseed</option>
                            <option value="Vegetable">Vegetable</option>
                            <option value="Fruit">Fruit</option>
                            <option value="Cash Crop">Cash Crop</option>
                            <option value="Fiber">Fiber</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="planting_date">Planting Date</label>
                            <input type="date" id="planting_date" name="planting_date" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="expected_harvest_date">Expected Harvest Date</label>
                            <input type="date" id="expected_harvest_date" name="expected_harvest_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="field_location">Field Location</label>
                        <input type="text" id="field_location" name="field_location" placeholder="e.g., North Field, Block 3">
                    </div>
                    
                    <div class="form-group">
                        <label for="field_size">Field Size (in acres)</label>
                        <input type="number" id="field_size" name="field_size" step="0.01" min="0.01" placeholder="e.g., 2.5">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register_crop" class="btn">Register Crop</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Crop Image Upload Form -->
            <div class="card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="crop_id">Select Crop</label>
                        <select id="crop_id" name="crop_id" required>
                            <option value="">Select Crop</option>
                            <?php foreach ($farmerCrops as $crop): ?>
                                <option value="<?php echo $crop['crop_id']; ?>" <?php echo ($cropId && $cropId == $crop['crop_id']) ? 'selected' : ''; ?>>
                                    <?php echo $crop['crop_name']; ?> (<?php echo $crop['crop_type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="growth_stage">Growth Stage</label>
                        <select id="growth_stage" name="growth_stage" required>
                            <option value="">Select Growth Stage</option>
                            <option value="Germination">Germination</option>
                            <option value="Seedling">Seedling</option>
                            <option value="Vegetative">Vegetative</option>
                            <option value="Budding">Budding</option>
                            <option value="Flowering">Flowering</option>
                            <option value="Fruiting">Fruiting</option>
                            <option value="Ripening">Ripening</option>
                            <option value="Harvesting">Harvesting</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any observations or notes about the crop's condition"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Crop Image</label>
                        <div class="file-upload">
                            <input type="file" id="crop_image" name="crop_image" accept="image/jpeg, image/png, image/jpg" required>
                            <label for="crop_image">Click to select an image or drag and drop</label>
                            <div class="upload-preview"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="upload_image" class="btn">Upload & Analyze</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title">Tips for Better Analysis</h2>
                <ul class="tips-list">
                    <li>Take clear, well-lit photos of your crops</li>
                    <li>Capture the entire plant including leaves, stems, and roots if visible</li>
                    <li>If there are signs of disease or stress, make sure they are visible in the image</li>
                    <li>Include some of the surrounding soil in the image</li>
                    <li>Avoid extreme shadows or glare that might affect image quality</li>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .upload-tabs {
        display: flex;
        margin-bottom: 30px;
        border-bottom: 1px solid #ddd;
    }
    
    .tab {
        flex: 1;
        text-align: center;
        padding: 12px;
        cursor: pointer;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .tab.active {
        color: #7dc383;
        font-weight: 600;
        border-bottom: 2px solid #7dc383;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
    }
    
    .form-group.half {
        flex: 1;
    }
    
    .tips-list {
        padding-left: 20px;
    }
    
    .tips-list li {
        margin-bottom: 10px;
        color: #555;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>
