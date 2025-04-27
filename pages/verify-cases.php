<?php
// session_start(); // Removed duplicate session_start()
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?page=login");
    exit;
}
require_once __DIR__ . '/../includes/config.php';

// Handle claim review
if (isset($_POST['claim_id'], $_POST['claim_status'], $_POST['admin_comments'])) {
    $claimId = (int)$_POST['claim_id'];
    $status = $_POST['claim_status'];
    $comments = $_POST['admin_comments'];
    $adminId = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE pension_claims SET claim_status=?, reviewed_by=?, admin_comments=?, reviewed_at=NOW() WHERE claim_id=?");
    $stmt->execute([$status, $adminId, $comments, $claimId]);
    header('Location: index.php?page=verify-cases');
    exit;
}

// Fetch pending/flagged claims
$stmt = $db->query("SELECT pc.*, cp.full_name, ps.scheme_name FROM pension_claims pc JOIN citizen_profiles cp ON pc.citizen_id = cp.citizen_id JOIN pension_schemes ps ON pc.scheme_id = ps.scheme_id WHERE pc.claim_status IN ('pending', 'flagged') ORDER BY pc.application_date DESC");
$claims = $stmt->fetchAll();
?>
<div class="container">
    <h1>Verify Pension Claims</h1>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Applicant</th>
                <th>Scheme</th>
                <th>Application Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($claims as $claim): ?>
                <tr>
                    <td><?php echo $claim['claim_id']; ?></td>
                    <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($claim['scheme_name']); ?></td>
                    <td><?php echo $claim['application_date']; ?></td>
                    <td><?php echo htmlspecialchars($claim['claim_status']); ?></td>
                    <td>
                        <button onclick="document.getElementById('review-<?php echo $claim['claim_id']; ?>').style.display='block'" class="btn btn-primary btn-sm">Review</button>
                        <div id="review-<?php echo $claim['claim_id']; ?>" style="display:none; background:#f9f9f9; padding:10px; margin-top:10px;">
                            <form method="post">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="claim_status" class="form-control" required>
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Admin Comments:</label>
                                    <textarea name="admin_comments" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">Submit</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('review-<?php echo $claim['claim_id']; ?>').style.display='none'">Cancel</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 