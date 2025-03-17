<?php
// Home page
?>

<div class="home-banner">
    <div class="banner-content">
        <h1>Welcome to Government Services Portal</h1>
        <p>Access essential services for farmers and citizens all in one place</p>
    </div>
</div>

<div class="features">
    <div class="feature-section">
        <h2>Crop Progress Monitoring</h2>
        <div class="feature-items">
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/10619/10619939.png" alt="">
                </div>
                <h3>Track Crop Growth</h3>
                <p>Upload images and monitor your crop's progress over time with AI-powered analysis.</p>
            </div>
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/5205/5205654.png" alt="">
                </div>
                <h3>Detailed Analysis</h3>
                <p>Get insights on soil health, water needs, and growth rate through graphical reports.</p>
            </div>
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/4814/4814268.png" alt="">
                </div>
                <h3>Weather Forecasts</h3>
                <p>Access weather predictions to make informed farming decisions.</p>
            </div>
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="feature-cta">
            <a href="index.php?page=register" class="btn">Register as Farmer</a>
        </div>
        <?php elseif ($_SESSION['user_type'] === 'farmer'): ?>
        <div class="feature-cta">
            <a href="index.php?page=crop-monitoring" class="btn">Go to Crop Monitoring</a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="feature-section">
        <h2>Citizen Verification & Pension Identification</h2>
        <div class="feature-items">
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/18548/18548027.png" alt="">
                </div>
                <h3>Verify Pension Status</h3>
                <p>Cross-check your pension benefits across multiple government schemes.</p>
            </div>
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/7466/7466016.png" alt="">
                </div>
                <h3>Easy Submission</h3>
                <p>Simply enter your Aadhaar and pension details for verification.</p>
            </div>
            <div class="feature-item">
                <div class="icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/3901/3901453.png" alt="">
                </div>
                <h3>Real-time Status</h3>
                <p>Track your verification status through a simple dashboard.</p>
            </div>
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="feature-cta">
            <a href="index.php?page=register" class="btn">Register as Citizen</a>
        </div>
        <?php elseif ($_SESSION['user_type'] === 'citizen'): ?>
        <div class="feature-cta">
            <a href="index.php?page=pension-verification" class="btn">Go to Pension Verification</a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin'): ?>
    <div class="feature-section">
        <h2>Administrator Tools</h2>
        <div class="feature-items">
            <div class="feature-item">
                <div class="icon">⚙️</div>
                <h3>Case Management</h3>
                <p>Review and manage flagged cases of duplicate pension benefits.</p>
            </div>
            <div class="feature-item">
                <div class="icon">📈</div>
                <h3>Analytics Dashboard</h3>
                <p>View comprehensive statistics and track system usage.</p>
            </div>
            <div class="feature-item">
                <div class="icon">👥</div>
                <h3>User Management</h3>
                <p>Manage user accounts and access privileges.</p>
            </div>
        </div>
        <div class="feature-cta">
            <a href="index.php?page=admin-dashboard" class="btn">Go to Admin Dashboard</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .home-banner {
        background: linear-gradient(rgba(125, 195, 131, 0.55), rgba(125, 195, 131, 0.57)), url('assets/images/farming.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 80px 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 40px;
    }
    
    .banner-content h1 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color:#fff1bc;
    }
    
    .banner-content p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .feature-section {
        margin-bottom: 60px;
        
    }
    
    .feature-section h2 {
        font-size: 1.8rem;
        color:#699c78;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e1e8f0;
    }
    
    .feature-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
       
    }
    
    .feature-item {
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background-color: #E6EEEB;
    }
    
    .feature-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .feature-item .icon {
        font-size: 2.5rem;
        margin-bottom: 20px;
    }

  
    .feature-item h3 {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: #333;
    }
    
    .feature-cta {
        text-align: center;
        
    }

    .feature-cta .btn {
        background-color: #7dc383;
    }

    
    @media (max-width: 768px) {
        .banner-content h1 {
            font-size: 2rem;
        }
        
        .feature-items {
            grid-template-columns: 1fr;
        }
    }
</style>
