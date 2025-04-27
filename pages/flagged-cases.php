<?php
// session_start(); // Removed duplicate session_start()
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?page=login");
    exit;
}
require_once __DIR__ . '/../includes/config.php';

// Handle resolution
if (isset($_POST['flag_id'], $_POST['resolution_status'], $_POST['resolution_notes'])) {
    $flagId = (int)$_POST['flag_id'];
    $status = $_POST['resolution_status'];
    $notes = $_POST['resolution_notes'];
    $adminId = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE flagged_duplicates SET resolution_status=?, resolved_by=?, resolution_notes=?, resolution_date=NOW() WHERE flag_id=?");
    $stmt->execute([$status, $adminId, $notes, $flagId]);
    header('Location: index.php?page=flagged-cases');
    exit;
}

// Fetch flagged cases
$stmt = $db->query("SELECT fd.*, cp.full_name, cp.aadhaar_number FROM flagged_duplicates fd JOIN citizen_profiles cp ON fd.citizen_id = cp.citizen_id ORDER BY flagged_date DESC");
$cases = $stmt->fetchAll();
?>
<div class="container">
    <h1>Flagged Duplicate Pension Cases</h1>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Citizen</th>
                <th>Aadhaar</th>
                <th>Description</th>
                <th>Status</th>
                <th>Flagged Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cases as $case): ?>
                <tr>
                    <td><?php echo $case['flag_id']; ?></td>
                    <td><?php echo htmlspecialchars($case['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($case['aadhaar_number']); ?></td>
                    <td><?php echo htmlspecialchars($case['duplicate_description']); ?></td>
                    <td><?php echo htmlspecialchars($case['resolution_status']); ?></td>
                    <td><?php echo $case['flagged_date']; ?></td>
                    <td>
                        <?php if ($case['resolution_status'] === 'Pending'): ?>
                        <button onclick="document.getElementById('resolve-<?php echo $case['flag_id']; ?>').style.display='block'" class="btn btn-primary btn-sm">Resolve</button>
                        <div id="resolve-<?php echo $case['flag_id']; ?>" style="display:none; background:#f9f9f9; padding:10px; margin-top:10px;">
                            <form method="post">
                                <input type="hidden" name="flag_id" value="<?php echo $case['flag_id']; ?>">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="resolution_status" class="form-control" required>
                                        <option value="Approved">Approve</option>
                                        <option value="Rejected">Reject</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Resolution Notes:</label>
                                    <textarea name="resolution_notes" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">Submit</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('resolve-<?php echo $case['flag_id']; ?>').style.display='none'">Cancel</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-muted">Resolved</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 