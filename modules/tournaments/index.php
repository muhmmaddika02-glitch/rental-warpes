<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

try {
    $stmt = $pdo->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) as total_participants
        FROM tournaments t
        ORDER BY t.start_date DESC
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
        <a href="dashboard.php?page=tournaments_create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Create Tournament
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light">
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
                                    <a href="dashboard.php?page=tournaments_edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <button onclick="if(confirm('Delete?')) window.location.href='dashboard.php?page=tournaments_delete&id=<?= $t['id'] ?>'" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>