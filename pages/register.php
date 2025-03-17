<?php
// Registration page

// Initialize variables
$userTypes = ['farmer', 'citizen', 'admin'];
$selectedType = isset($_GET['type']) && in_array($_GET['type'], $userTypes) ? $_GET['type'] : 'farmer';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = sanitize($_POST['email']);
    $userType = sanitize($_POST['user_type']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($username) || strlen($username) < 4) {
        $errors[] = "Username is required and must be at least 4 characters";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password is required and must be at least 6 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = "A valid email is required";
    }
    
    if (!in_array($userType, $userTypes)) {
        $errors[] = "Invalid user type";
    }
    
    // Check if username already exists
    if (!empty($username) && usernameExists($db, $username)) {
        $errors[] = "Username already exists";
    }
    
    // Check if email already exists
    if (!empty($email) && emailExists($db, $email)) {
        $errors[] = "Email already exists";
    }
    
    // If user type is farmer, validate additional fields
    if ($userType === 'farmer') {
        $fullName = sanitize($_POST['full_name']);
        $phoneNumber = sanitize($_POST['phone_number']);
        $address = sanitize($_POST['address']);
        
        if (empty($fullName)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($phoneNumber)) {
            $errors[] = "Phone number is required";
        }
        
        if (empty($address)) {
            $errors[] = "Address is required";
        }
    }
    
    // If user type is citizen, validate additional fields
    if ($userType === 'citizen') {
        $fullName = sanitize($_POST['full_name']);
        $aadhaarNumber = sanitize($_POST['aadhaar_number']);
        $dateOfBirth = sanitize($_POST['date_of_birth']);
        $address = sanitize($_POST['address']);
        $phoneNumber = sanitize($_POST['phone_number']);
        
        if (empty($fullName)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($aadhaarNumber) || strlen($aadhaarNumber) !== 12 || !is_numeric($aadhaarNumber)) {
            $errors[] = "Aadhaar number must be 12 digits";
        }
        
        if (empty($dateOfBirth)) {
            $errors[] = "Date of birth is required";
        }
        
        if (empty($address)) {
            $errors[] = "Address is required";
        }
        
        if (empty($phoneNumber)) {
            $errors[] = "Phone number is required";
        }
    }
    
    // If user type is admin, validate additional fields
    if ($userType === 'admin') {
        $fullName = sanitize($_POST['full_name']);
        $department = sanitize($_POST['department']);
        $role = sanitize($_POST['role']);
        $adminCode = sanitize($_POST['admin_code']);
        
        if (empty($fullName)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($department)) {
            $errors[] = "Department is required";
        }
        
        if (empty($role)) {
            $errors[] = "Role is required";
        }
        
        // Verify admin registration code (simple implementation)
        if (empty($adminCode) || $adminCode !== "ADMIN123") {
            $errors[] = "Invalid admin registration code";
        }
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Register user
        $userId = registerUser($db, $username, $password, $email, $userType);
        
        if ($userId) {
            // Create profile based on user type
            if ($userType === 'farmer') {
                $profileData = [
                    'full_name' => $fullName,
                    'phone_number' => $phoneNumber,
                    'address' => $address
                ];
                
                $farmerId = createFarmerProfile($db, $userId, $profileData);
                
                if (!$farmerId) {
                    $errors[] = "Failed to create farmer profile";
                }
            } elseif ($userType === 'citizen') {
                $profileData = [
                    'full_name' => $fullName,
                    'aadhaar_number' => $aadhaarNumber,
                    'date_of_birth' => $dateOfBirth,
                    'address' => $address,
                    'phone_number' => $phoneNumber
                ];
                
                $citizenId = createCitizenProfile($db, $userId, $profileData);
                
                if (!$citizenId) {
                    $errors[] = "Failed to create citizen profile";
                }
            } elseif ($userType === 'admin') {
                $profileData = [
                    'full_name' => $fullName,
                    'department' => $department,
                    'role' => $role
                ];
                
                $adminId = createAdminProfile($db, $userId, $profileData);
                
                if (!$adminId) {
                    $errors[] = "Failed to create admin profile";
                }
            }
            
            // If no errors, log the user in and redirect
            if (empty($errors)) {
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = $userType;
                
                // Redirect based on user type
                switch ($userType) {
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
            }
        } else {
            $errors[] = "Registration failed";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Register</h2>
        
        <div class="user-type-tabs">
            <a href="?page=register&type=farmer" class="tab <?php echo $selectedType === 'farmer' ? 'active' : ''; ?>">Farmer</a>
            <a href="?page=register&type=citizen" class="tab <?php echo $selectedType === 'citizen' ? 'active' : ''; ?>">Citizen</a>
            <a href="?page=register&type=admin" class="tab <?php echo $selectedType === 'admin' ? 'active' : ''; ?>">Admin</a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="user_type" value="<?php echo $selectedType; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required value="<?php echo isset($username) ? $username : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Your email address" required value="<?php echo isset($email) ? $email : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Choose a password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            
            <?php if ($selectedType === 'farmer'): ?>
                <!-- Farmer specific fields -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Your full name" required value="<?php echo isset($fullName) ? $fullName : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="Your phone number" required value="<?php echo isset($phoneNumber) ? $phoneNumber : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Your address" required><?php echo isset($address) ? $address : ''; ?></textarea>
                </div>
            <?php elseif ($selectedType === 'citizen'): ?>
                <!-- Citizen specific fields -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Your full name" required value="<?php echo isset($fullName) ? $fullName : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="aadhaar_number">Aadhaar Number</label>
                    <input type="text" id="aadhaar_number" name="aadhaar_number" placeholder="12-digit Aadhaar number" required value="<?php echo isset($aadhaarNumber) ? $aadhaarNumber : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo isset($dateOfBirth) ? $dateOfBirth : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Your address" required><?php echo isset($address) ? $address : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="Your phone number" required value="<?php echo isset($phoneNumber) ? $phoneNumber : ''; ?>">
                </div>
            <?php elseif ($selectedType === 'admin'): ?>
                <!-- Admin specific fields -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Your full name" required value="<?php echo isset($fullName) ? $fullName : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="Your department" required value="<?php echo isset($department) ? $department : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" name="role" placeholder="Your role" required value="<?php echo isset($role) ? $role : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="admin_code">Admin Code</label>
                    <input type="text" id="admin_code" name="admin_code" placeholder="Admin registration code" required>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button type="submit" class="btn btn-block">Register</button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="index.php?page=login">Login</a></p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
        padding: 20px 0;
    }
    
    .auth-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 550px;
    }
    
    .auth-title {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.8rem;
        color: #699c78;
    }
    
    .user-type-tabs {
        display: flex;
        margin-bottom: 30px;
        border-bottom: 1px solid #ddd;
        color: #699c78;
    }
    
    .tab {
        flex: 1;
        text-align: center;
        padding: 12px;
        cursor: pointer;
        color: #699c78;
        transition: all 0.3s ease;
    }
    
    .tab.active {
        color: #7dc383;
        font-weight: 600;
        border-bottom: 2px solid #7dc383;
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
