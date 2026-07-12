<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$tournamentId = $_GET['id'] ?? null;

if (!$tournamentId || !is_numeric($tournamentId)) {
    flashMessage('Invalid tournament ID.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) as total_participants
        FROM tournaments t
        WHERE t.id = ?
    ");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();
    
    if (!$tournament) {
        flashMessage('Tournament not found.', 'danger');
        header('Location: dashboard.php?page=tournaments');
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name, u.email as user_email
        FROM participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.tournament_id = ?
        ORDER BY p.registration_date ASC
    ");
    $stmt->execute([$tournamentId]);
    $participants = $stmt->fetchAll();
    
    $isUserRegistered = false;
    if (isCustomer()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE tournament_id = ? AND user_id = ?");
        $stmt->execute([$tournamentId, getCurrentUserId()]);
        $isUserRegistered = $stmt->fetch()['count'] > 0;
    }
    
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

$canRegister = !$isUserRegistered 
    && $tournament['status'] === 'registration_open' 
    && ($tournament['max_participants'] === null || $tournament['total_participants'] < $tournament['max_participants'])
    && isCustomer();

$isFull = $tournament['max_participants'] !== null && $tournament['total_participants'] >= $tournament['max_participants'];
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0"><i class="bi bi-trophy me-2"></i><?= escape($tournament['title']) ?></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-2"><strong><i class="bi bi-controller me-2"></i>Game:</strong> <?= escape($tournament['game_name']) ?></p>
                        <p class="mb-2"><strong><i class="bi bi-calendar-event me-2"></i>Start Date:</strong> <?= formatDate($tournament['start_date'], 'd M Y H:i') ?></p>
                        <p class="mb-2"><strong><i class="bi bi-calendar-check me-2"></i>End Date:</strong> <?= formatDate($tournament['end_date'], 'd M Y H:i') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong><i class="bi bi-trophy-fill me-2"></i>Prize Pool:</strong> <?= formatRupiah($tournament['prize_pool']) ?></p>
                        <p class="mb-2"><strong><i class="bi bi-cash me-2"></i>Registration Fee:</strong> <?= $tournament['registration_fee'] > 0 ? formatRupiah($tournament['registration_fee']) : 'Free' ?></p>
                        <p class="mb-2"><strong><i class="bi bi-people-fill me-2"></i>Participants:</strong> <?= $tournament['total_participants'] ?><?= $tournament['max_participants'] ? '/' . $tournament['max_participants'] : '' ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-info-circle me-2"></i>Status</h5>
                    <span class="badge bg-<?= $tournament['status'] === 'registration_open' ? 'success' : ($tournament['status'] === 'in_progress' ? 'primary' : ($tournament['status'] === 'completed' ? 'secondary' : 'warning')) ?> fs-6">
                        <?= ucfirst(str_replace('_', ' ', $tournament['status'])) ?>
                    </span>
                    
                    <?php if ($isUserRegistered): ?>
                        <span class="badge bg-info fs-6 ms-2"><i class="bi bi-check-circle me-1"></i>You are registered</span>
                    <?php endif; ?>
                    
                    <?php if ($isFull): ?>
                        <span class="badge bg-danger fs-6 ms-2"><i class="bi bi-x-circle me-1"></i>Registration Full</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($tournament['description']): ?>
                <div class="mb-4">
                    <h5><i class="bi bi-file-text me-2"></i>Description</h5>
                    <p class="text-muted"><?= nl2br(escape($tournament['description'])) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <a href="dashboard.php?page=tournaments" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Tournaments
                    </a>
                    
                    <?php if ($canRegister): ?>
                        <a href="dashboard.php?page=tournaments_register&id=<?= $tournament['id'] ?>" class="btn btn-success">
                            <i class="bi bi-person-plus me-1"></i> Register Now
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($isUserRegistered && $tournament['status'] === 'registration_open'): ?>
                        <button onclick="if(confirm('Are you sure you want to unregister from this tournament?')) window.location.href='dashboard.php?page=tournaments_unregister&id=<?= $tournament['id'] ?>&csrf=<?= generateCsrfToken() ?>'" class="btn btn-warning">
                            <i class="bi bi-person-dash me-1"></i> Unregister
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-people me-2"></i>Registered Participants</h5>
            </div>
            <div class="card-body">
                <?php if (empty($participants)): ?>
                    <p class="text-muted text-center py-3">No participants yet. Be the first to register!</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($participants as $index => $participant): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge bg-secondary rounded-circle" style="width:32px;height:32px;line-height:32px;"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?= escape($participant['user_name']) ?></strong>
                                        <?php if (isAdmin() || isStaff()): ?>
                                            <br><small class="text-muted"><?= escape($participant['user_email']) ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted">Registered: <?= formatDate($participant['registration_date'], 'd M Y H:i') ?></small>
                                    </div>
                                    <?php if ($participant['user_id'] === getCurrentUserId()): ?>
                                        <span class="badge bg-info">You</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
