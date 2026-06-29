<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function escape(string $string): string {
    global $pdo;
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
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
    return 'INV-' . date('Ymd') . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
}

function calculateBookingPrice(float $pricePerHour, int $durationHours): float {
    return $pricePerHour * $durationHours;
}

function checkDeviceAvailability(int $deviceId, string $bookingDate, string $startTime, int $durationHours): bool {
    global $pdo;
    
    $endTime = date('H:i:s', strtotime($startTime) + ($durationHours * 3600));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings 
        WHERE device_id = :device_id 
        AND booking_date = :booking_date 
        AND booking_status IN ('pending', 'confirmed', 'playing')
        AND NOT (
            (:end_time <= start_time OR :start_time >= ADDTIME(start_time, SEC_TO_TIME(duration_hours * 3600)))
        )
    ");
    
    $stmt->execute([
        'device_id' => $deviceId,
        'booking_date' => $bookingDate,
        'end_time' => $endTime,
        'start_time' => $startTime,
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
    $badges = [
        'available' => '<span class="badge bg-success">Available</span>',
        'booked' => '<span class="badge bg-warning text-dark">Booked</span>',
        'playing' => '<span class="badge bg-info text-dark">Playing</span>',
        'maintenance' => '<span class="badge bg-danger">Maintenance</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getBookingStatusBadge(string $status): string {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'confirmed' => '<span class="badge bg-primary">Confirmed</span>',
        'playing' => '<span class="badge bg-info text-dark">Playing</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getMembershipLevelBadge(string $level): string {
    $badges = [
        'bronze' => '<span class="badge bg-warning text-dark">Bronze</span>',
        'silver' => '<span class="badge bg-secondary">Silver</span>',
        'gold' => '<span class="badge bg-warning">Gold</span>'
    ];
    
    return $badges[$level] ?? '<span class="badge bg-secondary">Unknown</span>';
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
    $badges = [
        'qris' => '<span class="badge bg-primary">QRIS</span>',
        'bank_transfer' => '<span class="badge bg-info text-dark">Bank Transfer</span>',
        'e_wallet' => '<span class="badge bg-success">E-Wallet</span>'
    ];
    
    return $badges[$method] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getPaymentStatusBadge(string $status): string {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
