<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flashMessage('Invalid booking ID.', 'danger');
    header('Location: dashboard.php?page=bookings');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        flashMessage('Booking not found.', 'danger');
        header('Location: dashboard.php?page=bookings');
        exit;
    }
    
    if (isCustomer() && $booking['user_id'] !== getCurrentUserId()) {
        flashMessage('Access denied.', 'danger');
        header('Location: dashboard.php?page=bookings');
        exit;
    }
    
    if (!in_array($booking['booking_status'], ['pending'])) {
        flashMessage('Only pending bookings can be cancelled.', 'warning');
        header('Location: dashboard.php?page=bookings');
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
    if ($stmt->execute([$id])) {
        $stmt = $pdo->prepare("UPDATE invoice_details SET payment_status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$id]);
        
        addNotification($booking['user_id'], 'Booking Cancelled', 'Your booking #' . $id . ' has been cancelled.', 'system');
        
        flashMessage('Booking cancelled successfully.', 'success');
    } else {
        flashMessage('Failed to cancel booking.', 'danger');
    }
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
}

header('Location: dashboard.php?page=bookings');
exit;