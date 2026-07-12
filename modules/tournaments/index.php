<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$totalPages = 1;
$tournaments = [];

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM tournaments");
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $perPage));

    $stmt = $pdo->query("
        SELECT t.*,
               (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) as total_participants
        FROM tournaments t
        ORDER BY t.start_date DESC
        LIMIT $perPage OFFSET $offset
    ");
    $tournaments = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}
?>
<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <?php if (isAdmin() || isStaff()): ?>
        <a href="dashboard.php?page=tournaments_create" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Create Tournament</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Game</th>
                        <th>Prize Pool</th>
                        <th>Participants</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tournaments)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No tournaments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tournaments as $t): ?>
                            <tr>
                                <td>#<?= $t['id'] ?></td>
                                <td><strong><?= escape($t['title']) ?></strong></td>
                                <td><?= escape($t['game_name']) ?></td>
                                <td><?= formatRupiah($t['prize_pool']) ?></td>
                                <td><?= $t['total_participants'] . '/' . ($t['max_participants'] ?? '∞') ?></td>
                                <td><?= formatDate($t['start_date'], 'd M Y H:i') ?></td>
                                <td><span class="badge bg-<?= $t['status'] === 'registration_open' ? 'success' : ($t['status'] === 'in_progress' ? 'primary' : ($t['status'] === 'completed' ? 'secondary' : 'warning')) ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
                                <td class="text-end">
                                    <?php if (isAdmin() || isStaff()): ?>
                                    <a href="dashboard.php?page=tournaments_edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if (isAdmin()): ?>
                                    <button onclick="if(confirm('Delete?')) window.location.href='dashboard.php?page=tournaments_delete&id=<?= $t['id'] ?>&csrf=<?= generateCsrfToken() ?>'" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                    <?php if (!isAdmin() && !isStaff()): ?>
                                    <a href="dashboard.php?page=tournaments_view&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> View</a>
                                    <?php if ($t['status'] === 'registration_open'): ?>
                                    <a href="dashboard.php?page=tournaments_register&id=<?= $t['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-person-plus"></i> Register</a>
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

<?= renderPagination($page, $totalPages, 'dashboard.php?page=tournaments') ?>
