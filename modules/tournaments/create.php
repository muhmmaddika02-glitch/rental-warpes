<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

$errorMessage = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $gameName = trim($_POST['game_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prizePool = trim($_POST['prize_pool'] ?? '0');
    $registrationFee = trim($_POST['registration_fee'] ?? '0');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $maxParticipants = trim($_POST['max_participants'] ?? '');
    $status = $_POST['status'] ?? 'upcoming';
    $allowedStatus = ['upcoming', 'registration_open', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatus, true)) { $status = 'upcoming'; }

    $formData = $_POST;
    
    if (empty($title) || empty($gameName) || empty($startDate) || empty($endDate)) {
        $errorMessage = 'Title, game name, start date, and end date are required.';
    } elseif (!is_numeric($prizePool) || $prizePool < 0) {
        $errorMessage = 'Prize pool must be a non-negative number.';
    } elseif (!is_numeric($registrationFee) || $registrationFee < 0) {
        $errorMessage = 'Registration fee must be a non-negative number.';
    } elseif (!empty($maxParticipants) && (!is_numeric($maxParticipants) || $maxParticipants <= 0)) {
        $errorMessage = 'Max participants must be a positive number.';
    } elseif (strtotime($startDate) === false || strtotime($endDate) === false) {
        $errorMessage = 'Invalid date format.';
    } elseif (strtotime($endDate) <= strtotime($startDate)) {
        $errorMessage = 'End date must be after start date.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tournaments (title, game_name, description, prize_pool, registration_fee, start_date, end_date, max_participants, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $maxParticipantsValue = empty($maxParticipants) ? null : (int)$maxParticipants;
            
            if ($stmt->execute([$title, $gameName, $description, $prizePool, $registrationFee, $startDate, $endDate, $maxParticipantsValue, $status])) {
                flashMessage('Tournament created successfully!', 'success');
                header('Location: dashboard.php?page=tournaments');
                exit;
            } else {
                $errorMessage = 'Failed to create tournament.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}

$statusOptions = ['upcoming', 'registration_open', 'in_progress', 'completed', 'cancelled'];
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-plus-circle me-2"></i>Create New Tournament</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Tournament Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
                                   placeholder="e.g., Summer Championship 2026">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="game_name" class="form-label">Game Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="game_name" name="game_name" required
                                   value="<?= htmlspecialchars($formData['game_name'] ?? '') ?>"
                                   placeholder="e.g., Call of Duty, FIFA 24">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Tournament details and rules..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="prize_pool" class="form-label">Prize Pool (Rp)</label>
                            <input type="number" class="form-control" id="prize_pool" name="prize_pool" step="0.01" min="0"
                                   value="<?= htmlspecialchars($formData['prize_pool'] ?? '0') ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="registration_fee" class="form-label">Registration Fee (Rp)</label>
                            <input type="number" class="form-control" id="registration_fee" name="registration_fee" step="0.01" min="0"
                                   value="<?= htmlspecialchars($formData['registration_fee'] ?? '0') ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="max_participants" class="form-label">Max Participants</label>
                            <input type="number" class="form-control" id="max_participants" name="max_participants" min="1"
                                   value="<?= htmlspecialchars($formData['max_participants'] ?? '') ?>"
                                   placeholder="Leave empty for unlimited">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required
                                   value="<?= htmlspecialchars($formData['start_date'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required
                                   value="<?= htmlspecialchars($formData['end_date'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= $option ?>" <?= ($formData['status'] ?? 'upcoming') === $option ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $option)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Create Tournament
                        </button>
                        <a href="dashboard.php?page=tournaments" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
