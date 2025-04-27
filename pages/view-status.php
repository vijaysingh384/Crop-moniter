<?php
// View Status Page for Citizen
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'citizen') {
    header("Location: index.php?page=login");
    exit;
}

// Get citizen data
$userId = $_SESSION['user_id'];
$citizenData = null;
$citizenId = null;

try {
    $stmt = $db->prepare("
        SELECT c.*, u.email, u.created_at
        FROM citizen_profiles c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $citizenData = $stmt->fetch();
    
    if (is_array($citizenData)) {
        $citizenId = $citizenData['citizen_id'];
    }
} catch (PDOException $e) {
    error_log("Error fetching citizen data: " . $e->getMessage());
}

// Get all claims for this citizen
$claims = [];
try {
    if ($citizenId) {
        $stmt = $db->prepare("
            SELECT pc.*, ps.scheme_name, ps.scheme_provider, ps.benefit_amount,
                   u.username as admin_username
            FROM pension_claims pc
            JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
            LEFT JOIN users u ON pc.reviewed_by = u.user_id
            WHERE pc.citizen_id = ?
            ORDER BY pc.application_date DESC
        ");
        $stmt->execute([$citizenId]);
        $claims = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching claims: " . $e->getMessage());
}

// If a specific claim ID is provided, get details for that claim
$selectedClaim = null;
if (isset($_GET['claim_id']) && !empty($_GET['claim_id'])) {
    $claimId = (int)$_GET['claim_id'];
    
    try {
        $stmt = $db->prepare("
            SELECT pc.*, ps.scheme_name, ps.scheme_provider, ps.benefit_amount, ps.description,
                   ps.eligibility_criteria, u.username as admin_username
            FROM pension_claims pc
            JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id
            LEFT JOIN users u ON pc.reviewed_by = u.user_id
            WHERE pc.claim_id = ? AND pc.citizen_id = ?
        ");
        $stmt->execute([$claimId, $citizenId]);
        $selectedClaim = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching claim details: " . $e->getMessage());
    }
}

// Status update tracking
$trackingSteps = [
    'pending' => [
        'name' => 'Application Submitted',
        'description' => 'Your application has been received and is awaiting review by our team.',
        'date' => null,
        'icon' => 'fas fa-file-alt',
        'complete' => true
    ],
    'verification' => [
        'name' => 'Document Verification',
        'description' => 'Your documents are being verified by our team.',
        'date' => null,
        'icon' => 'fas fa-search',
        'complete' => false
    ],
    'review' => [
        'name' => 'Application Review',
        'description' => 'Your application is being reviewed by a pension officer.',
        'date' => null,
        'icon' => 'fas fa-user-check',
        'complete' => false
    ],
    'decision' => [
        'name' => 'Final Decision',
        'description' => 'A final decision has been made on your application.',
        'date' => null,
        'icon' => 'fas fa-clipboard-check',
        'complete' => false
    ]
];
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Application Status</h1>
            <p class="text-muted">View the status of your pension applications</p>
        </div>
    </div>
    
    <?php if (empty($claims)): ?>
        <div class="alert alert-info">
            <h5>No Applications Found</h5>
            <p>You haven't submitted any pension applications yet. Visit the <a href="index.php?page=pension-verification">Pension Verification</a> page to apply.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php if ($selectedClaim && is_array($selectedClaim)): ?>
                <!-- Claim Details View -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Application Details</h5>
                            <a href="index.php?page=view-status" class="btn btn-sm btn-outline-secondary">Back to All Applications</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <h4><?php echo htmlspecialchars($selectedClaim['scheme_name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($selectedClaim['scheme_provider']); ?></p>
                                    
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <p><strong>Application Date:</strong><br>
                                            <?php echo date('F j, Y', strtotime($selectedClaim['application_date'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong><br>
                                            <?php 
                                            switch ($selectedClaim['claim_status']) {
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
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($selectedClaim['claim_status'] === 'approved'): ?>
                                        <div class="alert alert-success mt-3">
                                            <h5><i class="fas fa-check-circle"></i> Congratulations!</h5>
                                            <p>Your pension application has been approved. You will receive a benefit amount of <?php echo htmlspecialchars($selectedClaim['benefit_amount']); ?>.</p>
                                            <p>Please visit your nearest pension office with your ID proof to complete the final verification process.</p>
                                        </div>
                                    <?php elseif ($selectedClaim['claim_status'] === 'rejected'): ?>
                                        <div class="alert alert-danger mt-3">
                                            <h5><i class="fas fa-times-circle"></i> Application Rejected</h5>
                                            <p>We regret to inform you that your pension application has been rejected.</p>
                                            <?php if (!empty($selectedClaim['admin_comments'])): ?>
                                                <p><strong>Reason:</strong> <?php echo htmlspecialchars($selectedClaim['admin_comments']); ?></p>
                                            <?php endif; ?>
                                            <p>If you believe this is an error, please visit your nearest pension office or contact our support team for assistance.</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-4">
                                        <h5>Scheme Details</h5>
                                        <p><?php echo htmlspecialchars($selectedClaim['description'] ?? 'No description available'); ?></p>
                                        
                                        <h6>Eligibility Criteria</h6>
                                        <p><?php echo htmlspecialchars($selectedClaim['eligibility_criteria'] ?? 'Please contact the scheme provider for eligibility details.'); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($selectedClaim['notes'])): ?>
                                        <div class="mt-4">
                                            <h5>Your Notes</h5>
                                            <p><?php echo nl2br(htmlspecialchars($selectedClaim['notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($selectedClaim['reviewed_by'] && !empty($selectedClaim['admin_comments'])): ?>
                                        <div class="mt-4">
                                            <h5>Admin Comments</h5>
                                            <p><?php echo nl2br(htmlspecialchars($selectedClaim['admin_comments'])); ?></p>
                                            <p class="text-muted">Reviewed by: <?php echo htmlspecialchars($selectedClaim['admin_username'] ?? 'System'); ?> 
                                               on <?php echo date('F j, Y', strtotime($selectedClaim['reviewed_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-5">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Application Timeline</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            // Update tracking steps based on claim status
                                            $trackingSteps['pending']['date'] = $selectedClaim['application_date'];
                                            
                                            if ($selectedClaim['claim_status'] !== 'pending') {
                                                $trackingSteps['verification']['complete'] = true;
                                                $trackingSteps['verification']['date'] = date('Y-m-d H:i:s', strtotime($selectedClaim['application_date'] . ' +2 days'));
                                                
                                                $trackingSteps['review']['complete'] = true;
                                                $trackingSteps['review']['date'] = date('Y-m-d H:i:s', strtotime($selectedClaim['application_date'] . ' +5 days'));
                                                
                                                if (in_array($selectedClaim['claim_status'], ['approved', 'rejected'])) {
                                                    $trackingSteps['decision']['complete'] = true;
                                                    $trackingSteps['decision']['date'] = $selectedClaim['reviewed_at'] ?? date('Y-m-d H:i:s', strtotime($selectedClaim['application_date'] . ' +7 days'));
                                                }
                                            }
                                            ?>
                                            
                                            <div class="timeline">
                                                <?php foreach ($trackingSteps as $step): ?>
                                                <div class="timeline-item <?php echo $step['complete'] ? 'complete' : ''; ?>">
                                                    <div class="timeline-marker">
                                                        <i class="<?php echo $step['icon']; ?>"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-1"><?php echo $step['name']; ?></h6>
                                                        <p class="mb-0 small"><?php echo $step['description']; ?></p>
                                                        <?php if ($step['date']): ?>
                                                        <p class="text-muted small mt-1"><?php echo date('M j, Y', strtotime($step['date'])); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Claims List View -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Your Applications</h5>
                        </div>
                        <div class="card-body">
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
                                        <?php foreach ($claims as $claim): ?>
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
                                                    <a href="index.php?page=view-status&claim_id=<?php echo $claim['claim_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Timeline Styling */
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.timeline-item.complete .timeline-marker {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.timeline-content {
    padding-bottom: 5px;
}
</style>
