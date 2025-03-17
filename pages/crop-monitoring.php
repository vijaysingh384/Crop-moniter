<?php
// Crop Monitoring Dashboard Page

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
    
    // Get all crops for this farmer
    $stmt = $db->prepare("SELECT * FROM crops WHERE farmer_id = ? ORDER BY planting_date DESC");
    $stmt->execute([$farmerId]);
    $crops = $stmt->fetchAll();
    
    // Count total images
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_images
        FROM crop_images ci
        JOIN crops c ON ci.crop_id = c.crop_id
        WHERE c.farmer_id = ?
    ");
    $stmt->execute([$farmerId]);
    $totalImages = $stmt->fetchColumn();
    
    // Get latest analyses
    $stmt = $db->prepare("
        SELECT ca.*, c.crop_name
        FROM crop_analysis ca
        JOIN crops c ON ca.crop_id = c.crop_id
        WHERE c.farmer_id = ?
        ORDER BY ca.analysis_date DESC
        LIMIT 5
    ");
    $stmt->execute([$farmerId]);
    $latestAnalyses = $stmt->fetchAll();
}
?>

<div class="dashboard">
    <h1 class="page-title">Crop Monitoring Dashboard</h1>
    
    <?php if (isset($farmerId)): ?>
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Crops</h3>
                <div class="number"><?php echo count($crops); ?></div>
            </div>
            <div class="stat-card">
                <h3>Images Uploaded</h3>
                <div class="number"><?php echo $totalImages; ?></div>
            </div>
            <div class="stat-card">
                <h3>Latest Analysis</h3>
                <div class="number"><?php echo count($latestAnalyses) > 0 ? date('M d, Y', strtotime($latestAnalyses[0]['analysis_date'])) : 'None'; ?></div>
            </div>
            <div class="stat-card">
                <h3>Actions</h3>
                <div class="action-buttons">
                    <a href="index.php?page=upload-crop" class="btn btn-sm">Upload Data</a>
                    <a href="index.php?page=crop-analysis" class="btn btn-sm btn-secondary">View Analysis</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2 class="card-title">Your Crops</h2>
            
            <?php if (count($crops) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Crop Name</th>
                                <th>Type</th>
                                <th>Planting Date</th>
                                <th>Expected Harvest</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($crops as $crop): ?>
                                <tr>
                                    <td><?php echo $crop['crop_name']; ?></td>
                                    <td><?php echo $crop['crop_type']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($crop['planting_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($crop['expected_harvest_date'])); ?></td>
                                    <td>
                                        <a href="index.php?page=upload-crop&crop_id=<?php echo $crop['crop_id']; ?>" class="btn btn-sm">Upload Image</a>
                                        <a href="index.php?page=crop-details&crop_id=<?php echo $crop['crop_id']; ?>" class="btn btn-sm btn-secondary">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't registered any crops yet.</p>
                    <a href="index.php?page=upload-crop" class="btn">Register Crop</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($latestAnalyses) > 0): ?>
            <div class="card">
                <h2 class="card-title">Latest Analyses</h2>
                <div class="analysis-list">
                    <?php foreach ($latestAnalyses as $analysis): ?>
                        <div class="analysis-item-card">
                            <h3><?php echo $analysis['crop_name']; ?></h3>
                            <div class="analysis-date"><?php echo date('M d, Y H:i', strtotime($analysis['analysis_date'])); ?></div>
                            <div class="analysis-scores">
                                <div class="score">
                                    <span class="label">Soil Health:</span>
                                    <span class="value"><?php echo $analysis['soil_health_score']; ?>/10</span>
                                </div>
                                <div class="score">
                                    <span class="label">Water Needs:</span>
                                    <span class="value"><?php echo $analysis['water_needs_score']; ?>/10</span>
                                </div>
                                <div class="score">
                                    <span class="label">Growth Rate:</span>
                                    <span class="value"><?php echo $analysis['growth_rate_score']; ?>/10</span>
                                </div>
                            </div>
                            <div class="disease-detection">
                                <strong>Status:</strong> <?php echo $analysis['disease_detection']; ?>
                            </div>
                            <a href="index.php?page=analysis-details&analysis_id=<?php echo $analysis['analysis_id']; ?>" class="btn btn-sm">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="card-title">Quick Links</h2>
            <div class="quick-links">
                <a href="index.php?page=upload-crop" class="quick-link">
                    <div class="icon">
                        <img src="https://cdn-icons-png.flaticon.com/128/8615/8615981.png" alt="">
                    </div>
                    <span>Register New Crop</span>
                </a>
                <a href="index.php?page=upload-crop?type=image" class="quick-link">
                    <div class="icon">
                        <img src="https://cdn-icons-png.flaticon.com/128/13434/13434886.png" alt="">
                    </div>
                    <span>Upload Crop Image</span>
                </a>
                <a href="index.php?page=crop-analysis" class="quick-link">
                    <div class="icon">
                        <img src="https://cdn-icons-png.flaticon.com/128/7864/7864341.png" alt="">
                    </div>
                    <span>View Analysis</span>
                </a>
                <a href="index.php?page=weather-forecast" class="quick-link">
                    <div class="icon">
                        <img src="https://cdn-icons-png.flaticon.com/128/5903/5903803.png" alt="">
                    </div>
                    <span>Weather Forecast</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.9rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #666;
    }
    
    .analysis-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .analysis-item-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .analysis-item-card h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }
    
    .analysis-date {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .analysis-scores {
        margin-bottom: 15px;
    }
    
    .score {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    
    .disease-detection {
        margin-bottom: 15px;
        padding: 8px;
        background-color: #f0f5ff;
        border-radius: 4px;
    }
    
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .quick-link {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .quick-link:hover {
        background-color: #f0f5ff;
        transform: translateY(-5px);
    }
    
    .quick-link .icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
</style>
