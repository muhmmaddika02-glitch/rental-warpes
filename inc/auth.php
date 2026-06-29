<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isRole(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function isAdmin(): bool {
    return isRole('admin');
}

function isStaff(): bool {
    return isRole('staff');
}

function isCustomer(): bool {
    return isRole('customer');
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName(): string {
    return $_SESSION['user_name'] ?? 'Unknown';
}

function getCurrentUserEmail(): string {
    return $_SESSION['user_email'] ?? 'Unknown';
}

function getCurrentUserRole(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

function getCurrentUserMembership(): string {
    return $_SESSION['membership_level'] ?? 'bronze';
}

function getCurrentUserPoints(): int {
    return $_SESSION['points'] ?? 0;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (!isRole($role)) {
        $_SESSION['error_message'] = 'Access denied. Insufficient permissions.';
        header('Location: dashboard.php');
        exit;
    }
}

function requireAdmin(): void {
    requireRole('admin');
}

function requireStaffOrAdmin(): void {
    requireLogin();
    if (!isStaff() && !isAdmin()) {
        $_SESSION['error_message'] = 'Access denied. Staff or Admin access required.';
        header('Location: dashboard.php');
        exit;
    }
}

function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function flashMessage(string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

function generatePasswordHash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}
