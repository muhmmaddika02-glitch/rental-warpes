<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$errorMessage = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $membershipLevel = $_POST['membership_level'] ?? 'bronze';
    $points = (int) ($_POST['points'] ?? 0);
    
    $formData = $_POST;
    
    if (empty($name) || empty($email) || empty($password)) {
        $errorMessage = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errorMessage = 'Email already in use.';
            } else {
                $hashedPassword = generatePasswordHash($password);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, role, membership_level, points) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$name, $email, $phone ?: null, $hashedPassword, $role, $membershipLevel, $points])) {
                    $_SESSION['success_message'] = 'User created successfully!';
                    header('Location: dashboard.php?page=users');
                    exit;
                } else {
                    $errorMessage = 'Failed to create user.';
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
                <h3 class="card-title"><i class="bi bi-person-plus me-2"></i>Create New User</h3>
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
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required
                                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="customer" <?= ($formData['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="staff" <?= ($formData['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= ($formData['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Membership Level</label>
                            <select class="form-select" name="membership_level">
                                <option value="bronze" <?= ($formData['membership_level'] ?? '') === 'bronze' ? 'selected' : '' ?>>Bronze</option>
                                <option value="silver" <?= ($formData['membership_level'] ?? '') === 'silver' ? 'selected' : '' ?>>Silver</option>
                                <option value="gold" <?= ($formData['membership_level'] ?? '') === 'gold' ? 'selected' : '' ?>>Gold</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="points" min="0"
                                   value="<?= htmlspecialchars($formData['points'] ?? '0') ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <a href="dashboard.php?page=users" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>