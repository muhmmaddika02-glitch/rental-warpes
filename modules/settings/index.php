<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$successMessage = '';
$errorMessage = '';

try {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_key ASC");
    $settings = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        try {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([trim($value), $key]);
        } catch (PDOException $e) {}
    }
    $successMessage = 'Settings saved successfully.';
    
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_key ASC");
    $settings = $stmt->fetchAll();
}
?>

<?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="bi bi-gear me-2"></i>System Settings</h3></div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row">
                <?php foreach ($settings as $setting): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= escape($setting['description'] ?: $setting['setting_key']) ?></label>
                        <input type="text" class="form-control" name="settings[<?= escape($setting['setting_key']) ?>]" 
                               value="<?= escape($setting['setting_value']) ?>">
                        <small class="text-muted">Key: <?= escape($setting['setting_key']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Settings</button>
            </div>
        </form>
    </div>
</div>