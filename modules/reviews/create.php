<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../../dashboard.php'); exit; }

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    flashMessage('Permintaan tidak valid.', 'danger');
    header('Location: ../../dashboard.php?page=bookings_view&id=' . (int)($_POST['booking_id'] ?? 0));
    exit;
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$deviceId = (int) ($_POST['device_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

if ($bookingId <= 0 || $deviceId <= 0 || $rating < 1 || $rating > 5) {
    flashMessage('Invalid review data.', 'danger');
    header('Location: ../../dashboard.php?page=bookings_view&id=' . $bookingId);
    exit;
}

global $pdo;

try {
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND booking_status = 'completed' LIMIT 1");
    $stmt->execute([$bookingId, getCurrentUserId()]);
    if (!$stmt->fetch()) {
        flashMessage('Booking not found or not completed.', 'danger');
        header('Location: ../../dashboard.php?page=bookings');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ? LIMIT 1");
    $stmt->execute([$bookingId]);
    if ($stmt->fetch()) {
        flashMessage('You already reviewed this booking.', 'warning');
    } else {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, device_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([getCurrentUserId(), $deviceId, $bookingId, $rating, $comment]);
        flashMessage('Review submitted! Thanks for your feedback.', 'success');
    }
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
}

header('Location: ../../dashboard.php?page=bookings_view&id=' . $bookingId);
