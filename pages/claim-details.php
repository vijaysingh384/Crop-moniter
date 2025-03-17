<?php
// Claim Details Page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'citizen') {
    header("Location: index.php?page=login");
    exit;
}

// Redirect to view-status page if no claim ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?page=view-status");
    exit;
}

$claimId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$citizenId = null;

// Get citizen ID
try {
    $stmt = $db->prepare("SELECT citizen_id FROM citizen_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $citizenData = $stmt->fetch();
    
    if ($citizenData) {
        $citizenId = $citizenData['citizen_id'];
    } else {
        // If no citizen profile found, redirect to home
        header("Location: index.php?page=home");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching citizen data: " . $e->getMessage());
    header("Location: index.php?page=view-status");
    exit;
}

// Get claim details
$claim = null;
try {
    $stmt = $db->prepare("
        SELECT pc.*, ps.scheme_name, ps.scheme_provider, ps.benefit_amount, 
               ps.description, ps.eligibility_criteria, u.username as admin_username
        FROM pension_claims pc
        JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
        LEFT JOIN users u ON pc.reviewed_by = u.user_id
        WHERE pc.claim_id = ? AND pc.citizen_id = ?
    ");
    $stmt->execute([$claimId, $citizenId]);
    $claim = $stmt->fetch();
    
    if (!$claim) {
        // If no claim found or claim doesn't belong to the user, redirect
        header("Location: index.php?page=view-status");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching claim details: " . $e->getMessage());
    header("Location: index.php?page=view-status");
    exit;
}

// Handle document upload
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    // This functionality would require file upload handling
    // For now, we'll just show a message
    $successMessage = "Document upload functionality will be implemented soon.";
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Claim Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php?page=pension-verification">Pension Verification</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=view-status">Application Status</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Claim #<?php echo $claimId; ?></li>
                </ol>
            </nav>
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
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($claim['scheme_name']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Scheme Provider</h6>
                        <p><?php echo htmlspecialchars($claim['scheme_provider']); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p><?php echo htmlspecialchars($claim['description'] ?? 'No description available'); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Eligibility Criteria</h6>
                        <p><?php echo htmlspecialchars($claim['eligibility_criteria'] ?? 'Please contact the scheme provider for eligibility details.'); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Benefit Amount</h6>
                        <p><?php echo htmlspecialchars($claim['benefit_amount']); ?></p>
                    </div>
                    
                    <?php if (!empty($claim['notes'])): ?>
                        <div class="mb-4">
                            <h6>Your Notes</h6>
                            <p><?php echo nl2br(htmlspecialchars($claim['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($claim['reviewed_by'] && !empty($claim['admin_comments'])): ?>
                        <div class="mb-4">
                            <h6>Admin Comments</h6>
                            <div class="alert alert-info">
                                <p><?php echo nl2br(htmlspecialchars($claim['admin_comments'])); ?></p>
                                <p class="mb-0 text-muted small">Reviewed by: <?php echo htmlspecialchars($claim['admin_username'] ?? 'System'); ?> 
                                   on <?php echo date('F j, Y', strtotime($claim['reviewed_at'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($claim['claim_status'] === 'pending' || $claim['claim_status'] === 'flagged'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Supporting Documents</h5>
                    </div>
                    <div class="card-body">
                        <p>Please upload any supporting documents that may help verify your eligibility for this scheme.</p>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type</label>
                                <select class="form-control" id="document_type" name="document_type" required>
                                    <option value="">-- Select Document Type --</option>
                                    <option value="id_proof">ID Proof (Aadhaar/PAN/Voter ID)</option>
                                    <option value="residence_proof">Residence Proof</option>
                                    <option value="income_certificate">Income Certificate</option>
                                    <option value="age_proof">Age Proof</option>
                                    <option value="bank_details">Bank Account Details</option>
                                    <option value="other">Other Supporting Document</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_file" class="form-label">Select File</label>
                                <input type="file" class="form-control" id="document_file" name="document_file" required>
                                <div class="form-text">Accepted formats: PDF, JPG, PNG (Max size: 5MB)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="document_description" name="document_description" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="upload_document" class="btn btn-primary">Upload Document</button>
                        </form>
                        
                        <div class="mt-4">
                            <h6>Uploaded Documents</h6>
                            <p class="text-muted">No documents have been uploaded yet.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Application Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 80px;"><strong>Status:</strong></div>
                        <div>
                            <?php 
                            switch ($claim['claim_status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning">Pending Review</span>';
                                    break;
                                case 'approved':
                                    echo '<span class="badge bg-success">Approved</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="badge bg-danger">Rejected</span>';
                                    break;
                                case 'flagged':
                                    echo '<span class="badge bg-warning">Flagged for Review</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">Unknown</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div style="width: 80px;"><strong>Applied:</strong></div>
                        <div><?php echo date('F j, Y', strtotime($claim['application_date'])); ?></div>
                    </div>
                    
                    <?php if ($claim['reviewed_at']): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div style="width: 80px;"><strong>Reviewed:</strong></div>
                            <div><?php echo date('F j, Y', strtotime($claim['reviewed_at'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($claim['claim_status'] === 'approved'): ?>
                        <div class="alert alert-success mt-3">
                            <h6><i class="fas fa-check-circle"></i> Application Approved</h6>
                            <p class="mb-0">Congratulations! Your application has been approved.</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Next Steps</h6>
                            <ol class="ps-3">
                                <li>Visit your nearest pension office with your original documents</li>
                                <li>Complete biometric verification</li>
                                <li>Submit bank details for benefit transfer</li>
                            </ol>
                        </div>
                    <?php elseif ($claim['claim_status'] === 'rejected'): ?>
                        <div class="alert alert-danger mt-3">
                            <h6><i class="fas fa-times-circle"></i> Application Rejected</h6>
                            <p class="mb-0">We're sorry, but your application has been rejected.</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>What You Can Do</h6>
                            <ul class="ps-3">
                                <li>Review the admin comments for the reason</li>
                                <li>Visit your nearest pension office for clarification</li>
                                <li>You may reapply after 3 months with updated documentation</li>
                            </ul>
                        </div>
                    <?php elseif ($claim['claim_status'] === 'flagged'): ?>
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-exclamation-triangle"></i> Additional Information Required</h6>
                            <p class="mb-0">Your application requires additional documentation or clarification.</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>What You Need To Do</h6>
                            <ul class="ps-3">
                                <li>Review admin comments for specific requirements</li>
                                <li>Upload the requested documents</li>
                                <li>Your application will be reviewed again after submission</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Application Under Review</h6>
                            <p class="mb-0">Your application is currently being processed. The typical review period is 7-10 working days.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="index.php?page=view-status" class="btn btn-outline-secondary btn-sm">Back to All Applications</a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Help & Support</h5>
                </div>
                <div class="card-body">
                    <p>Need help with your application?</p>
                    <ul class="ps-3">
                        <li>Call our helpline: <strong>1800-XXX-XXXX</strong></li>
                        <li>Email: <strong>support@govservices.in</strong></li>
                        <li>Visit your nearest pension office</li>
                    </ul>
                    
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
