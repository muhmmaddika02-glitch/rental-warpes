<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && $token && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function formatRupiah($amount): string {
    if ($amount === null) {
        return 'Rp 0';
    }
    $amount = (float) $amount;
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate(?string $date, string $format = 'd M Y H:i'): string {
    if ($date === null) {
        return '-';
    }
    return date($format, strtotime($date));
}

function generateInvoiceNumber(int $bookingId): string {
    return 'INV-' . date('Ymd') . '-' . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
}

function calculateBookingPrice(float $pricePerHour, int $durationHours): float {
    return $pricePerHour * $durationHours;
}

function checkDeviceAvailability(int $deviceId, string $bookingDate, string $startTime, int $durationHours): bool {
    global $pdo;
    
    $startTs = strtotime($bookingDate . ' ' . $startTime);
    $endTs = $startTs + ($durationHours * 3600);
    $startDt = date('Y-m-d H:i:s', $startTs);
    $endDt = date('Y-m-d H:i:s', $endTs);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings 
        WHERE device_id = :device_id 
        AND booking_status IN ('pending', 'confirmed', 'playing')
        AND NOT (
            (:end_dt <= CONCAT(booking_date, ' ', start_time))
            OR (:start_dt >= CONCAT(booking_date, ' ', ADDTIME(start_time, SEC_TO_TIME(duration_hours * 3600))))
        )
    ");
    
    $stmt->execute([
        'device_id' => $deviceId,
        'end_dt' => $endDt,
        'start_dt' => $startDt,
    ]);
    $result = $stmt->fetch();
    
    return (int) $result['total'] === 0;
}

function addNotification(int $userId, string $title, string $message, string $type = 'system'): bool {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type) 
        VALUES (?, ?, ?, ?)
    ");
    
    return $stmt->execute([$userId, $title, $message, $type]);
}

function getDeviceStatusBadge(string $status): string {
    $map = [
        'available' => 'green',
        'booked' => 'orange',
        'playing' => 'cyan',
        'maintenance' => 'red'
    ];
    $label = ucfirst($status);
    $tone = $map[$status] ?? 'purple';
    return "<span class=\"badge-neon badge-{$tone}\">{$label}</span>";
}

function getBookingStatusBadge(string $status): string {
    $map = [
        'pending' => 'orange',
        'confirmed' => 'purple',
        'playing' => 'cyan',
        'completed' => 'green',
        'cancelled' => 'red'
    ];
    $label = ucfirst($status);
    $tone = $map[$status] ?? 'purple';
    return "<span class=\"badge-neon badge-{$tone}\">{$label}</span>";
}

function getMembershipLevelBadge(string $level): string {
    $map = [
        'bronze' => 'badge-membership-bronze',
        'silver' => 'badge-membership-silver',
        'gold' => 'badge-membership-gold'
    ];
    $cls = $map[$level] ?? 'badge-neon badge-purple';
    return "<span class=\"{$cls}\">" . ucfirst($level) . "</span>";
}

function getBookingDurationOptions(): array {
    return [
        1 => '1 Hour',
        2 => '2 Hours',
        3 => '3 Hours',
        4 => '4 Hours',
        5 => '5 Hours',
        6 => '6 Hours',
        7 => '7 Hours',
        8 => '8 Hours'
    ];
}

function getPaymentMethodBadge(string $method): string {
    $map = [
        'qris' => 'purple',
        'bank_transfer' => 'cyan',
        'e_wallet' => 'green'
    ];
    $labels = [
        'qris' => 'QRIS',
        'bank_transfer' => 'Bank Transfer',
        'e_wallet' => 'E-Wallet'
    ];
    $tone = $map[$method] ?? 'purple';
    $label = $labels[$method] ?? ucfirst($method);
    return "<span class=\"badge-neon badge-{$tone}\">{$label}</span>";
}

function getPaymentStatusBadge(string $status): string {
    $map = [
        'pending' => 'orange',
        'paid' => 'green',
        'failed' => 'red',
        'cancelled' => 'purple'
    ];
    $label = ucfirst($status);
    $tone = $map[$status] ?? 'purple';
    return "<span class=\"badge-neon badge-{$tone}\">{$label}</span>";
}

function getMembershipDiscountRate(string $level): float {
    return match ($level) {
        'gold' => 0.10,
        'silver' => 0.05,
        default => 0.0,
    };
}

function getMembershipDiscountLabel(string $level): string {
    return match ($level) {
        'gold' => 'Gold (10% OFF)',
        'silver' => 'Silver (5% OFF)',
        default => 'Bronze (No discount)',
    };
}

/**
 * Build a QR Code image URL from a live, keyless QR API.
 * (Browser fetches it directly — same external-asset model the app already uses for CDNs.)
 */
function qrImageUrl(string $data, int $size = 200): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
         . '&ecc=L&data=' . urlencode($data);
}
