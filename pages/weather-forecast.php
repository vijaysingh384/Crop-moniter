<?php
// Weather Forecast Page

// Default location (can be updated based on user profile or input)
$defaultLatitude = 28.6139;  // Default to New Delhi, India
$defaultLongitude = 77.2090;

// Get user's location preferences if available
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    $stmt = $db->prepare("
        SELECT fp.farm_latitude, fp.farm_longitude, fp.location_name
        FROM farmer_profiles fp
        WHERE fp.user_id = ?
    ");
    $stmt->execute([$userId]);
    $locationData = $stmt->fetch();
    
    if (is_array($locationData) && $locationData['farm_latitude'] && $locationData['farm_longitude']) {
        $defaultLatitude = $locationData['farm_latitude'];
        $defaultLongitude = $locationData['farm_longitude'];
        $locationName = $locationData['location_name'];
    }
}

// Check for location input from form
$latitude = isset($_GET['latitude']) ? (float)$_GET['latitude'] : $defaultLatitude;
$longitude = isset($_GET['longitude']) ? (float)$_GET['longitude'] : $defaultLongitude;
$locationName = isset($_GET['location_name']) ? sanitize($_GET['location_name']) : ($locationName ?? 'Your Location');

// API Key for OpenWeatherMap (in a real app, this would be stored in environment variables)
$apiKey = 'YOUR_OPENWEATHERMAP_API_KEY'; // Replace with your actual API key

// Function to get weather data from OpenWeatherMap API
function getWeatherData($latitude, $longitude, $apiKey) {
    // In a real app, this would make an actual API call to OpenWeatherMap
    // For this example, we'll create mock data
    
    // Current date
    $currentDate = new DateTime();
    
    // Mock data for demonstration
    $weatherData = [
        'current' => [
            'dt' => time(),
            'temp' => rand(15, 35),
            'feels_like' => rand(15, 35),
            'humidity' => rand(30, 90),
            'wind_speed' => rand(1, 15),
            'weather' => [
                [
                    'main' => ['Clear', 'Clouds', 'Rain', 'Thunderstorm'][rand(0, 3)],
                    'description' => ['Clear sky', 'Scattered clouds', 'Light rain', 'Thunderstorm'][rand(0, 3)],
                    'icon' => ['01d', '02d', '10d', '11d'][rand(0, 3)]
                ]
            ],
            'pressure' => rand(980, 1030),
            'uvi' => rand(0, 10) / 10
        ],
        'daily' => []
    ];
    
    // Generate 7-day forecast
    for ($i = 0; $i < 7; $i++) {
        $forecastDate = clone $currentDate;
        $forecastDate->modify("+$i days");
        
        $weatherData['daily'][] = [
            'dt' => $forecastDate->getTimestamp(),
            'temp' => [
                'min' => rand(10, 25),
                'max' => rand(25, 40)
            ],
            'humidity' => rand(30, 90),
            'wind_speed' => rand(1, 15),
            'weather' => [
                [
                    'main' => ['Clear', 'Clouds', 'Rain', 'Thunderstorm'][rand(0, 3)],
                    'description' => ['Clear sky', 'Scattered clouds', 'Light rain', 'Thunderstorm'][rand(0, 3)],
                    'icon' => ['01d', '02d', '10d', '11d'][rand(0, 3)]
                ]
            ],
            'pop' => rand(0, 100) / 100, // Probability of precipitation
            'rain' => rand(0, 3) == 0 ? 0 : rand(1, 15), // Rain volume in mm
        ];
    }
    
    // In a real app, you would make an actual API call like this:
    /*
    $url = "https://api.openweathermap.org/data/3.0/onecall?lat=$latitude&lon=$longitude&exclude=minutely,hourly,alerts&units=metric&appid=$apiKey";
    $response = file_get_contents($url);
    if ($response) {
        $weatherData = json_decode($response, true);
    }
    */
    
    return $weatherData;
}

// Get weather data
$weatherData = getWeatherData($latitude, $longitude, $apiKey);

// Prepare weather icons
$weatherIcons = [
    'Clear' => 'fas fa-sun',
    'Clouds' => 'fas fa-cloud',
    'Rain' => 'fas fa-cloud-rain',
    'Thunderstorm' => 'fas fa-bolt',
    'Drizzle' => 'fas fa-cloud-rain',
    'Snow' => 'fas fa-snowflake',
    'Mist' => 'fas fa-smog',
    'Smoke' => 'fas fa-smog',
    'Haze' => 'fas fa-smog',
    'Dust' => 'fas fa-smog',
    'Fog' => 'fas fa-smog',
    'Sand' => 'fas fa-wind',
    'Ash' => 'fas fa-smog',
    'Squall' => 'fas fa-wind',
    'Tornado' => 'fas fa-wind'
];

