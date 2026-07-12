<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$users = [];
$totalPages = 1;

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $perPage));

    $stmt = $pdo->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
               (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE user_id = u.id AND booking_status = 'completed') as total_spent
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>
<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash['text']) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="dashboard.php?page=users_create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add User</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="10" class="text-center py-5 text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><strong><?= escape($user['name']) ?></strong></td>
                                <td><?= escape($user['email']) ?></td>
                                <td><?= escape($user['phone'] ?? '-') ?></td>
                                <td>
                                    <?php $roleBadges = ['admin' => 'badge bg-danger','staff' => 'badge bg-warning','customer' => 'badge bg-success']; ?>
                                    <span class="<?= $roleBadges[$user['role']] ?? 'badge bg-secondary' ?>"><?= ucfirst($user['role']) ?></span>
                                </td>
                                <td><?= getMembershipLevelBadge($user['membership_level']) ?></td>
                                <td><?= number_format($user['points'], 0, ',', '.') ?></td>
                                <td><?= $user['total_bookings'] ?></td>
                                <td><?= formatRupiah($user['total_spent']) ?></td>
                                <td><?= formatDate($user['created_at'], 'd M Y') ?></td>
                                <td>
                                    <a href="dashboard.php?page=users_edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="dashboard.php?page=users_delete&id=<?= $user['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete user?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= renderPagination($page, $totalPages, 'dashboard.php?page=users') ?>
