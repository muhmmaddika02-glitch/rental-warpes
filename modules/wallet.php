<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireLogin();
global $pdo;

$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT points, membership_level FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$points = (int) ($user['points'] ?? 0);
$memberLevel = $user['membership_level'] ?? 'bronze';

$topupOptions = [50 => 50000, 100 => 100000, 200 => 200000, 500 => 500000];
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup'])) {
    $amount = (int) ($_POST['amount'] ?? 0);
    if ($amount <= 0 || !array_key_exists($amount, $topupOptions)) {
        $errorMessage = 'Invalid top-up amount.';
    } else {
        $bonus = $memberLevel === 'gold' ? 0.15 : ($memberLevel === 'silver' ? 0.08 : 0);
        $bonusPoints = (int) round($amount * $bonus);
        $totalPoints = $amount + $bonusPoints;
        try {
            $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $stmt->execute([$totalPoints, $userId]);
            addNotification($userId, 'Top-up Successful', "Saldo +{$totalPoints} poin (termasuk bonus {$bonusPoints})", 'system');
            $successMessage = "Top-up Rp " . number_format($amount * 1000) . " berhasil! (+{$bonusPoints} bonus poin)";
            $points += $totalPoints;
        } catch (PDOException $e) {
            $errorMessage = 'Database error.';
        }
    }
}
?>
<div class="row">
    <div class="col-lg-4">
        <div class="card text-center p-4">
            <div class="display-3 mb-2" style="color:var(--neon-cyan);">💰</div>
            <h2 class="stat-neon-value" style="font-size:2.5rem"><?= number_format($points) ?></h2>
            <p class="text-muted">Poin Saldo</p>
            <p class="small text-muted">1 poin = Rp 1.000</p>
            <span class="badge-neon badge-<?= $memberLevel === 'gold' ? 'gold' : ($memberLevel === 'silver' ? 'cyan' : 'orange') ?> mx-auto">
                <?= ucfirst($memberLevel) ?>
            </span>
            <?php if ($memberLevel !== 'bronze'): ?>
                <p class="small text-success mt-2">Bonus top-up <?= $memberLevel === 'gold' ? '15%' : '8%' ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top-up Saldo</h3></div>
            <div class="card-body">
                <?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>
                <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <?php foreach ($topupOptions as $pts => $rp): ?>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="amount" id="topup_<?= $pts ?>" value="<?= $pts ?>" autocomplete="off" <?= $pts === 50 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-info w-100 py-4 text-center" for="topup_<?= $pts ?>">
                                <div class="fw-bold fs-5"><?= number_format($pts) ?> Poin</div>
                                <div class="small">Rp <?= number_format($rp) ?></div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="topup" class="btn btn-primary btn-lg w-100">Top-up Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
