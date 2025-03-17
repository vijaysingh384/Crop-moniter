<?php
// Admin Dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?page=login");
    exit;
}

// Get admin data
$userId = $_SESSION['user_id'];
$adminData = null;

try {
    $stmt = $db->prepare("
        SELECT a.*, u.email, u.created_at, u.last_login 
        FROM admin_profiles a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.user_id = ?
    ");
    $stmt->execute([$userId]);
    $adminData = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching admin data: " . $e->getMessage());
}

// Get some system statistics
$stats = [
    'farmers' => 0,
    'citizens' => 0,
    'crops' => 0,
    'images' => 0,
    'pensions' => 0
];

try {
    // Count farmers
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'farmer'");
    $stats['farmers'] = (int)$stmt->fetchColumn();
    
    // Count citizens
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'citizen'");
    $stats['citizens'] = (int)$stmt->fetchColumn();
    
    // Count crops
    $stmt = $db->query("SELECT COUNT(*) FROM crops");
    $stats['crops'] = (int)$stmt->fetchColumn();
    
    // Count crop images
    $stmt = $db->query("SELECT COUNT(*) FROM crop_images");
    $stats['images'] = (int)$stmt->fetchColumn();
    
    // Count pension claims
    $stmt = $db->query("SELECT COUNT(*) FROM pension_claims");
    $stats['pensions'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Get recent pension claims for review
$recentClaims = [];
try {
    $stmt = $db->query("
        SELECT pc.*, c.full_name, ps.scheme_name
        FROM pension_claims pc
        JOIN citizen_profiles c ON pc.citizen_id = c.citizen_id
        JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
        WHERE pc.claim_status = 'pending'
        ORDER BY pc.application_date DESC
        LIMIT 10
    ");
    $recentClaims = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recent claims: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-lg-12">
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($adminData['full_name']); ?></p>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- System Statistics -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Farmers</h5>
                        <h2 class="stat-card-value"><?php echo $stats['farmers']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Citizens</h5>
                        <h2 class="stat-card-value"><?php echo $stats['citizens']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Crops</h5>
                        <h2 class="stat-card-value"><?php echo $stats['crops']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-icon">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Pension Claims</h5>
                        <h2 class="stat-card-value"><?php echo $stats['pensions']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Quick Action Cards -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <a href="index.php?page=manage-farmers" class="btn btn-primary mb-2">Manage Farmers</a>
                        <a href="index.php?page=manage-citizens" class="btn btn-primary mb-2">Manage Citizens</a>
                        <a href="index.php?page=manage-crops" class="btn btn-primary mb-2">Crop Database</a>
                        <a href="index.php?page=pension-schemes" class="btn btn-primary mb-2">Pension Schemes</a>
                        <a href="index.php?page=system-reports" class="btn btn-info mb-2">Generate Reports</a>
                        <a href="index.php?page=system-settings" class="btn btn-secondary mb-2">System Settings</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin Profile Card -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Your Profile</h5>
                </div>
                <div class="card-body">
                    <div class="profile-info">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Name:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($adminData['full_name']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Department:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($adminData['department']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Role:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($adminData['role']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Email:</strong></div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($adminData['email']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Last Login:</strong></div>
                            <div class="col-sm-8"><?php echo $adminData['last_login'] ? date('M d, Y h:i A', strtotime($adminData['last_login'])) : 'First Login'; ?></div>
                        </div>
                    </div>
                    <a href="index.php?page=edit-profile" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Pension Claims for Review -->
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Pension Claims</h5>
                    <a href="index.php?page=pension-claims" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentClaims) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Scheme</th>
                                        <th>Application Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentClaims as $claim): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($claim['scheme_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($claim['application_date'])); ?></td>
                                            <td><span class="badge bg-warning">Pending</span></td>
                                            <td>
                                                <a href="index.php?page=review-claim&id=<?php echo $claim['claim_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No pending pension claims to review.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        height: 100%;
    }
    
    .stat-card-body {
        padding: 20px;
        display: flex;
        align-items: center;
    }
    
    .stat-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(125, 195, 131, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    
    .stat-card-icon i {
        font-size: 24px;
        color: #7dc383;
    }
    
    .stat-card-info {
        flex: 1;
    }
    
    .stat-card-title {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .stat-card-value {
        margin: 5px 0 0;
        color: #333;
        font-weight: 600;
    }
    
    .action-buttons {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .profile-info {
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            grid-template-columns: 1fr;
        }
    }
</style>
