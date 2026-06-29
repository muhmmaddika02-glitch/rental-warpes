<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([getCurrentUserId()]);
    $notifications = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([getCurrentUserId()]);
    
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}
?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-bell me-2"></i>Notifications</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        <p class="mb-0">No notifications yet</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="list-group-item <?= !$notif['is_read'] ? 'list-group-item-primary' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php
                                            $typeIcons = [
                                                'booking_confirmation' => 'bi-calendar-check text-primary',
                                                'payment_confirmation' => 'bi-credit-card text-success',
                                                'tournament_announcement' => 'bi-trophy text-warning',
                                                'promotion' => 'bi-megaphone text-info',
                                                'system' => 'bi-gear text-secondary'
                                            ];
                                            $icon = $typeIcons[$notif['type']] ?? 'bi-info-circle';
                                            ?>
                                            <i class="bi <?= $icon ?> me-2"></i>
                                            <?= escape($notif['title']) ?>
                                        </h6>
                                        <p class="mb-1"><?= escape($notif['message']) ?></p>
                                        <small class="text-muted"><?= formatDate($notif['created_at']) ?></small>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="badge bg-primary rounded-pill">New</span>
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