// Get weather icon
function getWeatherIcon($weatherType) {
    global $weatherIcons;
    return $weatherIcons[$weatherType] ?? 'fas fa-cloud';
}

// Get weather impact assessment
function getWeatherImpact($weatherType, $temp, $humidity, $windSpeed, $rain = 0) {
    $impact = [
        'status' => 'Favorable',
        'statusClass' => 'favorable',
        'description' => 'Weather conditions are favorable for crop growth.',
        'recommendations' => []
    ];
    
    // Temperature impact
    if ($temp < 10) {
        $impact['status'] = 'Caution';
        $impact['statusClass'] = 'caution';
        $impact['description'] = 'Low temperatures may affect crop growth.';
        $impact['recommendations'][] = 'Consider frost protection measures if plants are sensitive.';
    } elseif ($temp > 35) {
        $impact['status'] = 'Warning';
        $impact['statusClass'] = 'warning';
        $impact['description'] = 'High temperatures may stress crops.';
        $impact['recommendations'][] = 'Ensure adequate irrigation to prevent heat stress.';
        $impact['recommendations'][] = 'Consider shade options for sensitive crops.';
    }
    
    // Precipitation impact
    if ($weatherType == 'Rain' || $weatherType == 'Thunderstorm' || $weatherType == 'Drizzle') {
        if ($rain > 10) {
            $impact['status'] = 'Warning';
            $impact['statusClass'] = 'warning';
            $impact['description'] = 'Heavy rainfall may affect crop conditions.';
            $impact['recommendations'][] = 'Check fields for proper drainage to prevent waterlogging.';
            $impact['recommendations'][] = 'Monitor for signs of disease which may spread in wet conditions.';
        } elseif ($rain > 5) {
            if ($impact['status'] != 'Warning') {
                $impact['status'] = 'Caution';
                $impact['statusClass'] = 'caution';
            }
            $impact['description'] = 'Moderate rainfall expected.';
            $impact['recommendations'][] = 'Adjust irrigation schedules accordingly.';
        } else {
            $impact['recommendations'][] = 'Light rainfall expected, beneficial for most crops.';
        }
    }
    
    // Drought conditions
    if ($weatherType == 'Clear' && $humidity < 40 && $temp > 30) {
        $impact['status'] = 'Warning';
        $impact['statusClass'] = 'warning';
        $impact['description'] = 'Hot and dry conditions may cause water stress.';
        $impact['recommendations'][] = 'Increase irrigation frequency.';
        $impact['recommendations'][] = 'Consider mulching to reduce soil evaporation.';
    }
    
    // Wind impact
    if ($windSpeed > 10) {
        if ($impact['status'] != 'Warning') {
            $impact['status'] = 'Caution';
            $impact['statusClass'] = 'caution';
        }
        $impact['description'] = 'Strong winds may affect crop structures.';
        $impact['recommendations'][] = 'Check support structures for tall crops.';
        $impact['recommendations'][] = 'Ensure newly planted seedlings are protected.';
    }
    
    return $impact;
}

// Get current weather impact
$currentWeather = $weatherData['current']['weather'][0]['main'];
$currentTemp = $weatherData['current']['temp'];
$currentHumidity = $weatherData['current']['humidity'];
$currentWindSpeed = $weatherData['current']['wind_speed'];
$currentImpact = getWeatherImpact($currentWeather, $currentTemp, $currentHumidity, $currentWindSpeed);

// Get farming calendar recommendations based on season
function getFarmingCalendarTips() {
    $month = (int)date('m');
    $season = '';
    
    // Determine season (based on Northern Hemisphere)
    if ($month >= 3 && $month <= 5) {
        $season = 'Spring';
    } elseif ($month >= 6 && $month <= 8) {
        $season = 'Summer';
    } elseif ($month >= 9 && $month <= 11) {
        $season = 'Fall';
    } else {
        $season = 'Winter';
    }
    
    // Season-specific tips
    $tips = [
        'Spring' => [
            'Prepare seedbeds and start planting summer crops.',
            'Apply fertilizers as required for crop growth.',
            'Monitor for early pest emergence and take preventive measures.',
            'Check irrigation systems before peak season.'
        ],
        'Summer' => [
            'Ensure adequate irrigation, especially during hot periods.',
            'Monitor for pests and diseases that thrive in warm weather.',
            'Apply mulch to conserve soil moisture and reduce weed growth.',
            'Provide shade for sensitive crops during peak heat hours.'
        ],
        'Fall' => [
            'Harvest summer crops at optimal maturity.',
            'Prepare fields for winter crops where applicable.',
            'Perform soil testing to determine nutrient needs.',
            'Plan crop rotation for the next growing season.'
        ],
        'Winter' => [
            'Protect sensitive crops from frost and cold damage.',
            'Maintain drainage systems to prevent waterlogging.',
            'Plan for the next growing season and order seeds/inputs.',
            'Perform maintenance on farm equipment.'
        ]
    ];
    
    return [
        'season' => $season,
        'tips' => $tips[$season]
    ];
}

