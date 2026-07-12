document.addEventListener('DOMContentLoaded', function () {
  loadNotifications();
  initTooltips();
  initConfirmations();
  initSidebar();
});

function loadNotifications() {
  fetch('api/notifications.php?count=true')
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('notif-count');
      const hd = document.getElementById('notif-header');
      if (el) {
        el.textContent = data.count || '0';
        el.style.display = data.count > 0 ? 'inline-flex' : 'none';
      }
      if (hd) hd.textContent = data.count + ' Notification' + (data.count !== 1 ? 's' : '');
    })
    .catch(() => {});
}

function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    try { new bootstrap.Tooltip(el); } catch (e) {}
  });
}

function initConfirmations() {
  document.querySelectorAll('.confirm-action').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirmMessage || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });
}

function initSidebar() {
  const toggle = document.querySelector('[data-lte-toggle="sidebar"]');
  if (toggle) {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.toggle('sidebar-open');
    });
  }
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('sidebar-open')) {
      const sidebar = document.querySelector('.app-sidebar');
      if (sidebar && !sidebar.contains(e.target) && !toggle?.contains(e.target)) {
        document.body.classList.remove('sidebar-open');
      }
    }
  });
}

function formatRupiah(amount) {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function calculateTotal() {
  const price = parseFloat(document.getElementById('price_per_hour')?.value || 0);
  const dur = parseInt(document.getElementById('duration_hours')?.value || 0);
  const total = price * dur;
  const el = document.getElementById('total_price');
  if (el) el.textContent = formatRupiah(total);
  const hidden = document.getElementById('total_price_hidden');
  if (hidden) hidden.value = total;
}

function printInvoice() { window.print(); }

function downloadQRCode(id) {
  window.location.href = 'api/download-qr.php?booking_id=' + id;
}

function refreshDeviceStatus() {
  fetch('api/devices-status.php')
    .then(r => r.json())
    .then(data => {
      data.forEach(d => {
        const badge = document.querySelector(`[data-device-id="${d.id}"] .device-status`);
        if (badge) {
          badge.className = 'badge-neon badge-' + getStatusColor(d.status);
          badge.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
        }
      });
    })
    .catch(() => {});
}

function getStatusColor(status) {
  return ({ available: 'green', booked: 'orange', playing: 'cyan', maintenance: 'red' })[status] || 'purple';
}

setInterval(loadNotifications, 60000);
setInterval(refreshDeviceStatus, 30000);
