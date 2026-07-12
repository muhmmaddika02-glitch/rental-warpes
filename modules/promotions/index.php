<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM promotions");
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $perPage));

    $stmt = $pdo->prepare("SELECT * FROM promotions ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $promotions = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}
?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="text-end mb-3">
    <?php if (isAdmin()): ?>
    <a href="dashboard.php?page=promotions_create" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Add Promotion</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Discount</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($promotions)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No promotions.</td></tr>
                    <?php else: ?>
                        <?php foreach ($promotions as $p): ?>
                            <tr>
                                <td><strong><?= escape($p['code'] ?? '-') ?></strong></td>
                                <td><?= escape($p['title']) ?></td>
                                <td><?= $p['discount_percentage'] ? $p['discount_percentage'] . '%' : formatRupiah($p['discount_amount']) ?></td>
                                <td><?= formatDate($p['start_date'], 'd M Y') ?></td>
                                <td><?= formatDate($p['end_date'], 'd M Y') ?></td>
                                <td><?= $p['usage_limit'] ?? '∞' ?></td>
                                <td><?= $p['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                                <td class="text-end">
                                    <?php if (isAdmin()): ?>
                                    <a href="dashboard.php?page=promotions_edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <button onclick="if(confirm('Delete promotion?')) window.location.href='dashboard.php?page=promotions_delete&id=<?= $p['id'] ?>&csrf=<?= generateCsrfToken() ?>'" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    <?php else: ?>
                                    <a href="dashboard.php?page=promotions_view&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> View</a>
                                    <?php if ($p['is_active'] && $p['code']): ?>
                                    <button onclick="copyPromoCode('<?= htmlspecialchars($p['code']) ?>')" class="btn btn-sm btn-success"><i class="bi bi-clipboard"></i> Copy Code</button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= renderPagination($page, $totalPages, 'dashboard.php?page=promotions') ?>

<script>
function copyPromoCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Promo code "' + code + '" copied to clipboard!');
    }, function() {
        prompt('Copy this promo code:', code);
    });
}
</script>
