<?php
// Analysis Details Page

// Check if analysis_id is provided
if (!isset($_GET['analysis_id'])) {
    echo '<div class="alert alert-danger">Analysis ID is required</div>';
    echo '<a href="index.php?page=crop-analysis" class="btn">Back to Analysis</a>';
    exit;
}

$analysisId = (int)$_GET['analysis_id'];
$userId = $_SESSION['user_id'];

// Get farmer ID
$stmt = $db->prepare("SELECT farmer_id FROM farmer_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$farmer = $stmt->fetch();

if (!$farmer) {
    echo '<div class="alert alert-warning">Your farmer profile is not complete. Please update your profile.</div>';
    echo '<a href="index.php?page=crop-monitoring" class="btn">Back to Dashboard</a>';
    exit;
}

$farmerId = $farmer['farmer_id'];

// Get analysis details and verify it belongs to this farmer
$stmt = $db->prepare("
    SELECT ca.*, c.crop_name, c.crop_type, c.planting_date, c.expected_harvest_date, 
           ci.image_path, ci.growth_stage, ci.upload_date, ci.notes
    FROM crop_analysis ca
    JOIN crops c ON ca.crop_id = c.crop_id
    LEFT JOIN crop_images ci ON ca.image_id = ci.image_id
    WHERE ca.analysis_id = ? AND c.farmer_id = ?
");

$stmt->execute([$analysisId, $farmerId]);
$analysis = $stmt->fetch();

if (!$analysis) {
    echo '<div class="alert alert-danger">Analysis not found or you do not have permission to view it.</div>';
    echo '<a href="index.php?page=crop-analysis" class="btn">Back to Analysis</a>';
    exit;
}

// Get previous analyses for this crop to show trends
$stmt = $db->prepare("
    SELECT ca.analysis_id, ca.soil_health_score, ca.water_needs_score, ca.growth_rate_score, 
           ca.disease_detection, ca.analysis_date
    FROM crop_analysis ca
    WHERE ca.crop_id = ? AND ca.analysis_id != ?
    ORDER BY ca.analysis_date DESC
    LIMIT 5
");

$stmt->execute([$analysis['crop_id'], $analysisId]);
$previousAnalyses = $stmt->fetchAll();

// Calculate days since planting
$plantingDate = new DateTime($analysis['planting_date']);
$analysisDate = new DateTime($analysis['analysis_date']);
$daysSincePlanting = $plantingDate->diff($analysisDate)->days;

// Calculate days until harvest
$harvestDate = new DateTime($analysis['expected_harvest_date']);
$daysUntilHarvest = $analysisDate->diff($harvestDate)->days;
?>

<div class="analysis-details-container">
    <h1 class="page-title">Analysis Details</h1>
    
    <div class="actions-bar">
        <a href="index.php?page=crop-analysis<?php echo isset($analysis['crop_id']) ? '?crop_id=' . $analysis['crop_id'] : ''; ?>" class="btn btn-secondary">Back to Analysis</a>
    </div>
    
    <div class="analysis-overview">
        <div class="card crop-info-card">
            <h2 class="card-title"><?php echo $analysis['crop_name']; ?> Information</h2>
            <div class="crop-info">
                <div class="info-item">
                    <span class="label">Crop Type:</span>
                    <span class="value"><?php echo $analysis['crop_type']; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Growth Stage:</span>
                    <span class="value"><?php echo $analysis['growth_stage']; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Planting Date:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($analysis['planting_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Expected Harvest:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($analysis['expected_harvest_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Days Since Planting:</span>
                    <span class="value"><?php echo $daysSincePlanting; ?> days</span>
                </div>
                <div class="info-item">
                    <span class="label">Days Until Harvest:</span>
                    <span class="value"><?php echo $daysUntilHarvest; ?> days</span>
                </div>
            </div>
        </div>
        
        <?php if ($analysis['image_path']): ?>
            <div class="card image-card">
                <h2 class="card-title">Crop Image</h2>
                <div class="crop-image">
                    <img src="<?php echo $analysis['image_path']; ?>" alt="<?php echo $analysis['crop_name']; ?>">
                    <div class="image-details">
                        <p><strong>Uploaded:</strong> <?php echo date('M d, Y H:i', strtotime($analysis['upload_date'])); ?></p>
                        <?php if ($analysis['notes']): ?>
                            <p><strong>Notes:</strong> <?php echo $analysis['notes']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="analysis-results">
        <div class="card">
            <h2 class="card-title">Analysis Results</h2>
            <div class="analysis-date">
                <strong>Analysis Date:</strong> <?php echo date('M d, Y H:i', strtotime($analysis['analysis_date'])); ?>
            </div>
            
            <div class="metrics">
                <div class="metric">
                    <h3>Soil Health</h3>
                    <div class="score"><?php echo $analysis['soil_health_score']; ?>/10</div>
                    <div class="meter" data-value="<?php echo $analysis['soil_health_score']; ?>" data-max="10">
                        <div class="meter-value" style="width: <?php echo ($analysis['soil_health_score'] / 10) * 100; ?>%;"></div>
                    </div>
                    <div class="score-interpretation">
                        <?php
                        $score = $analysis['soil_health_score'];
                        if ($score >= 8) {
                            echo "<span class='good'>Excellent soil health</span>";
                        } elseif ($score >= 6) {
                            echo "<span class='moderate'>Good soil health</span>";
                        } elseif ($score >= 4) {
                            echo "<span class='caution'>Moderate soil health - consider soil amendments</span>";
                        } else {
                            echo "<span class='poor'>Poor soil health - immediate action recommended</span>";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="metric">
                    <h3>Water Needs</h3>
                    <div class="score"><?php echo $analysis['water_needs_score']; ?>/10</div>
                    <div class="meter" data-value="<?php echo $analysis['water_needs_score']; ?>" data-max="10">
                        <div class="meter-value" style="width: <?php echo ($analysis['water_needs_score'] / 10) * 100; ?>%;"></div>
                    </div>
                    <div class="score-interpretation">
                        <?php
                        $score = $analysis['water_needs_score'];
                        if ($score >= 8) {
                            echo "<span class='good'>Well-hydrated - minimal irrigation needed</span>";
                        } elseif ($score >= 6) {
                            echo "<span class='moderate'>Adequate hydration - monitor conditions</span>";
                        } elseif ($score >= 4) {
                            echo "<span class='caution'>Moderate water stress - consider irrigation</span>";
                        } else {
                            echo "<span class='poor'>Significant water stress - immediate irrigation needed</span>";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="metric">
                    <h3>Growth Rate</h3>
                    <div class="score"><?php echo $analysis['growth_rate_score']; ?>/10</div>
                    <div class="meter" data-value="<?php echo $analysis['growth_rate_score']; ?>" data-max="10">
                        <div class="meter-value" style="width: <?php echo ($analysis['growth_rate_score'] / 10) * 100; ?>%;"></div>
                    </div>
                    <div class="score-interpretation">
                        <?php
                        $score = $analysis['growth_rate_score'];
                        if ($score >= 8) {
                            echo "<span class='good'>Excellent growth rate - above expectations</span>";
                        } elseif ($score >= 6) {
                            echo "<span class='moderate'>Good growth rate - on track</span>";
                        } elseif ($score >= 4) {
                            echo "<span class='caution'>Moderate growth - some concerns</span>";
                        } else {
                            echo "<span class='poor'>Poor growth rate - intervention recommended</span>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="disease-detection">
                <h3>Disease Detection</h3>
                <div class="disease-status">
                    <?php 
                    $disease = $analysis['disease_detection'];
                    $diseaseClass = ($disease === 'Healthy') ? 'good' : 'caution';
                    echo "<span class='$diseaseClass'>$disease</span>"; 
                    ?>
                </div>
            </div>
            
            <div class="recommendations">
                <h3>Recommendations</h3>
                <div class="recommendation-text">
                    <?php echo $analysis['recommendations']; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($previousAnalyses) > 0): ?>
        <div class="card">
            <h2 class="card-title">Historical Trend</h2>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
            
            <h3 class="trend-subtitle">Previous Analyses</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Soil Health</th>
                            <th>Water Needs</th>
                            <th>Growth Rate</th>
                            <th>Disease</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="current-analysis">
                            <td><?php echo date('M d, Y', strtotime($analysis['analysis_date'])); ?> (Current)</td>
                            <td><?php echo $analysis['soil_health_score']; ?>/10</td>
                            <td><?php echo $analysis['water_needs_score']; ?>/10</td>
                            <td><?php echo $analysis['growth_rate_score']; ?>/10</td>
                            <td><?php echo $analysis['disease_detection']; ?></td>
                            <td>Current</td>
                        </tr>
                        <?php foreach ($previousAnalyses as $prevAnalysis): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($prevAnalysis['analysis_date'])); ?></td>
                                <td><?php echo $prevAnalysis['soil_health_score']; ?>/10</td>
                                <td><?php echo $prevAnalysis['water_needs_score']; ?>/10</td>
                                <td><?php echo $prevAnalysis['growth_rate_score']; ?>/10</td>
                                <td><?php echo $prevAnalysis['disease_detection']; ?></td>
                                <td>
                                    <a href="index.php?page=analysis-details&analysis_id=<?php echo $prevAnalysis['analysis_id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2 class="card-title">Take Action</h2>
        <div class="action-buttons">
            <a href="index.php?page=upload-crop&type=image&crop_id=<?php echo $analysis['crop_id']; ?>" class="btn">Upload New Image</a>
            <a href="index.php?page=weather-forecast" class="btn btn-secondary">Check Weather Forecast</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (count($previousAnalyses) > 0): ?>
            // Prepare data for trend chart
            const labels = [
                <?php 
                foreach (array_reverse($previousAnalyses) as $pa) {
                    echo "'" . date('M d', strtotime($pa['analysis_date'])) . "', ";
                }
                echo "'" . date('M d', strtotime($analysis['analysis_date'])) . " (Current)'";
                ?>
            ];
            
            const soilHealthData = [
                <?php 
                foreach (array_reverse($previousAnalyses) as $pa) {
                    echo $pa['soil_health_score'] . ", ";
                }
                echo $analysis['soil_health_score'];
                ?>
            ];
            
            const waterNeedsData = [
                <?php 
                foreach (array_reverse($previousAnalyses) as $pa) {
                    echo $pa['water_needs_score'] . ", ";
                }
                echo $analysis['water_needs_score'];
                ?>
            ];
            
            const growthRateData = [
                <?php 
                foreach (array_reverse($previousAnalyses) as $pa) {
                    echo $pa['growth_rate_score'] . ", ";
                }
                echo $analysis['growth_rate_score'];
                ?>
            ];
            
            // Create trend chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Soil Health',
                        data: soilHealthData,
                        borderColor: 'rgba(125, 195, 131, 1)',
                        backgroundColor: 'rgba(125, 195, 131, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Water Needs',
                        data: waterNeedsData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Growth Rate',
                        data: growthRateData,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            title: {
                                display: true,
                                text: 'Score (0-10)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Analysis Date'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Crop Health Metrics Over Time',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<style>
    .actions-bar {
        margin-bottom: 30px;
    }
    
    .analysis-overview {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .crop-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
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
    
    .crop-image {
        text-align: center;
    }
    
    .crop-image img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .image-details {
        margin-top: 15px;
        text-align: left;
    }
    
    .analysis-results {
        margin-bottom: 30px;
    }
    
    .analysis-date {
        margin-bottom: 20px;
        color: #666;
    }
    
    .metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .metric {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .metric h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }
    
    .score {
        font-size: 2rem;
        font-weight: 600;
        color: #7dc383;
        text-align: center;
        margin-bottom: 10px;
    }
    
    .meter {
        height: 10px;
        background-color: #eee;
        border-radius: 10px;
        margin-bottom: 10px;
        overflow: hidden;
        position: relative;
    }
    
    .meter-value {
        height: 100%;
        border-radius: 10px;
        background-color: #7dc383;
    }
    
    .score-interpretation {
        font-size: 0.9rem;
        margin-top: 10px;
    }
    
    .good {
        color: #28a745;
    }
    
    .moderate {
        color: #17a2b8;
    }
    
    .caution {
        color: #ffc107;
    }
    
    .poor {
        color: #dc3545;
    }
    
    .disease-detection {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .disease-detection h3 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .disease-status {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
    }
    
    .recommendations {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .recommendations h3 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .recommendation-text {
        line-height: 1.6;
    }
    
    .chart-container {
        height: 400px;
        margin-bottom: 30px;
    }
    
    .trend-subtitle {
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .current-analysis {
        background-color: #f0f5ff;
        font-weight: 600;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .analysis-overview {
            grid-template-columns: 1fr;
        }
        
        .metrics {
            grid-template-columns: 1fr;
        }
        
        .crop-info {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>
