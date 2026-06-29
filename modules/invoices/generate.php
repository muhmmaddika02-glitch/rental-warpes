<?php
require_once __DIR__ . '/../../inc/auth.php';

if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
    die('Invalid booking ID.');
}

$bookingId = (int) $_GET['id'];

global $pdo;

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
               d.name as device_name, d.type as device_type,
               inv.invoice_number, inv.payment_status, inv.invoice_date,
               p.payment_method, p.paid_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN devices d ON b.device_id = d.id
        LEFT JOIN invoice_details inv ON inv.booking_id = b.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die('Booking not found.');
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$invoiceNumber = $booking['invoice_number'] ?? generateInvoiceNumber($bookingId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= htmlspecialchars($invoiceNumber) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            color: #333;
        }
        .invoice-header {
            border-bottom: 3px solid #7C3AED;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            color: #7C3AED;
            font-weight: bold;
        }
        .invoice-details td {
            padding: 8px 0;
        }
        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .status-paid {
            color: #198754;
            font-weight: bold;
        }
        .status-unpaid {
            color: #ffc107;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 0.9rem;
            color: #666;
            text-align: center;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
        .btn-print {
            background: #7C3AED;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-print:hover { background: #6D28D9; }
    </style>
</head>
<body>
    <div class="no-print mb-4 text-end">
        <button onclick="window.print()" class="btn-print">
            <i class="bi bi-printer"></i> Print / Download PDF
        </button>
        <a href="../dashboard.php?page=bookings_view&id=<?= $bookingId ?>" class="btn btn-secondary ms-2">Back</a>
    </div>
    
    <div class="invoice-header">
        <div class="row">
            <div class="col-6">
                <h1 class="invoice-title">GameZone</h1>
                <p class="mb-1">Gaming Room Booking System</p>
                <p class="mb-0">invoice@gamezone.com</p>
            </div>
            <div class="col-6 text-end">
                <h2>INVOICE</h2>
                <p class="mb-1"><strong>Invoice #:</strong> <?= htmlspecialchars($invoiceNumber) ?></p>
                <p class="mb-0"><strong>Date:</strong> <?= formatDate($booking['invoice_date'] ?? date('Y-m-d'), 'd M Y') ?></p>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-6">
            <h5>Bill To:</h5>
            <p class="mb-1"><strong><?= htmlspecialchars($booking['customer_name']) ?></strong></p>
            <p class="mb-1"><?= htmlspecialchars($booking['customer_email']) ?></p>
            <p class="mb-0"><?= htmlspecialchars($booking['customer_phone'] ?? '-') ?></p>
        </div>
        <div class="col-6 text-end">
            <h5>Booking Details:</h5>
            <p class="mb-1"><strong>Booking ID:</strong> #<?= $booking['id'] ?></p>
            <p class="mb-1"><strong>Status:</strong> 
                <?= $booking['payment_status'] === 'paid' ? '<span class="status-paid">PAID</span>' : '<span class="status-unpaid">UNPAID</span>' ?>
            </p>
        </div>
    </div>
    
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th>Date</th>
                <th>Time</th>
                <th>Duration</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($booking['device_name']) ?></td>
                <td><?= htmlspecialchars($booking['device_type']) ?></td>
                <td><?= formatDate($booking['booking_date'], 'd M Y') ?></td>
                <td><?= date('H:i', strtotime($booking['start_time'])) ?></td>
                <td><?= $booking['duration_hours'] ?> hour<?= $booking['duration_hours'] > 1 ? 's' : '' ?></td>
                <td class="text-end"><?= formatRupiah($booking['total_price']) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                <td class="text-end"><?= formatRupiah($booking['total_price']) ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-end"><strong>Tax (0%):</strong></td>
                <td class="text-end"><?= formatRupiah(0) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="5" class="text-end"><strong>TOTAL:</strong></td>
                <td class="text-end"><?= formatRupiah($booking['total_price']) ?></td>
            </tr>
        </tfoot>
    </table>
    
    <?php if ($booking['payment_method']): ?>
    <div class="row mt-4">
        <div class="col-6">
            <h5>Payment Method:</h5>
            <p><?= strtoupper(str_replace('_', ' ', $booking['payment_method'])) ?></p>
        </div>
        <div class="col-6 text-end">
            <h5>Paid Date:</h5>
            <p><?= $booking['paid_at'] ? formatDate($booking['paid_at'], 'd M Y H:i') : '-' ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p><strong>GameZone Gaming Room</strong> - Thank you for your business!</p>
        <p class="mb-0">This invoice is valid. Payment is due within 1 hour of booking.</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"></script>
</body>
</html>