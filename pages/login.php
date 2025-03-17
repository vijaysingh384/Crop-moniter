<?php
// Login page

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $user = loginUser($db, $username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            switch ($user['user_type']) {
                case 'farmer':
                    header("Location: index.php?page=crop-monitoring");
                    break;
                case 'citizen':
                    header("Location: index.php?page=pension-verification");
                    break;
                case 'admin':
                    header("Location: index.php?page=admin-dashboard");
                    break;
                default:
                    header("Location: index.php");
            }
            exit;
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Login</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($username) ? $username : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-block">Login</button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="index.php?page=register">Register</a></p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
    }
    
    .auth-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }
    
    .auth-title {
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.8rem;
        color: #699c78;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .btn-block {
        display: block;
        width: 100%;
        background-color: #7dc383;
        color: #fff;
    }
</style>
