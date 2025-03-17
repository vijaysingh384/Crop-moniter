<?php
// Start session
session_start();

// Include configuration file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$userType = $loggedIn ? $_SESSION['user_type'] : '';

// Navigation restrictions based on user type
$restrictedPages = [
    'farmer' => ['crop-monitoring', 'upload-crop', 'crop-analysis', 'weather-forecast'],
    'citizen' => ['pension-verification', 'view-status'],
    'admin' => ['admin-dashboard', 'flagged-cases', 'verify-cases', 'manage-users']
];

// Redirect to login if trying to access restricted pages while not logged in
if (!$loggedIn && !in_array($page, ['home', 'login', 'register', 'about'])) {
    header('Location: index.php?page=login');
    exit;
}

// Redirect to appropriate dashboard based on user type
if ($loggedIn && $page === 'home') {
    switch ($userType) {
        case 'farmer':
            header('Location: index.php?page=crop-monitoring');
            break;
        case 'citizen':
            header('Location: index.php?page=pension-verification');
            break;
        case 'admin':
            header('Location: index.php?page=admin-dashboard');
            break;
    }
    exit;
}

// Check if the user is trying to access a page not allowed for their user type
if ($loggedIn && $page !== 'home' && $page !== 'profile' && $page !== 'logout') {
    $allowedForUser = false;
    
    if ($userType === 'farmer' && in_array($page, $restrictedPages['farmer'])) {
        $allowedForUser = true;
    } else if ($userType === 'citizen' && in_array($page, $restrictedPages['citizen'])) {
        $allowedForUser = true;
    } else if ($userType === 'admin' && in_array($page, $restrictedPages['admin'])) {
        $allowedForUser = true;
    }
    
    if (!$allowedForUser) {
        header('Location: index.php?page=home');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Government Services Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>Government Services Portal</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php?page=home">Home</a></li>
                    
                    <?php if (!$loggedIn): ?>
                        <li><a href="index.php?page=login">Login</a></li>
                        <li><a href="index.php?page=register">Register</a></li>
                    <?php else: ?>
                        <?php if ($userType === 'farmer'): ?>
                            <li><a href="index.php?page=crop-monitoring">Crop Monitoring</a></li>
                            <li><a href="index.php?page=upload-crop">Upload Data</a></li>
                            <li><a href="index.php?page=crop-analysis">Analysis</a></li>
                            <li><a href="index.php?page=weather-forecast">Weather</a></li>
                        <?php elseif ($userType === 'citizen'): ?>
                            <li><a href="index.php?page=pension-verification">Pension Verification</a></li>
                            <li><a href="index.php?page=view-status">View Status</a></li>
                        <?php elseif ($userType === 'admin'): ?>
                            <li><a href="index.php?page=admin-dashboard">Dashboard</a></li>
                            <li><a href="index.php?page=flagged-cases">Flagged Cases</a></li>
                            <li><a href="index.php?page=verify-cases">Verify Cases</a></li>
                            <li><a href="index.php?page=manage-users">Manage Users</a></li>
                        <?php endif; ?>
                        <li><a href="index.php?page=profile">Profile</a></li>
                        <li><a href="index.php?page=logout">Logout</a></li>
                    <?php endif; ?>
                    
                    <li><a href="index.php?page=about">About</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php
            // Include the appropriate page
            $filePath = 'pages/' . $page . '.php';
            if (file_exists($filePath)) {
                include $filePath;
            } else {
                include 'pages/404.php';
            }
            ?>
        </div>
    </main>

    

    <script src="assets/js/main.js"></script>
    <?php
    // Include page-specific JS if it exists
    $pageJS = 'assets/js/' . $page . '.js';
    if (file_exists($pageJS)): 
    ?>
    <script src="<?php echo $pageJS; ?>"></script>
    <?php endif; ?>
</body>
</html>

<style>
.container-logo-h1 {
    color: #699c78;
}
</style>
