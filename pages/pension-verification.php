<?php
// Pension Verification Page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'citizen') {
    header("Location: index.php?page=login");
    exit;
}

// Get citizen data
$userId = $_SESSION['user_id'];
$citizenData = null;

try {
    $stmt = $db->prepare("
        SELECT c.*, u.email, u.created_at, u.last_login 
        FROM citizen_profiles c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $citizenData = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching citizen data: " . $e->getMessage());
}

// Get available pension schemes
$pensionSchemes = [];
try {
    $stmt = $db->query("SELECT * FROM pension_schemes ORDER BY scheme_name");
    $pensionSchemes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching pension schemes: " . $e->getMessage());
}

// Get citizen's existing pension claims
$existingClaims = [];
try {
    $stmt = $db->prepare("
        SELECT pc.*, ps.scheme_name, ps.scheme_provider, ps.benefit_amount
        FROM pension_claims pc
        JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
        WHERE pc.citizen_id = ?
        ORDER BY pc.application_date DESC
    ");
    $stmt->execute([$citizenData['citizen_id']]);
    $existingClaims = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching existing claims: " . $e->getMessage());
}

// Handle new claim submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_claim'])) {
    $schemeId = (int)$_POST['scheme_id'];
    $notes = sanitize($_POST['notes']);
    
    // Validate scheme exists
    $schemeExists = false;
    foreach ($pensionSchemes as $scheme) {
        if ($scheme['scheme_id'] == $schemeId) {
            $schemeExists = true;
            break;
        }
    }
    
    if (!$schemeExists) {
        $errorMessage = "Invalid pension scheme selected";
    } else {
        // Check if already applied for this scheme
        $alreadyApplied = false;
        foreach ($existingClaims as $claim) {
            if ($claim['scheme_id'] == $schemeId && in_array($claim['claim_status'], ['pending', 'approved'])) {
                $alreadyApplied = true;
                break;
            }
        }
        
        if ($alreadyApplied) {
            $errorMessage = "You have already applied for this pension scheme";
        } else {
            // Submit new claim
            try {
                $stmt = $db->prepare("
                    INSERT INTO pension_claims (citizen_id, scheme_id, notes)
                    VALUES (?, ?, ?)
                ");
                $success = $stmt->execute([$citizenData['citizen_id'], $schemeId, $notes]);
                
                if ($success) {
                    $successMessage = "Your pension claim has been submitted successfully";
                    // Refresh the existing claims list
                    $stmt = $db->prepare("
                        SELECT pc.*, ps.scheme_name, ps.scheme_provider, ps.benefit_amount
                        FROM pension_claims pc
                        JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
                        WHERE pc.citizen_id = ?
                        ORDER BY pc.application_date DESC
                    ");
                    $stmt->execute([$citizenData['citizen_id']]);
                    $existingClaims = $stmt->fetchAll();
                } else {
                    $errorMessage = "Failed to submit your pension claim";
                }
            } catch (PDOException $e) {
                error_log("Error submitting pension claim: " . $e->getMessage());
                $errorMessage = "An error occurred while submitting your claim";
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-lg-12">
            <h1 class="page-title">Pension Verification & Claims</h1>
            <p class="text-muted">Welcome, <?php echo htmlspecialchars($citizenData['full_name']); ?></p>
        </div>
    </div>
    
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Citizen Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Your Profile</h5>
                </div>
                <div class="card-body">
                    <div class="profile-info">
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Name:</strong></div>
                            <div class="col-sm-7"><?php echo htmlspecialchars($citizenData['full_name']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Aadhaar:</strong></div>
                            <div class="col-sm-7"><?php echo htmlspecialchars($citizenData['aadhaar_number']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Date of Birth:</strong></div>
                            <div class="col-sm-7"><?php echo date('M d, Y', strtotime($citizenData['date_of_birth'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Phone:</strong></div>
                            <div class="col-sm-7"><?php echo htmlspecialchars($citizenData['phone_number']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Email:</strong></div>
                            <div class="col-sm-7"><?php echo htmlspecialchars($citizenData['email']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5"><strong>Address:</strong></div>
                            <div class="col-sm-7"><?php echo htmlspecialchars($citizenData['address']); ?></div>
                        </div>
                    </div>
                    <a href="index.php?page=edit-profile" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
        </div>
        
        <!-- Apply for Pension Scheme -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Apply for Pension Scheme</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label for="scheme_id">Select Pension Scheme</label>
                            <select id="scheme_id" name="scheme_id" class="form-control" required>
                                <option value="">-- Select a Scheme --</option>
                                <?php foreach ($pensionSchemes as $scheme): ?>
                                    <option value="<?php echo $scheme['scheme_id']; ?>">
                                        <?php echo htmlspecialchars($scheme['scheme_name']); ?> - 
                                        <?php echo htmlspecialchars($scheme['scheme_provider']); ?> 
                                        (<?php echo htmlspecialchars($scheme['benefit_amount']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="scheme_details" class="mb-3 p-3 bg-light rounded" style="display: none;">
                            <h6 class="scheme-name"></h6>
                            <p class="scheme-description"></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Eligibility:</strong> <span class="scheme-eligibility"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Benefit Amount:</strong> <span class="scheme-benefit"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="notes">Additional Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Any additional information about your application"></textarea>
                        </div>
                        
                        <button type="submit" name="submit_claim" class="btn btn-primary">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Existing Claims -->
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Your Pension Claims</h5>
                </div>
                <div class="card-body">
                    <?php if (count($existingClaims) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Scheme Name</th>
                                        <th>Provider</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Benefit Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existingClaims as $claim): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($claim['scheme_name']); ?></td>
                                            <td><?php echo htmlspecialchars($claim['scheme_provider']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($claim['application_date'])); ?></td>
                                            <td>
                                                <?php
                                                switch ($claim['claim_status']) {
                                                    case 'pending':
                                                        echo '<span class="badge bg-warning">Pending</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="badge bg-success">Approved</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="badge bg-danger">Rejected</span>';
                                                        break;
                                                    case 'flagged':
                                                        echo '<span class="badge bg-warning">Flagged</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($claim['benefit_amount']); ?></td>
                                            <td>
                                                <a href="index.php?page=claim-details&id=<?php echo $claim['claim_id']; ?>" class="btn btn-sm btn-primary">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">You haven't applied for any pension schemes yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-info {
        margin-bottom: 15px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const schemeSelect = document.getElementById('scheme_id');
    const schemeDetails = document.getElementById('scheme_details');
    
    // Store scheme data
    const schemeData = <?php echo json_encode($pensionSchemes); ?>;
    
    schemeSelect.addEventListener('change', function() {
        const selectedSchemeId = this.value;
        
        if (selectedSchemeId) {
            // Find the selected scheme
            const selectedScheme = schemeData.find(scheme => scheme.scheme_id == selectedSchemeId);
            
            if (selectedScheme) {
                // Update scheme details
                document.querySelector('.scheme-name').textContent = selectedScheme.scheme_name;
                document.querySelector('.scheme-description').textContent = selectedScheme.description || 'No description available';
                document.querySelector('.scheme-eligibility').textContent = selectedScheme.eligibility_criteria || 'Contact the scheme provider for eligibility details';
                document.querySelector('.scheme-benefit').textContent = selectedScheme.benefit_amount;
                
                // Show the details
                schemeDetails.style.display = 'block';
            }
        } else {
            // Hide the details if no scheme is selected
            schemeDetails.style.display = 'none';
        }
    });
});
</script>
