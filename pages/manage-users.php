<?php
// session_start(); // Removed duplicate session_start()
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?page=login");
    exit;
}
require_once __DIR__ . '/../includes/config.php';

// Handle user actions (activate, deactivate, delete)
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    if ($userId !== $_SESSION['user_id']) { // Prevent self-modification
        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
        } elseif ($action === 'deactivate') {
            $stmt = $db->prepare("UPDATE users SET user_type = 'deactivated' WHERE user_id = ?");
            $stmt->execute([$userId]);
        } elseif ($action === 'activate') {
            // You may want to store previous type in a separate column for real systems
            $stmt = $db->prepare("UPDATE users SET user_type = 'citizen' WHERE user_id = ? AND user_type = 'deactivated'");
            $stmt->execute([$userId]);
        }
    }
    header('Location: index.php?page=manage-users');
    exit;
}

// Fetch all users
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<div class="container">
    <h1>Manage Users</h1>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Created At</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                    <td><?php echo $user['created_at']; ?></td>
                    <td><?php echo $user['last_login']; ?></td>
                    <td>
                        <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                            <?php if ($user['user_type'] !== 'deactivated'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" class="btn btn-warning btn-sm">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">(You)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 