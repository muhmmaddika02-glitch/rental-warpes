<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

try {
    if ($action === 'update_status') {
        $bookingId = (int) ($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';
        
        if ($bookingId <= 0 || empty($status)) {
            $response = ['success' => false, 'message' => 'Missing required parameters.'];
        } elseif (!in_array($status, ['pending', 'confirmed', 'playing', 'completed', 'cancelled'])) {
            $response = ['success' => false, 'message' => 'Invalid status.'];
        } else {
            $stmt = $pdo->prepare("
                SELECT b.*, d.id as device_id 
                FROM bookings b
                JOIN devices d ON b.device_id = d.id
                WHERE b.id = ? LIMIT 1
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $response = ['success' => false, 'message' => 'Booking not found.'];
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                $stmt->execute([$status, $bookingId]);
                
                if ($status === 'playing') {
                    $stmt = $pdo->prepare("UPDATE devices SET status = 'playing' WHERE id = ?");
                    $stmt->execute([$booking['device_id']]);
                    
                    addNotification($booking['user_id'], 'Session Started', 'Your gaming session for booking #' . $bookingId . ' has started. Have fun!', 'booking_confirmation');
                } elseif ($status === 'completed') {
                    $stmt = $pdo->prepare("UPDATE devices SET status = 'available' WHERE id = ?");
                    $stmt->execute([$booking['device_id']]);
                    
                    $stmt = $pdo->prepare("UPDATE users SET points = points + FLOOR(? / 10000) WHERE id = ?");
                    $stmt->execute([$booking['total_price'], $booking['user_id']]);
                    
                    addNotification($booking['user_id'], 'Session Completed', 'Your gaming session for booking #' . $bookingId . ' has ended. Please leave a review!', 'system');
                }
                
                $response = ['success' => true, 'message' => 'Status updated to ' . $status . '.'];
            }
        }
    } elseif ($action === 'count') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([getCurrentUserId()]);
        $count = $stmt->fetch()['total'];
        
        $response = ['success' => true, 'count' => (int)$count];
    } else {
        $response = ['success' => false, 'message' => 'Unknown action.'];
    }
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Database error.'];
}

echo json_encode($response);