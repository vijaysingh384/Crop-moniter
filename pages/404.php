<?php
// 404 Page Not Found
?>

<div class="error-container">
    <div class="error-content">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you are looking for does not exist or has been moved.</p>
        <a href="index.php" class="btn">Go to Home</a>
    </div>
</div>

<style>
    .error-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
        text-align: center;
    }
    
    .error-content h1 {
        font-size: 8rem;
        color: #7dc383;
        margin: 0;
        line-height: 1;
    }
    
    .error-content h2 {
        font-size: 2rem;
        margin-bottom: 20px;
    }
    
    .error-content p {
        margin-bottom: 30px;
        color: #666;
    }
</style>
