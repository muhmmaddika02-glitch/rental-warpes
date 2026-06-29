<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flashMessage('Invalid user ID.', 'danger');
    header('Location: dashboard.php?page=users');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        flashMessage('User not found.', 'danger');
        header('Location: dashboard.php?page=users');
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $membershipLevel = $_POST['membership_level'] ?? 'bronze';
    $points = (int) ($_POST['points'] ?? 0);
    
    if (empty($name) || empty($email)) {
        $errorMessage = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetch()) {
                $errorMessage = 'Email already in use.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $errorMessage = 'Password must be at least 6 characters.';
                    } else {
                        $hashedPassword = generatePasswordHash($password);
                        $stmt = $pdo->prepare("
                            UPDATE users SET name = ?, email = ?, phone = ?, password = ?, role = ?, membership_level = ?, points = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $email, $phone ?: null, $hashedPassword, $role, $membershipLevel, $points, $id]);
                    }
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users SET name = ?, email = ?, phone = ?, role = ?, membership_level = ?, points = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone ?: null, $role, $membershipLevel, $points, $id]);
                }
                
                if (empty($errorMessage)) {
                    $_SESSION['success_message'] = 'User updated successfully!';
                    header('Location: dashboard.php?page=users');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pencil-square me-2"></i>Edit User</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password (leave empty to keep current)</label>
                            <input type="password" class="form-control" name="password" minlength="6">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="customer" <?= ($_POST['role'] ?? $user['role']) === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="staff" <?= ($_POST['role'] ?? $user['role']) === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= ($_POST['role'] ?? $user['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Membership</label>
                            <select class="form-select" name="membership_level">
                                <option value="bronze" <?= ($_POST['membership_level'] ?? $user['membership_level']) === 'bronze' ? 'selected' : '' ?>>Bronze</option>
                                <option value="silver" <?= ($_POST['membership_level'] ?? $user['membership_level']) === 'silver' ? 'selected' : '' ?>>Silver</option>
                                <option value="gold" <?= ($_POST['membership_level'] ?? $user['membership_level']) === 'gold' ? 'selected' : '' ?>>Gold</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="points" min="0"
                                   value="<?= htmlspecialchars($_POST['points'] ?? $user['points']) ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <a href="dashboard.php?page=users" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>