$farmingCalendar = getFarmingCalendarTips();
?>

<div class="weather-container">
    <h1 class="page-title">Weather Forecast</h1>
    
    <div class="location-selector card">
        <h2 class="card-title">Select Location</h2>
        <form method="GET" action="" id="locationForm">
            <input type="hidden" name="page" value="weather-forecast">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="location_name">Location Name</label>
                    <input type="text" id="location_name" name="location_name" value="<?php echo $locationName; ?>">
                </div>
                
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="number" id="latitude" name="latitude" step="0.0001" value="<?php echo $latitude; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="number" id="longitude" name="longitude" step="0.0001" value="<?php echo $longitude; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-secondary" id="getCurrentLocation">Get Current Location</button>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn">Update Forecast</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="current-weather card">
        <div class="weather-header">
            <h2 class="card-title">Current Weather in <?php echo $locationName; ?></h2>
            <div class="last-updated">Last updated: <?php echo date('M d, Y H:i', $weatherData['current']['dt']); ?></div>
        </div>
        
        <div class="weather-main">
            <div class="weather-icon">
                <i class="<?php echo getWeatherIcon($currentWeather); ?>"></i>
                <div class="weather-condition"><?php echo $weatherData['current']['weather'][0]['description']; ?></div>
            </div>
            
            <div class="weather-details">
                <div class="temperature">
                    <span class="temp-value"><?php echo round($currentTemp); ?>&deg;C</span>
                    <span class="feels-like">Feels like: <?php echo round($weatherData['current']['feels_like']); ?>&deg;C</span>
                </div>
                
                <div class="other-metrics">
                    <div class="metric">
                        <i class="fas fa-tint"></i>
                        <span><?php echo $weatherData['current']['humidity']; ?>% Humidity</span>
                    </div>
                    
                    <div class="metric">
                        <i class="fas fa-wind"></i>
                        <span><?php echo $weatherData['current']['wind_speed']; ?> m/s Wind</span>
                    </div>
                    
                    <div class="metric">
                        <i class="fas fa-compress-arrows-alt"></i>
                        <span><?php echo $weatherData['current']['pressure']; ?> hPa Pressure</span>
                    </div>
                    
                    <div class="metric">
                        <i class="fas fa-sun"></i>
                        <span>UV Index: <?php echo $weatherData['current']['uvi']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="weather-impact card">
        <h2 class="card-title">Crop Impact Assessment</h2>
        
        <div class="impact-status <?php echo $currentImpact['statusClass']; ?>">
            <?php echo $currentImpact['status']; ?>
        </div>
        
        <div class="impact-description">
            <?php echo $currentImpact['description']; ?>
        </div>
        
        <?php if (!empty($currentImpact['recommendations'])): ?>
            <div class="recommendations">
                <h3>Recommendations</h3>
                <ul>
                    <?php foreach ($currentImpact['recommendations'] as $recommendation): ?>
                        <li><?php echo $recommendation; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="forecast-days card">
        <h2 class="card-title">7-Day Forecast</h2>
        
        <div class="days-container">
            <?php foreach ($weatherData['daily'] as $index => $day): ?>
                <?php 
                $dayWeather = $day['weather'][0]['main'];
                $dayTemp = [
                    'min' => $day['temp']['min'],
                    'max' => $day['temp']['max']
                ];
                
                $dayImpact = getWeatherImpact(
                    $dayWeather, 
                    $dayTemp['max'], 
                    $day['humidity'], 
                    $day['wind_speed'],
                    $day['rain'] ?? 0
                );
                
                $dayName = $index == 0 ? 'Today' : date('D', $day['dt']);
                $date = date('M d', $day['dt']);
                ?>
                
                <div class="forecast-day">
                    <div class="day-header">
                        <div class="day-name"><?php echo $dayName; ?></div>
                        <div class="day-date"><?php echo $date; ?></div>
                    </div>
                    
                    <div class="day-icon">
                        <i class="<?php echo getWeatherIcon($dayWeather); ?>"></i>
                    </div>
                    
                    <div class="day-condition">
                        <?php echo $day['weather'][0]['description']; ?>
                    </div>
                    
                    <div class="day-temp">
                        <span class="max"><?php echo round($dayTemp['max']); ?>&deg;</span>
                        <span class="min"><?php echo round($dayTemp['min']); ?>&deg;</span>
                    </div>
                    
                    <div class="day-metrics">
                        <div class="day-metric">
                            <i class="fas fa-tint"></i>
                            <span><?php echo $day['humidity']; ?>%</span>
                        </div>
                        
                        <div class="day-metric">
                            <i class="fas fa-wind"></i>
                            <span><?php echo $day['wind_speed']; ?> m/s</span>
                        </div>
                        
                        <?php if (isset($day['pop']) && $day['pop'] > 0): ?>
                            <div class="day-metric">
                                <i class="fas fa-cloud-rain"></i>
                                <span><?php echo round($day['pop'] * 100); ?>%</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="day-impact <?php echo $dayImpact['statusClass']; ?>">
                        <?php echo $dayImpact['status']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="farming-calendar card">
        <h2 class="card-title">Seasonal Farming Tips (<?php echo $farmingCalendar['season']; ?>)</h2>
        
        <ul class="farming-tips">
            <?php foreach ($farmingCalendar['tips'] as $tip): ?>
                <li><?php echo $tip; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get current location
        const getCurrentLocationBtn = document.getElementById('getCurrentLocation');
        
        getCurrentLocationBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                getCurrentLocationBtn.textContent = 'Finding location...';
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude.toFixed(4);
                    document.getElementById('longitude').value = position.coords.longitude.toFixed(4);
                    document.getElementById('locationForm').submit();
                }, function(error) {
                    alert('Unable to retrieve your location: ' + error.message);
                    getCurrentLocationBtn.textContent = 'Get Current Location';
                });
            } else {
                alert('Geolocation is not supported by your browser');
            }
        });
    });
