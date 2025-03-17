<?php
// Crop Analysis Page

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
    
    // Get selected crop for analysis
    $selectedCropId = isset($_GET['crop_id']) ? (int)$_GET['crop_id'] : null;
    
    // Get all crops for this farmer (for dropdown)
    $stmt = $db->prepare("SELECT * FROM crops WHERE farmer_id = ? ORDER BY crop_name");
    $stmt->execute([$farmerId]);
    $farmerCrops = $stmt->fetchAll();
    
    // Get all analyses for the selected crop or the most recent analyses if no crop is selected
    if ($selectedCropId) {
        // Verify the crop belongs to this farmer
        $stmt = $db->prepare("SELECT COUNT(*) FROM crops WHERE crop_id = ? AND farmer_id = ?");
        $stmt->execute([$selectedCropId, $farmerId]);
        
        if ((int)$stmt->fetchColumn() === 0) {
            $selectedCropId = null; // Reset if not found or not owned by this farmer
        }
    }
    
    if ($selectedCropId) {
        // Get analyses for the selected crop
        $stmt = $db->prepare("
            SELECT ca.*, c.crop_name, ci.image_path, ci.growth_stage, ci.upload_date
            FROM crop_analysis ca
            JOIN crops c ON ca.crop_id = c.crop_id
            LEFT JOIN crop_images ci ON ca.image_id = ci.image_id
            WHERE ca.crop_id = ?
            ORDER BY ca.analysis_date DESC
        ");
        $stmt->execute([$selectedCropId]);
        $analyses = $stmt->fetchAll();
        
        // Get crop details
        $stmt = $db->prepare("SELECT * FROM crops WHERE crop_id = ?");
        $stmt->execute([$selectedCropId]);
        $cropDetails = $stmt->fetch();
    } else {
        // Get recent analyses across all crops
        $stmt = $db->prepare("
            SELECT ca.*, c.crop_name, ci.image_path, ci.growth_stage, ci.upload_date
            FROM crop_analysis ca
            JOIN crops c ON ca.crop_id = c.crop_id
            LEFT JOIN crop_images ci ON ca.image_id = ci.image_id
            WHERE c.farmer_id = ?
            ORDER BY ca.analysis_date DESC
            LIMIT 10
        ");
        $stmt->execute([$farmerId]);
        $analyses = $stmt->fetchAll();
    }
    
    // Prepare data for charts
    $chartData = [
        'labels' => [],
        'soilHealth' => [],
        'waterNeeds' => [],
        'growthRate' => []
    ];
    
    // Group the latest analysis data by date (monthly)
    $analysisByMonth = [];
    foreach ($analyses as $analysis) {
        $month = date('M Y', strtotime($analysis['analysis_date']));
        
        if (!isset($analysisByMonth[$month])) {
            $analysisByMonth[$month] = [
                'soil_health_sum' => 0,
                'water_needs_sum' => 0,
                'growth_rate_sum' => 0,
                'count' => 0
            ];
        }
        
        $analysisByMonth[$month]['soil_health_sum'] += $analysis['soil_health_score'];
        $analysisByMonth[$month]['water_needs_sum'] += $analysis['water_needs_score'];
        $analysisByMonth[$month]['growth_rate_sum'] += $analysis['growth_rate_score'];
        $analysisByMonth[$month]['count']++;
    }
    
    // Calculate averages by month
    foreach ($analysisByMonth as $month => $data) {
        $chartData['labels'][] = $month;
        $chartData['soilHealth'][] = round($data['soil_health_sum'] / $data['count'], 1);
        $chartData['waterNeeds'][] = round($data['water_needs_sum'] / $data['count'], 1);
        $chartData['growthRate'][] = round($data['growth_rate_sum'] / $data['count'], 1);
    }
    
    // Calculate disease detection summary
    $diseaseDetection = [];
    foreach ($analyses as $analysis) {
        $disease = $analysis['disease_detection'];
        if (!isset($diseaseDetection[$disease])) {
            $diseaseDetection[$disease] = 0;
        }
        $diseaseDetection[$disease]++;
    }
}
?>

<div class="analysis-container">
    <h1 class="page-title">Crop Analysis</h1>
    
    <?php if (isset($farmerId)): ?>
        <div class="crop-selection">
            <form method="GET" action="">
                <input type="hidden" name="page" value="crop-analysis">
                <div class="form-row">
                    <div class="form-group flex-grow">
                        <label for="crop_id">Select Crop for Analysis</label>
                        <select id="crop_id" name="crop_id" onchange="this.form.submit()">
                            <option value="">All Crops (Recent Analyses)</option>
                            <?php foreach ($farmerCrops as $crop): ?>
                                <option value="<?php echo $crop['crop_id']; ?>" <?php echo ($selectedCropId && $selectedCropId == $crop['crop_id']) ? 'selected' : ''; ?>>
                                    <?php echo $crop['crop_name']; ?> (<?php echo $crop['crop_type']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <a href="index.php?page=upload-crop&type=image<?php echo $selectedCropId ? '&crop_id=' . $selectedCropId : ''; ?>" class="btn">Upload New Image</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (isset($cropDetails)): ?>
            <div class="crop-details card">
                <h2 class="card-title"><?php echo $cropDetails['crop_name']; ?> Details</h2>
                <div class="crop-info">
                    <div class="info-item">
                        <span class="label">Crop Type:</span>
                        <span class="value"><?php echo $cropDetails['crop_type']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Planting Date:</span>
                        <span class="value"><?php echo date('M d, Y', strtotime($cropDetails['planting_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Expected Harvest:</span>
                        <span class="value"><?php echo date('M d, Y', strtotime($cropDetails['expected_harvest_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Field Location:</span>
                        <span class="value"><?php echo $cropDetails['field_location'] ?: 'Not specified'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Field Size:</span>
                        <span class="value"><?php echo $cropDetails['field_size'] ? $cropDetails['field_size'] . ' acres' : 'Not specified'; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (count($analyses) > 0): ?>
            <div class="analysis-charts">
                <div class="card">
                    <h2 class="card-title">Health Metrics Over Time</h2>
                    <div class="chart-container">
                        <canvas id="healthMetricsChart"></canvas>
                    </div>
                </div>
                
                <div class="analysis-metrics">
                    <div class="card metric-card">
                        <h3>Soil Health Trend</h3>
                        <div class="chart-container small-chart">
                            <canvas id="soilHealthChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="card metric-card">
                        <h3>Water Needs Trend</h3>
                        <div class="chart-container small-chart">
                            <canvas id="waterNeedsChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="card metric-card">
                        <h3>Growth Rate Trend</h3>
                        <div class="chart-container small-chart">
                            <canvas id="growthRateChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php if (count($diseaseDetection) > 0): ?>
                    <div class="card">
                        <h2 class="card-title">Disease Detection Summary</h2>
                        <div class="chart-container">
                            <canvas id="diseaseChart"></canvas>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 class="card-title">Analysis History</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Crop</th>
                                <th>Growth Stage</th>
                                <th>Soil Health</th>
                                <th>Water Needs</th>
                                <th>Growth Rate</th>
                                <th>Disease</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analyses as $analysis): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($analysis['analysis_date'])); ?></td>
                                    <td><?php echo $analysis['crop_name']; ?></td>
                                    <td><?php echo $analysis['growth_stage']; ?></td>
                                    <td><?php echo $analysis['soil_health_score']; ?>/10</td>
                                    <td><?php echo $analysis['water_needs_score']; ?>/10</td>
                                    <td><?php echo $analysis['growth_rate_score']; ?>/10</td>
                                    <td><?php echo $analysis['disease_detection']; ?></td>
                                    <td>
                                        <a href="index.php?page=analysis-details&analysis_id=<?php echo $analysis['analysis_id']; ?>" class="btn btn-sm">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
                // Pass PHP data to JavaScript
                const chartLabels = <?php echo json_encode($chartData['labels']); ?>;
                const soilHealthData = <?php echo json_encode($chartData['soilHealth']); ?>;
                const waterNeedsData = <?php echo json_encode($chartData['waterNeeds']); ?>;
                const growthRateData = <?php echo json_encode($chartData['growthRate']); ?>;
                const diseaseData = <?php echo json_encode($diseaseDetection); ?>;
            </script>
        <?php else: ?>
            <div class="empty-state">
                <p>No analysis data available for this crop yet.</p>
                <a href="index.php?page=upload-crop&type=image<?php echo $selectedCropId ? '&crop_id=' . $selectedCropId : ''; ?>" class="btn">Upload Image for Analysis</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .crop-selection {
        margin-bottom: 30px;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
        align-items: flex-end;
    }
    
    .form-group.flex-grow {
        flex-grow: 1;
    }
    
    .crop-details {
        margin-bottom: 30px;
    }
    
    .crop-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-item .label {
        font-weight: 600;
        color: #666;
    }
    
    .info-item .value {
        font-size: 1.1rem;
    }
    
    .analysis-charts {
        margin-bottom: 30px;
    }
    
    .analysis-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .metric-card h3 {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .small-chart {
        height: 200px;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .empty-state p {
        margin-bottom: 20px;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 10px;
        }
        
        .analysis-metrics {
            grid-template-columns: 1fr;
        }
        
        .crop-info {
            grid-template-columns: 1fr;
        }
    }
</style>
