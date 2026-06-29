<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

try {
    $stmt = $pdo->query("
        SELECT u.*, 
               (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
               (SELECT SUM(total_price) FROM bookings WHERE user_id = u.id AND booking_status = 'completed') as total_spent
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Membership</th>
                        <th>Points</th>
                        <th>Bookings</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="10" class="text-center py-5 text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= escape($user['name']) ?></strong>
                                </td>
                                <td><?= escape($user['email']) ?></td>
                                <td><?= escape($user['phone'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $roleBadges = [
                                        'admin' => 'badge bg-danger',
                                        'staff' => 'badge bg-warning text-dark',
                                        'customer' => 'badge bg-success'
                                    ];
                                    ?>
                                    <span class="<?= $roleBadges[$user['role']] ?? 'badge bg-secondary' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= getMembershipLevelBadge($user['membership_level']) ?></td>
                                <td><?= number_format($user['points'], 0, ',', '.') ?></td>
                                <td><?= $user['total_bookings'] ?></td>
                                <td><?= formatRupiah($user['total_spent']) ?></td>
                                <td><?= formatDate($user['created_at'], 'd M Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>