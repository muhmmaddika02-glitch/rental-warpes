<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

require_once __DIR__ . '/../inc/auth.php';
global $pdo;

$errorMessage = '';
$successMessage = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
    
    if (!$user) {
        flashMessage('User not found.', 'danger');
        header('Location: logout.php');
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $errorMessage = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Invalid email format.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $stmt->execute([$email, getCurrentUserId()]);
                if ($stmt->fetch()) {
                    $errorMessage = 'Email already in use.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, getCurrentUserId()]);
                    
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                    
                    $successMessage = 'Profile updated successfully.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database error.';
            }
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            $errorMessage = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'Password must be at least 6 characters.';
        } elseif (!verifyPassword($currentPassword, $user['password'])) {
            $errorMessage = 'Current password is incorrect.';
        } else {
            try {
                $hashedPassword = generatePasswordHash($newPassword);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, getCurrentUserId()]);
                $successMessage = 'Password changed successfully.';
            } catch (PDOException $e) {
                $errorMessage = 'Database error.';
            }
        }
    }
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-person me-2"></i>Profile Information</h3></div>
            <div class="card-body">
                <?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>
                <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="<?= escape($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= escape($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= escape($user['phone'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-award me-2"></i>Membership</h3></div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <?= getMembershipLevelBadge($user['membership_level'] ?? 'bronze') ?>
                    </div>
                    <div>
                        <strong><?= number_format($user['points'] ?? 0, 0, ',', '.') ?> Points</strong>
                    </div>
                </div>
                <p class="small text-muted mb-0">
                    Earn 1 point for every Rp10,000 spent. Redeem points for game time or discounts.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-lock me-2"></i>Change Password</h3></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="current_password" id="current_password" required>
                            <button type="button" onclick="togglePass('current_password',this)" class="btn btn-outline-secondary"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                            <button type="button" onclick="togglePass('new_password',this)" class="btn btn-outline-secondary"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="6">
                            <button type="button" onclick="togglePass('confirm_password',this)" class="btn btn-outline-secondary"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-clock-history me-2"></i>Account Info</h3></div>
            <div class="card-body">
                <p><strong>Role:</strong> <?php
                    $roleTone = $user['role'] === 'admin' ? 'red' : ($user['role'] === 'staff' ? 'gold' : 'green');
                    ?><span class="badge-neon badge-<?= $roleTone ?>"><?= ucfirst($user['role']) ?></span></p>
                <p><strong>Member Since:</strong> <?= formatDate($user['created_at'], 'd M Y') ?></p>
                <p class="mb-0"><strong>Last Updated:</strong> <?= formatDate($user['updated_at'], 'd M Y H:i') ?></p>
            </div>
        </div>
    </div>
</div>