</script>

<style>
    .weather-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .location-selector {
        margin-bottom: 30px;
    }
    
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .current-weather {
        margin-bottom: 30px;
    }
    
    .weather-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .last-updated {
        font-size: 0.9rem;
        color: #666;
    }
    
    .weather-main {
        display: flex;
        align-items: center;
    }
    
    .weather-icon {
        flex: 0 0 150px;
        text-align: center;
    }
    
    .weather-icon i {
        font-size: 5rem;
        color: #7dc383;
    }
    
    .weather-condition {
        margin-top: 10px;
        font-size: 1.2rem;
        text-transform: capitalize;
    }
    
    .weather-details {
        flex: 1;
    }
    
    .temperature {
        margin-bottom: 20px;
    }
    
    .temp-value {
        font-size: 3rem;
        font-weight: 700;
        color: #333;
    }
    
    .feels-like {
        margin-left: 15px;
        font-size: 1.1rem;
        color: #666;
    }
    
    .other-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .metric {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .metric i {
        font-size: 1.2rem;
        color: #7dc383;
    }
    
    .weather-impact {
        margin-bottom: 30px;
        padding: 20px;
    }
    
    .impact-status {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .impact-status.favorable {
        background-color: #d4edda;
        color: #155724;
    }
    
    .impact-status.caution {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .impact-status.warning {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .impact-description {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .recommendations {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }
    
    .recommendations h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    
    .recommendations ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .recommendations li {
        margin-bottom: 8px;
    }
    
    .forecast-days {
        margin-bottom: 30px;
    }
    
    .days-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .forecast-day {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        text-align: center;
        position: relative;
    }
    
    .day-header {
        margin-bottom: 10px;
    }
    
    .day-name {
        font-weight: 600;
    }
    
    .day-date {
        font-size: 0.9rem;
        color: #666;
    }
    
    .day-icon {
        margin: 15px 0;
    }
    
    .day-icon i {
        font-size: 2rem;
        color: #7dc383;
    }
    
    .day-condition {
        margin-bottom: 10px;
        font-size: 0.9rem;
        text-transform: capitalize;
    }
    
    .day-temp {
        margin-bottom: 15px;
    }
    
    .day-temp .max {
        font-size: 1.3rem;
        font-weight: 600;
    }
    
    .day-temp .min {
        font-size: 1.1rem;
        color: #666;
        margin-left: 10px;
    }
    
    .day-metrics {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .day-metric {
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    
    .day-impact {
        padding: 5px;
        border-radius: 5px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .day-impact.favorable {
        background-color: #d4edda;
        color: #155724;
    }
    
    .day-impact.caution {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .day-impact.warning {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .farming-calendar {
        margin-bottom: 30px;
    }
    
    .farming-tips li {
        margin-bottom: 10px;
        line-height: 1.6;
    }
    
    @media (max-width: 768px) {
        .weather-main {
            flex-direction: column;
            text-align: center;
        }
        
        .weather-icon {
            margin-bottom: 20px;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .form-group {
            width: 100%;
        }
        
        .days-container {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .days-container {
            grid-template-columns: 1fr;
        }
    }
</style>
