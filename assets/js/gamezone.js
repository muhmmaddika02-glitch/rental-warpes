document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    initTooltips();
    initConfirmations();
});

function loadNotifications() {
    fetch('api/notifications.php?count=true')
        .then(response => response.json())
        .then(data => {
            const notifCount = document.getElementById('notif-count');
            const notifHeader = document.getElementById('notif-header');
            if (notifCount && data.count > 0) {
                notifCount.textContent = data.count;
                notifCount.style.display = 'inline-block';
            } else if (notifCount) {
                notifCount.style.display = 'none';
            }
            if (notifHeader) {
                notifHeader.textContent = data.count + ' Notification' + (data.count !== 1 ? 's' : '');
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initConfirmations() {
    document.querySelectorAll('.confirm-action').forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm-message') || 'Are you sure?')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function calculateTotal() {
    const pricePerHour = parseFloat(document.getElementById('price_per_hour')?.value || 0);
    const duration = parseInt(document.getElementById('duration_hours')?.value || 0);
    const totalPrice = pricePerHour * duration;
    
    const totalElement = document.getElementById('total_price');
    if (totalElement) {
        totalElement.textContent = formatRupiah(totalPrice);
    }
    
    const hiddenTotal = document.getElementById('total_price_hidden');
    if (hiddenTotal) {
        hiddenTotal.value = totalPrice;
    }
}

function printInvoice() {
    window.print();
}

function downloadQRCode(bookingId) {
    window.location.href = 'api/download-qr.php?booking_id=' + bookingId;
}

function refreshDeviceStatus() {
    fetch('api/devices-status.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(device => {
                const statusBadge = document.querySelector(`[data-device-id="${device.id}"] .device-status`);
                if (statusBadge) {
                    statusBadge.className = 'badge device-status bg-' + getStatusColor(device.status);
                    statusBadge.textContent = device.status.charAt(0).toUpperCase() + device.status.slice(1);
                }
            });
        })
        .catch(error => console.error('Error refreshing device status:', error));
}

function getStatusColor(status) {
    const colors = {
        'available': 'success',
        'booked': 'warning',
        'playing': 'info',
        'maintenance': 'danger'
    };
    return colors[status] || 'secondary';
}

setInterval(loadNotifications, 60000);
setInterval(refreshDeviceStatus, 30000);